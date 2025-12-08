<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Models\AccountingPeriod;
use Modules\Accounting\Models\JournalEntry;

return new class() extends Migration
{
    public function up(): void
    {
        if ( ! Schema::hasTable((new JournalEntry())->getTable())) {
            Schema::create((new JournalEntry())->getTable(), function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('entry_number')->unique();
                $table->date('entry_date');
                $table->enum('type', ['manual', 'auto'])->default('manual');
                $table->enum('status', ['draft', 'posted', 'reversed'])->default('draft');
                $table->text('description');
                $table->text('reference')->nullable();
                $table->foreignId('period_id')->nullable()->constrained((new AccountingPeriod())->getTable())->nullOnDelete();
                $table->foreignId('created_by')->constrained((new User())->getTable());
                $table->foreignId('posted_by')->nullable()->constrained((new User())->getTable())->nullOnDelete();
                $table->dateTime('posted_at')->nullable();
                $table->string('source_type')->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('entry_number', 'accounting_journal_entries_entry_number_index');
                $table->index('entry_date', 'accounting_journal_entries_entry_date_index');
                $table->index('type', 'accounting_journal_entries_type_index');
                $table->index('status', 'accounting_journal_entries_status_index');
                $table->index('period_id', 'accounting_journal_entries_period_id_index');
                $table->index(['source_type', 'source_id'], 'accounting_journal_entries_source_index');
                $table->index(['entry_date', 'status'], 'accounting_journal_entries_date_status_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists((new JournalEntry())->getTable());
    }
};
