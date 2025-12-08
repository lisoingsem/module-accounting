<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Models\Account;
use Modules\Accounting\Models\JournalEntry;
use Modules\Accounting\Models\JournalEntryLine;

return new class() extends Migration
{
    public function up(): void
    {
        if ( ! Schema::hasTable((new JournalEntryLine())->getTable())) {
            Schema::create((new JournalEntryLine())->getTable(), function (Blueprint $table): void {
                $table->id();
                $table->foreignId('journal_entry_id')->constrained((new JournalEntry())->getTable())->cascadeOnDelete();
                $table->foreignId('account_id')->constrained((new Account())->getTable());
                $table->enum('type', ['debit', 'credit']);
                $table->decimal('amount', 15, 2);
                $table->text('description')->nullable();
                $table->text('reference')->nullable();
                $table->integer('line_number')->default(0);
                $table->timestamps();

                $table->index('journal_entry_id', 'accounting_journal_entry_lines_journal_entry_id_index');
                $table->index('account_id', 'accounting_journal_entry_lines_account_id_index');
                $table->index('type', 'accounting_journal_entry_lines_type_index');
                $table->index(['journal_entry_id', 'type'], 'accounting_journal_entry_lines_entry_type_index');
                $table->index(['account_id', 'type'], 'accounting_journal_entry_lines_account_type_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists((new JournalEntryLine())->getTable());
    }
};
