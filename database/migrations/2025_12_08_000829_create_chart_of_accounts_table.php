<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Models\Account;

return new class() extends Migration
{
    public function up(): void
    {
        if ( ! Schema::hasTable((new Account())->getTable())) {
            Schema::create((new Account())->getTable(), function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('code', 50)->unique();
                $table->string('name');
                $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
                $table->text('description')->nullable();
                $table->foreignId('parent_id')->nullable()->constrained((new Account())->getTable())->nullOnDelete();
                $table->integer('level')->default(0);
                $table->boolean('is_active')->default(true);
                $table->boolean('is_system')->default(false);
                $table->decimal('opening_balance', 15, 2)->default(0);
                $table->decimal('current_balance', 15, 2)->default(0);
                $table->string('currency', 3)->default('USD');
                $table->integer('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();

                $table->index('code', 'accounting_accounts_code_index');
                $table->index('type', 'accounting_accounts_type_index');
                $table->index('parent_id', 'accounting_accounts_parent_id_index');
                $table->index(['is_active', 'type'], 'accounting_accounts_active_type_index');
                $table->index('sort_order', 'accounting_accounts_sort_order_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists((new Account())->getTable());
    }
};
