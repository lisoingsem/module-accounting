<?php

declare(strict_types=1);

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'accounting_journal_entry_lines')]
#[Fillable([
    'journal_entry_id',
    'account_id',
    'type',
    'amount',
    'description',
    'reference',
    'line_number',
])]
final class JournalEntryLine extends Model
{
    use HasFactory;

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
