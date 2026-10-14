<?php

namespace App\Models;

use App\Enums\TreeOrderStatus;
use App\Enums\TreePaymentMethod;
use App\TreeOrders\TreeCatalog;
use Carbon\CarbonImmutable;
use Database\Factories\TreeOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A Christmas tree order, paid with HelloAsso or by cheque.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $customer_name
 * @property string $customer_phone
 * @property string|null $customer_email
 * @property TreePaymentMethod|null $payment_method
 * @property int $total_cents
 * @property CarbonImmutable|null $paid_at
 * @property int|null $paid_by
 * @property int|null $helloasso_checkout_id
 * @property int|null $helloasso_order_id
 * @property CarbonImmutable|null $picked_up_at
 * @property CarbonImmutable|null $cancelled_at
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read string $number
 * @property-read User|null $user
 * @property-read Collection<int, TreeOrderLine> $lines
 */
#[Fillable(['user_id', 'customer_name', 'customer_phone', 'customer_email', 'total_cents', 'created_by'])]
class TreeOrder extends Model
{
    /** @use HasFactory<TreeOrderFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payment_method' => TreePaymentMethod::class,
            'total_cents' => 'integer',
            'paid_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'helloasso_checkout_id' => 'integer',
            'helloasso_order_id' => 'integer',
        ];
    }

    /**
     * Readable number, "S-0042".
     *
     * @return Attribute<string, never>
     */
    protected function number(): Attribute
    {
        return Attribute::get(fn (): string => 'S-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<TreeOrderLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(TreeOrderLine::class);
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    public function isPickedUp(): bool
    {
        return $this->picked_up_at !== null;
    }

    public function quantity(): int
    {
        return (int) $this->lines->sum('quantity');
    }

    public function status(): TreeOrderStatus
    {
        return match (true) {
            $this->isCancelled() => TreeOrderStatus::Cancelled,
            $this->isPickedUp() => TreeOrderStatus::PickedUp,
            $this->isPaid() => TreeOrderStatus::Paid,
            app(TreeCatalog::class)->isClosed() => TreeOrderStatus::Expired,
            default => TreeOrderStatus::Pending,
        };
    }

    /**
     * @param  Builder<TreeOrder>  $query
     */
    public function scopeWithStatus(Builder $query, TreeOrderStatus $status): void
    {
        $closed = app(TreeCatalog::class)->isClosed();

        match ($status) {
            TreeOrderStatus::Cancelled => $query->whereNotNull('cancelled_at'),
            TreeOrderStatus::PickedUp => $query->whereNull('cancelled_at')->whereNotNull('picked_up_at'),
            TreeOrderStatus::Paid => $query->whereNull('cancelled_at')->whereNull('picked_up_at')->whereNotNull('paid_at'),
            TreeOrderStatus::Pending => $query->whereNull('cancelled_at')->whereNull('paid_at')->when($closed, fn (Builder $query) => $query->whereRaw('1 = 0')),
            TreeOrderStatus::Expired => $query->whereNull('cancelled_at')->whereNull('paid_at')->when(! $closed, fn (Builder $query) => $query->whereRaw('1 = 0')),
        };
    }

    /**
     * Paid and not cancelled: the trees to order from the producer.
     *
     * @param  Builder<TreeOrder>  $query
     */
    public function scopeCounted(Builder $query): void
    {
        $query->whereNotNull('paid_at')->whereNull('cancelled_at');
    }

    /**
     * By customer name, or by number ("S-0042", "42").
     *
     * @param  Builder<TreeOrder>  $query
     */
    public function scopeSearch(Builder $query, string $term): void
    {
        $term = trim($term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term): void {
            $query->where('customer_name', 'like', '%'.$term.'%');

            if (preg_match('/^(?:s-?)?0*(\d+)$/i', $term, $matches)) {
                $query->orWhere('id', (int) $matches[1]);
            }
        });
    }
}
