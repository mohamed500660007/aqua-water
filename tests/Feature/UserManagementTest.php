<?php

namespace Tests\Feature;

use App\Domain\Users\Enums\Gender;
use App\Domain\Users\Enums\MaritalStatus;
use App\Domain\Users\Enums\MilitaryStatus;
use App\Domain\Users\Enums\UserStatus;
use App\Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'خالد منصور',
            'email' => 'khaled@aquawater.eg',
            'phone' => '01001112233',
            'role' => 'مدير النظام',
            'department' => 'الإدارة',
            'branch' => 'القاهرة',
            'status' => UserStatus::Active->value,
            'password' => 'secret-password',
        ], $overrides);
    }

    public function test_index_lists_users(): void
    {
        $user = User::factory()->create(['name' => 'ريهام عبد الفتاح', 'department' => 'المبيعات', 'hire_date' => '2025-03-01']);

        $this->get(route('users.index'))
            ->assertOk()
            ->assertSeeInOrder(['كود الموظف', 'اسم الموظف', 'القسم', 'الحالة', 'تاريخ التعيين'])
            ->assertSeeInOrder([$user->code(), $user->name, 'المبيعات', $user->status->label(), '2025-03-01'])
            ->assertDontSee($user->email);

        // البحث بكود الموظف
        $this->get(route('users.index', ['q' => $user->code()]))->assertOk()->assertSee($user->name);
    }

    public function test_index_paginates_results(): void
    {
        User::factory()->count(15)->create();

        $response = $this->get(route('users.index'));

        $this->assertCount(12, $response->viewData('users')->items());
        $this->assertSame(15, $response->viewData('users')->total());
    }

    public function test_index_searches_by_name_email_and_phone(): void
    {
        $target = User::factory()->create([
            'name' => 'سيد عبد الحميد',
            'email' => 'sayed@aquawater.eg',
            'phone' => '01003334455',
        ]);

        User::factory()->create(['name' => 'شخص آخر', 'email' => 'other@aquawater.eg', 'phone' => '01111111111']);

        foreach (['سيد', 'sayed@aquawater.eg', '01003334455', '3334455'] as $term) {
            $results = $this->get(route('users.index', ['q' => $term]))->viewData('users');

            $this->assertCount(1, $results->items(), "فشل البحث بـ: {$term}");
            $this->assertTrue($results->first()->is($target));
        }
    }

    public function test_index_filters_by_status_role_department_and_branch(): void
    {
        $target = User::factory()->create([
            'status' => UserStatus::Active,
            'role' => 'فني تركيب',
            'department' => 'الفنيين',
            'branch' => 'الإسكندرية',
        ]);

        User::factory()->create([
            'status' => UserStatus::Suspended,
            'role' => 'فني تركيب',
            'department' => 'الفنيين',
            'branch' => 'الإسكندرية',
        ]);

        $results = $this->get(route('users.index', [
            'status' => UserStatus::Active->value,
            'role' => 'فني تركيب',
            'department' => 'الفنيين',
            'branch' => 'الإسكندرية',
        ]))->viewData('users');

        $this->assertCount(1, $results->items());
        $this->assertTrue($results->first()->is($target));
    }

    public function test_index_sorts_by_whitelisted_column_only(): void
    {
        User::factory()->create(['name' => 'باسم']);
        User::factory()->create(['name' => 'أحمد']);

        $sorted = $this->get(route('users.index', ['sort' => 'name', 'dir' => 'asc']))->viewData('users');
        $this->assertSame(['أحمد', 'باسم'], $sorted->pluck('name')->all());

        $this->get(route('users.index', ['sort' => 'password', 'dir' => 'asc']))->assertOk();
    }

    public function test_it_creates_a_user(): void
    {
        $response = $this->post(route('users.store'), $this->validPayload());

        $user = User::firstWhere('email', 'khaled@aquawater.eg');

        $response->assertRedirect(route('users.show', $user));

        $this->assertSame('خالد منصور', $user->name);
        $this->assertSame('01001112233', $user->phone);
        $this->assertSame('مدير النظام', $user->role);
        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertTrue(Hash::check('secret-password', $user->password));
    }

    public function test_it_normalizes_email_and_phone_before_saving(): void
    {
        $this->post(route('users.store'), $this->validPayload([
            'email' => '  Khaled@AquaWater.EG ',
            'phone' => '+20 100 111 2233',
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'khaled@aquawater.eg',
            'phone' => '01001112233',
        ]);
    }

    public function test_it_validates_required_unique_and_lookup_fields(): void
    {
        User::factory()->create(['email' => 'khaled@aquawater.eg', 'phone' => '01001112233']);

        $this->post(route('users.store'), $this->validPayload([
            'name' => '',
            'email' => 'not-an-email',
            'phone' => '12345',
            'role' => 'دور غير موجود',
            'status' => 'unknown',
            'password' => 'short',
        ]))->assertSessionHasErrors(['name', 'email', 'phone', 'role', 'status', 'password']);

        $this->post(route('users.store'), $this->validPayload())
            ->assertSessionHasErrors(['email', 'phone']);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_optional_fields_may_be_left_empty(): void
    {
        $this->post(route('users.store'), $this->validPayload([
            'phone' => null,
            'role' => null,
            'department' => null,
            'branch' => null,
            'password' => null,
        ]))->assertSessionHasNoErrors();

        $user = User::sole();

        $this->assertNull($user->phone);
        $this->assertNull($user->role);
        $this->assertNull($user->password);
    }

    public function test_it_shows_user_details(): void
    {
        $user = User::factory()->create();

        $this->get(route('users.show', $user))
            ->assertOk()
            ->assertSee($user->name)
            ->assertSee($user->email);
    }

    public function test_create_and_edit_pages_render(): void
    {
        $user = User::factory()->create();

        $this->get(route('users.create'))->assertOk()->assertSee('مستخدم جديد');
        $this->get(route('users.edit', $user))->assertOk()->assertSee($user->name);
    }

    public function test_it_updates_a_user_and_keeps_the_password_when_left_empty(): void
    {
        $user = User::factory()->create(['password' => Hash::make('original-password')]);

        $this->put(route('users.update', $user), $this->validPayload([
            'name' => 'اسم بعد التعديل',
            'status' => UserStatus::OnLeave->value,
            'password' => null,
        ]))->assertRedirect(route('users.show', $user));

        $user->refresh();

        $this->assertSame('اسم بعد التعديل', $user->name);
        $this->assertSame(UserStatus::OnLeave, $user->status);
        $this->assertTrue(Hash::check('original-password', $user->password));
    }

    public function test_it_replaces_the_password_when_a_new_one_is_provided(): void
    {
        $user = User::factory()->create(['password' => Hash::make('original-password')]);

        $this->put(route('users.update', $user), $this->validPayload(['password' => 'brand-new-password']));

        $this->assertTrue(Hash::check('brand-new-password', $user->refresh()->password));
    }

    public function test_update_ignores_the_current_user_in_unique_rules(): void
    {
        $user = User::factory()->create(['email' => 'khaled@aquawater.eg', 'phone' => '01001112233']);

        $this->put(route('users.update', $user), $this->validPayload(['password' => null]))
            ->assertSessionHasNoErrors();
    }

    public function test_validation_errors_are_rendered_on_the_form(): void
    {
        $this->get(route('users.create'));

        $this->followingRedirects()
            ->post(route('users.store'), $this->validPayload(['name' => '', 'phone' => '12345']))
            ->assertOk()
            ->assertSee('حقل اسم المستخدم مطلوب.')
            ->assertSee('رقم الهاتف غير صحيح — اكتبه بالصيغة المحلية مثل 01012345678.');
    }

    public function test_delete_action_renders_inside_an_alpine_scope(): void
    {
        $user = User::factory()->create();

        foreach ([route('users.index'), route('users.show', $user)] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertStringContainsString(
                'action="'.route('users.destroy', $user).'" method="POST" x-data',
                $html,
            );

            // مؤشر على وجود توجيه Blade لم يُترجم داخل خصائص المكوّنات فيكسر تعبير Alpine
            $this->assertStringNotContainsString('@js(', $html);
        }
    }

    public function test_it_soft_deletes_a_user(): void
    {
        $user = User::factory()->create();

        $this->delete(route('users.destroy', $user))
            ->assertRedirect(route('users.index'));

        $this->assertSoftDeleted($user);
        $this->get(route('users.index'))->assertDontSee($user->email);
    }

    public function test_it_saves_the_full_identity_card_and_personal_data(): void
    {
        $this->post(route('users.store'), $this->validPayload([
            'national_id' => '29005121201234',
            'national_id_issued_at' => '2020-03-01',
            'national_id_expires_at' => '2027-03-01',
            'national_id_issuer' => 'سجل مدني المنصورة',
            'card_address' => '12 شارع الجلاء — المنصورة',
            'card_occupation' => 'فني',
            'religion' => 'مسلم',
            'marital_status' => 'married',
            'qualification' => 'دبلوم صنايع',
            'military_status' => 'completed',
            'secondary_phone' => '01112223344',
            'governorate' => 'الدقهلية',
            'city' => 'طلخا',
            'address' => 'بجوار المستشفى',
            'emergency_contact_name' => 'محمود منصور',
            'emergency_contact_relation' => 'الأخ',
            'emergency_contact_phone' => '01223334455',
            'hire_date' => '2024-01-10',
            'notes' => 'يجيد قيادة السيارات',
        ]))->assertSessionHasNoErrors();

        $user = User::firstWhere('email', 'khaled@aquawater.eg');

        // تُستخرج تلقائيًا من الرقم القومي لأنها لم تُرسل
        $this->assertSame('1990-05-12', $user->birth_date->toDateString());
        $this->assertSame(Gender::Male, $user->gender);
        $this->assertSame('الدقهلية', $user->birth_place);
        $this->assertSame('مصري', $user->nationality);

        $this->assertSame(MaritalStatus::Married, $user->marital_status);
        $this->assertSame(MilitaryStatus::Completed, $user->military_status);
        $this->assertSame('2027-03-01', $user->national_id_expires_at->toDateString());
        $this->assertSame('01223334455', $user->emergency_contact_phone);

        $this->get(route('users.show', $user))
            ->assertOk()
            ->assertSee('29005121201234')
            ->assertSee('سجل مدني المنصورة')
            ->assertSee('دبلوم صنايع')
            ->assertSee('محمود منصور')
            ->assertSee('يجيد قيادة السيارات');

        $this->assertSame(1, User::query()->search('29005121201234')->count());
    }

    public function test_it_rejects_identity_data_that_contradicts_the_national_id(): void
    {
        $this->post(route('users.store'), $this->validPayload([
            'national_id' => '29005121201234',
            'birth_date' => '1991-01-01',
            'gender' => 'female',
        ]))->assertSessionHasErrors(['birth_date', 'gender']);

        $this->post(route('users.store'), $this->validPayload(['national_id' => '29002301201234']))
            ->assertSessionHasErrors('national_id');

        $this->post(route('users.store'), $this->validPayload([
            'national_id_issued_at' => '2024-01-01',
            'national_id_expires_at' => '2023-01-01',
            'secondary_phone' => '01001112233',
        ]))->assertSessionHasErrors(['national_id_expires_at', 'secondary_phone']);

        User::factory()->create(['national_id' => '29005121201234']);
        $this->post(route('users.store'), $this->validPayload(['email' => 'other@aquawater.eg', 'phone' => null, 'national_id' => '29005121201234']))
            ->assertSessionHasErrors('national_id');
    }

    public function test_military_status_is_not_kept_for_female_employees(): void
    {
        $this->post(route('users.store'), $this->validPayload([
            'national_id' => '30102280101224',
            'military_status' => 'completed',
        ]))->assertSessionHasNoErrors();

        $user = User::firstWhere('email', 'khaled@aquawater.eg');
        $this->assertSame(Gender::Female, $user->gender);
        $this->assertNull($user->military_status);
    }

    public function test_create_form_focuses_on_important_personal_fields_without_repeated_card_details(): void
    {
        $html = $this->get(route('users.create'))->assertOk()->getContent();

        foreach (['national_id', 'marital_status', 'birth_date', 'gender', 'birth_place', 'nationality',
            'qualification', 'military_status', 'secondary_phone', 'governorate', 'city', 'address',
            'emergency_contact_name', 'emergency_contact_relation', 'emergency_contact_phone', 'hire_date', 'notes'] as $field) {
            $this->assertStringContainsString('name="'.$field.'"', $html, "الحقل غير موجود في النموذج: {$field}");
        }

        foreach (['national_id_issued_at', 'national_id_expires_at', 'national_id_issuer', 'card_address',
            'card_occupation', 'religion'] as $field) {
            $this->assertDoesNotMatchRegularExpression(
                '/<(?:input|select|textarea)\b[^>]*\bname="'.preg_quote($field, '/').'"/u',
                $html,
                "حقل البطاقة المكرر ما زال ظاهرًا: {$field}"
            );
        }
    }

    public function test_edit_form_preserves_hidden_legacy_card_details(): void
    {
        $user = User::factory()->create([
            'national_id_issued_at' => '2020-03-01',
            'national_id_expires_at' => '2027-03-01',
            'national_id_issuer' => 'سجل مدني المنصورة',
            'card_address' => '12 شارع الجلاء',
            'card_occupation' => 'فني',
            'religion' => 'مسلم',
        ]);

        $this->put(route('users.update', $user), $this->validPayload([
            'email' => $user->email,
            'phone' => $user->phone,
            'national_id_issued_at' => '2020-03-01',
            'national_id_expires_at' => '2027-03-01',
            'national_id_issuer' => 'سجل مدني المنصورة',
            'card_address' => '12 شارع الجلاء',
            'card_occupation' => 'فني',
            'religion' => 'مسلم',
        ]))->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('سجل مدني المنصورة', $user->national_id_issuer);
        $this->assertSame('12 شارع الجلاء', $user->card_address);
        $this->assertSame('فني', $user->card_occupation);
        $this->assertSame('مسلم', $user->religion);
    }

    public function test_employees_report_summarises_and_filters_the_workforce(): void
    {
        User::factory()->create([
            'name' => 'محصل مكتمل', 'department' => 'المحصّلين', 'status' => UserStatus::Active, 'base_salary' => 4000,
            'national_id' => '29005121201234', 'phone' => '01000000001', 'emergency_contact_phone' => '01000000002',
            'hire_date' => '2026-10-05', 'birth_date' => '1990-05-12', 'gender' => 'male',
        ]);
        User::factory()->create([
            'name' => 'سكرتيرة بلا بطاقة', 'department' => 'السكرتارية', 'status' => UserStatus::OnLeave, 'base_salary' => 3000,
            'national_id' => null, 'hire_date' => '2025-01-01', 'gender' => 'female',
        ]);

        $page = $this->get(route('users.report', ['from' => '2026-10-01', 'to' => '2026-10-31']))
            ->assertOk()->assertSee('size: A4 portrait', false);

        foreach (['name="q"', 'name="status"', 'name="role"', 'name="department"', 'name="branch"', 'name="gender"', 'name="content"', 'name="sort"'] as $field) {
            $page->assertSee($field, false);
        }

        $summary = $page->viewData('summary');
        $this->assertSame(2, $summary['total']);
        $this->assertSame(1, $summary['hired']);
        $this->assertSame(7000.0, $summary['salaries']);
        $this->assertSame(1, $summary['statuses']['on_leave']);
        $this->assertSame(1, $summary['missing_national_id']);
        $this->assertEqualsCanonicalizing(['المحصّلين', 'السكرتارية'], $page->viewData('byDepartment')->pluck('name')->all());
        $this->assertSame(['سكرتيرة بلا بطاقة'], $page->viewData('alerts')->pluck('user.name')->all());

        $women = $this->get(route('users.report', ['gender' => 'female']))->assertOk();
        $this->assertSame(['سكرتيرة بلا بطاقة'], $women->viewData('rows')->pluck('name')->all());

        $this->get(route('users.report', ['content' => 'summary']))
            ->assertOk()->assertDontSee('rpt-section-title">كشف الموظفين', false);

        $this->get(route('users.index'))->assertOk()->assertSee(route('users.report'), false);
    }
}
