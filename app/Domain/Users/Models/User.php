<?php

namespace App\Domain\Users\Models;

use App\Domain\Payroll\Models\Payroll;
use App\Domain\Users\Enums\Gender;
use App\Domain\Users\Enums\MaritalStatus;
use App\Domain\Users\Enums\MilitaryStatus;
use App\Domain\Users\Enums\UserStatus;
use App\Support\Digits;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    public const SORTABLE = ['name', 'email', 'role', 'status', 'created_at'];

    /** الأدوار التي يجوز أن تُسلَّم لها عهدة من المخزن */
    public const CUSTODY_ROLES = ['مندوب مبيعات', 'فني تركيب'];

    /** من يجوز تسجيله «مندوب مبيعات» على العقد */
    public const CONTRACT_SALES_ROLES = ['مندوب مبيعات', 'مندوبة', 'سكرتارية'];

    public const INSTALLER_ROLE = 'فني تركيب';

    protected static string $factory = UserFactory::class;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',
        'department',
        'branch',
        'status',
        'base_salary',
        'installment_commission',
        'device_commission',
        'installation_commission',
        'marketing_commission',
        'maintenance_commission',
        'password',
        'national_id',
        'national_id_issued_at',
        'national_id_expires_at',
        'national_id_issuer',
        'card_address',
        'card_occupation',
        'religion',
        'marital_status',
        'birth_date',
        'gender',
        'birth_place',
        'nationality',
        'qualification',
        'military_status',
        'secondary_phone',
        'governorate',
        'city',
        'address',
        'emergency_contact_name',
        'emergency_contact_relation',
        'emergency_contact_phone',
        'hire_date',
        'notes',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'base_salary' => 'decimal:2',
            'installment_commission' => 'decimal:2',
            'device_commission' => 'decimal:2',
            'installation_commission' => 'decimal:2',
            'marketing_commission' => 'decimal:2',
            'maintenance_commission' => 'decimal:2',
            'national_id_issued_at' => 'date',
            'national_id_expires_at' => 'date',
            'birth_date' => 'date',
            'hire_date' => 'date',
            'gender' => Gender::class,
            'marital_status' => MaritalStatus::class,
            'military_status' => MilitaryStatus::class,
        ];
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function scopeCustodians(Builder $query): void
    {
        $query->whereIn('role', self::CUSTODY_ROLES);
    }

    /** بحث بالاسم أو البريد أو رقم الهاتف أو الرقم القومي */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $digits = Digits::normalize($term);

        $query->where(function (Builder $query) use ($term, $digits) {
            $query->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%");

            if ($digits !== '') {
                $query->orWhere('phone', 'like', "%{$digits}%")
                    ->orWhere('national_id', 'like', "%{$digits}%");
            }
        });
    }

    /** العمر بالسنوات من تاريخ الميلاد */
    public function age(): ?int
    {
        return $this->birth_date?->age;
    }

    /** بطاقة الرقم القومي منتهية أو تنتهي خلال شهرين — تحتاج تجديدًا */
    public function nationalIdNeedsRenewal(): bool
    {
        return $this->national_id_expires_at !== null && $this->national_id_expires_at->lte(today()->addMonths(2));
    }

    /** @param  array<string, string|null>  $filters */
    public function scopeApplyFilters(Builder $query, array $filters): void
    {
        foreach (['status', 'role', 'department', 'branch', 'gender'] as $column) {
            $value = trim((string) ($filters[$column] ?? ''));

            $query->when($value !== '', fn (Builder $query) => $query->where($column, $value));
        }
    }

    public function scopeApplySort(Builder $query, ?string $column, ?string $direction): void
    {
        $column = in_array($column, self::SORTABLE, true) ? $column : 'created_at';
        $direction = strtolower((string) $direction) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($column, $direction)->orderBy('id', 'desc');
    }
}
