<?php

namespace Tests\Feature;

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
        $user = User::factory()->create(['name' => 'ريهام عبد الفتاح']);

        $this->get(route('users.index'))
            ->assertOk()
            ->assertSee($user->name)
            ->assertSee($user->email);
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
            'department' => 'العمليات',
            'branch' => 'الإسكندرية',
        ]);

        User::factory()->create([
            'status' => UserStatus::Suspended,
            'role' => 'فني تركيب',
            'department' => 'العمليات',
            'branch' => 'الإسكندرية',
        ]);

        $results = $this->get(route('users.index', [
            'status' => UserStatus::Active->value,
            'role' => 'فني تركيب',
            'department' => 'العمليات',
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
}
