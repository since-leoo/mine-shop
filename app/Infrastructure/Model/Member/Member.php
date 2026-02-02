<?php

declare(strict_types=1);
/**
 * This file is part of MineAdmin.
 *
 * @link     https://www.mineadmin.com
 * @document https://doc.mineadmin.com
 * @contact  root@imoi.cn
 * @license  https://github.com/mineadmin/MineAdmin/blob/master/LICENSE
 */

namespace App\Infrastructure\Model\Member;

use App\Infrastructure\Model\Concerns\LoadsRelations;
use Carbon\Carbon;
use Hyperf\Database\Model\Collection as ModelCollection;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\Database\Model\Relations\BelongsToMany;
use Hyperf\Database\Model\Relations\HasMany;
use Hyperf\Database\Model\Relations\HasOne;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property string $openid
 * @property null|string $unionid
 * @property null|string $nickname
 * @property null|string $avatar
 * @property string $gender
 * @property null|string $phone
 * @property null|Carbon $birthday
 * @property null|string $city
 * @property null|string $province
 * @property null|string $district
 * @property null|string $street
 * @property null|string $region_path
 * @property null|string $country
 * @property string $level
 * @property null|int $level_id
 * @property int $growth_value
 * @property int $total_orders
 * @property float $total_amount
 * @property null|Carbon $last_login_at
 * @property null|string $last_login_ip
 * @property string $status
 * @property string $source
 * @property null|string $remark
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Member extends Model
{
    use LoadsRelations;

    protected ?string $table = 'members';

    protected array $fillable = [
        'openid',
        'unionid',
        'nickname',
        'avatar',
        'gender',
        'phone',
        'birthday',
        'city',
        'province',
        'district',
        'street',
        'region_path',
        'country',
        'level',
        'level_id',
        'growth_value',
        'total_orders',
        'total_amount',
        'last_login_at',
        'last_login_ip',
        'status',
        'source',
        'remark',
    ];

    protected array $casts = [
        'birthday' => 'date',
        'growth_value' => 'integer',
        'level_id' => 'integer',
        'total_orders' => 'integer',
        'total_amount' => 'decimal:2',
        'last_login_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected array $appends = [
        'points_balance',
        'points_total',
        'level_info',
    ];

    public function addresses(): HasMany
    {
        $relation = $this->hasMany(MemberAddress::class, 'member_id', 'id');
        $relation->select([
            'id',
            'member_id',
            'receiver_name',
            'receiver_phone',
            'province',
            'city',
            'district',
            'detail',
            'full_address',
            'is_default',
            'created_at',
        ]);

        return $relation;
    }

    public function wallet(): HasOne
    {
        $relation = $this->hasOne(MemberWallet::class, 'member_id', 'id');
        $relation->select([
            'id',
            'member_id',
            'type',
            'balance',
            'frozen_balance',
            'total_recharge',
            'total_consume',
            'status',
        ]);
        $relation->where('type', 'balance');
        return $relation;
    }

    public function pointsWallet(): HasOne
    {
        $relation = $this->hasOne(MemberWallet::class, 'member_id', 'id');
        $relation->select([
            'id',
            'member_id',
            'type',
            'balance',
            'frozen_balance',
            'total_recharge',
            'total_consume',
            'status',
        ]);
        $relation->where('type', 'points');
        return $relation;
    }

    public function wallets(): HasMany
    {
        $relation = $this->hasMany(MemberWallet::class, 'member_id', 'id');
        $relation->select([
            'id',
            'member_id',
            'type',
            'balance',
            'frozen_balance',
            'total_recharge',
            'total_consume',
            'status',
        ]);

        return $relation;
    }

    public function levelDefinition(): BelongsTo
    {
        $relation = $this->belongsTo(MemberLevel::class, 'level_id', 'id');
        $relation->select(['id', 'name', 'level']);
        return $relation;
    }

    public function walletTransactions(): HasMany
    {
        $relation = $this->hasMany(MemberWalletTransaction::class, 'member_id', 'id');
        $relation->orderByDesc('id');
        return $relation;
    }

    public function tags(): BelongsToMany
    {
        $relation = $this->belongsToMany(MemberTag::class, 'mall_member_tag_relations', 'member_id', 'tag_id')
            ->withTimestamps();
        $relation->select([
            'mall_member_tags.id',
            'mall_member_tags.name',
            'mall_member_tags.color',
            'mall_member_tags.status',
        ]);

        return $relation;
    }

    public function getPointsBalanceAttribute(): int
    {
        $wallet = $this->getLoadedPointsWallet();

        return $wallet ? (int) $wallet->balance : 0;
    }

    public function getPointsTotalAttribute(): int
    {
        $wallet = $this->getLoadedPointsWallet();

        return $wallet ? (int) $wallet->total_recharge : 0;
    }

    public function getLevelInfoAttribute(): ?array
    {
        if (! $this->relationLoaded('levelDefinition')) {
            return null;
        }

        $level = $this->getRelation('levelDefinition');

        return $level ? $level->toArray() : null;
    }

    private function getLoadedPointsWallet(): ?MemberWallet
    {
        if ($this->relationLoaded('pointsWallet')) {
            $wallet = $this->getRelation('pointsWallet');
            return $wallet instanceof MemberWallet ? $wallet : null;
        }

        if ($this->relationLoaded('wallets')) {
            $wallets = $this->getRelation('wallets');
            if ($wallets instanceof ModelCollection) {
                $wallet = $wallets->first(static function ($wallet): bool {
                    return $wallet instanceof MemberWallet && $wallet->type === 'points';
                });
                return $wallet instanceof MemberWallet ? $wallet : null;
            }
        }

        return null;
    }
}
