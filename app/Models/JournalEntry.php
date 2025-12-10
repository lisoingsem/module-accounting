<?php

declare(strict_types=1);

namespace Modules\Accounting\Models;

use App\Models\User;
use App\Traits\HasUuidTrait;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Accounting\Enums\EntryStatus;
use Modules\Accounting\Enums\JournalEntryType;

final class JournalEntry extends Model
{
    use HasFactory;
    use HasUuidTrait;
    use SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'accounting_journal_entries';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'entry_number',
        'entry_date',
        'type',
        'status',
        'description',
        'reference',
        'period_id',
        'created_by',
        'posted_by',
        'posted_at',
        'source_type',
        'source_id',
        'notes',
    ];

    /**
     * Get the accounting period for this entry.
     *
     * @return BelongsTo<AccountingPeriod, $this>
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class, 'period_id');
    }

    /**
     * Get the user who created this entry.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who posted this entry.
     *
     * @return BelongsTo<User, $this>
     */
    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    /**
     * Get journal entry lines.
     *
     * @return HasMany<JournalEntryLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class, 'journal_entry_id')->orderBy('line_number');
    }

    /**
     * Get the source model (polymorphic).
     */
    public function source(): MorphTo
    {
        return $this->morphTo('source');
    }

    /**
     * Get total debits.
     */
    public function getTotalDebits(): float
    {
        return (float) $this->lines()->where('type', 'debit')->sum('amount');
    }

    /**
     * Get total credits.
     */
    public function getTotalCredits(): float
    {
        return (float) $this->lines()->where('type', 'credit')->sum('amount');
    }

    /**
     * Check if entry is balanced (debits = credits).
     */
    public function isBalanced(): bool
    {
        return abs($this->getTotalDebits() - $this->getTotalCredits()) < 0.01;
    }

    /**
     * Check if entry is posted.
     */
    public function isPosted(): bool
    {
        return $this->status === EntryStatus::POSTED->value;
    }

    /**
     * Check if entry is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === EntryStatus::DRAFT->value;
    }

    /**
     * Scope to get posted entries.
     */
    #[Scope]
    protected function posted($query)
    {
        return $query->where('status', EntryStatus::POSTED->value);
    }

    /**
     * Scope to get draft entries.
     */
    #[Scope]
    protected function draft($query)
    {
        return $query->where('status', EntryStatus::DRAFT->value);
    }

    /**
     * Scope to get manual entries.
     */
    #[Scope]
    protected function manual($query)
    {
        return $query->where('type', JournalEntryType::MANUAL->value);
    }

    /**
     * Scope to get auto entries.
     */
    #[Scope]
    protected function auto($query)
    {
        return $query->where('type', JournalEntryType::AUTO->value);
    }

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'type' => JournalEntryType::class,
            'status' => EntryStatus::class,
            'posted_at' => 'datetime',
        ];
    }
}
