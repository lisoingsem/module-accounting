<?php

declare(strict_types=1);

namespace Modules\Accounting\Models;

use App\Traits\HasUuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Accounting\Enums\AccountType;

final class Account extends Model
{
    use HasFactory;
    use HasUuidTrait;
    use SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'accounting_accounts';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'code',
        'name',
        'type',
        'description',
        'parent_id',
        'level',
        'is_active',
        'is_system',
        'opening_balance',
        'current_balance',
        'currency',
        'sort_order',
    ];

    /**
     * Get the parent account.
     *
     * @return BelongsTo<Account, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    /**
     * Get child accounts.
     *
     * @return HasMany<Account, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Get journal entry lines for this account.
     *
     * @return HasMany<JournalEntryLine, $this>
     */
    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class, 'account_id');
    }

    /**
     * Get the account type enum.
     */
    public function getTypeEnum(): AccountType
    {
        if ($this->type instanceof AccountType) {
            return $this->type;
        }

        return AccountType::from($this->type);
    }

    /**
     * Get total debits for this account.
     */
    public function getTotalDebits(): float
    {
        return (float) $this->journalEntryLines()
            ->where('type', 'debit')
            ->sum('amount');
    }

    /**
     * Get total credits for this account.
     */
    public function getTotalCredits(): float
    {
        return (float) $this->journalEntryLines()
            ->where('type', 'credit')
            ->sum('amount');
    }

    /**
     * Get account balance (debits - credits for assets/expenses, credits - debits for others).
     */
    public function getBalance(): float
    {
        $debits = $this->getTotalDebits();
        $credits = $this->getTotalCredits();

        $typeEnum = $this->getTypeEnum();

        if ($typeEnum->increasesWithDebit()) {
            return $debits - $credits;
        }

        return $credits - $debits;
    }

    /**
     * Scope to get active accounts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get accounts by type.
     */
    public function scopeOfType($query, AccountType $type)
    {
        return $query->where('type', $type->value);
    }

    /**
     * Scope to get root accounts (no parent).
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'is_active' => 'boolean',
            'is_system' => 'boolean',
            'opening_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'level' => 'integer',
            'sort_order' => 'integer',
        ];
    }
}
