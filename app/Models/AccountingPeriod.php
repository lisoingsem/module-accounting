<?php

declare(strict_types=1);

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use App\Concerns\HasUuid;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table(name: 'accounting_periods')]
#[Fillable([
    'name',
    'start_date',
    'end_date',
    'is_closed',
    'closed_at',
    'closed_by',
    'notes',
])]
final class AccountingPeriod extends Model
{
    use HasFactory;
    use HasUuid;
    use SoftDeletes;

    /**
     * Get the user who closed this period.
     *
     * @return BelongsTo<User, $this>
     */
    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /**
     * Get journal entries for this period.
     *
     * @return HasMany<JournalEntry, $this>
     */
    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class, 'period_id');
    }

    /**
     * Check if period is open.
     */
    public function isOpen(): bool
    {
        return ! $this->is_closed;
    }

    /**
     * Check if a date falls within this period.
     */
    public function containsDate(string $date): bool
    {
        return $date >= $this->start_date && $date <= $this->end_date;
    }

    /**
     * Scope to get open periods.
     */
    #[Scope]
    protected function open($query)
    {
        return $query->where('is_closed', false);
    }

    /**
     * Scope to get closed periods.
     */
    #[Scope]
    protected function closed($query)
    {
        return $query->where('is_closed', true);
    }

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_closed' => 'boolean',
            'closed_at' => 'datetime',
        ];
    }
}
