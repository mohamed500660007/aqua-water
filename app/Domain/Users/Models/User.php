<?php

namespace App\Domain\Users\Models;

use App\Domain\Users\Enums\UserStatus;
use App\Support\Digits;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    public const SORTABLE = ['name', 'email', 'role', 'status', 'created_at'];

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
        'password',
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
        ];
    }

    /** بحث بالاسم أو البريد أو رقم الهاتف */
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
                $query->orWhere('phone', 'like', "%{$digits}%");
            }
        });
    }

    /** @param  array<string, string|null>  $filters */
    public function scopeApplyFilters(Builder $query, array $filters): void
    {
        foreach (['status', 'role', 'department', 'branch'] as $column) {
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
