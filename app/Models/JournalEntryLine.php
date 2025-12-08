<?php

declare(strict_types=1);

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class JournalEntryLine extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'accounting_journal_entry_lines';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'journal_entry_id',
        'account_id',
        'type',
        'amount',
        'description',
        'reference',
        'line_number',
    ];

    /**
     * Get the journal entry for this line.
     *
     * @return BelongsTo<JournalEntry, $this>
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    /**
     * Get the account for this line.
     *
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /**
     * Check if this is a debit line.
     */
    public function isDebit(): bool
    {
        return 'debit' === $this->type;
    }

    /**
     * Check if this is a credit line.
     */
    public function isCredit(): bool
    {
        return 'credit' === $this->type;
    }

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'line_number' => 'integer',
        ];
    }
}
