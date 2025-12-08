<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Models\AccountingPeriod;

return new class() extends Migration
{
    public function up(): void
    {
        if ( ! Schema::hasTable((new AccountingPeriod())->getTable())) {
            Schema::create((new AccountingPeriod())->getTable(), function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('name');
                $table->date('start_date');
                $table->date('end_date');
                $table->boolean('is_closed')->default(false);
                $table->dateTime('closed_at')->nullable();
                $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('start_date', 'accounting_periods_start_date_index');
                $table->index('end_date', 'accounting_periods_end_date_index');
                $table->index('is_closed', 'accounting_periods_is_closed_index');
                $table->index(['start_date', 'end_date'], 'accounting_periods_date_range_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists((new AccountingPeriod())->getTable());
    }
};
