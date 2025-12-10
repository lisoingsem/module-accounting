<?php

declare(strict_types=1);

namespace Modules\Accounting\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Modules\Accounting\Enums\AccountType;
use Modules\Accounting\Models\Account;

final class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            // Assets (1000s)
            [
                'code' => '1000',
                'name' => 'Assets',
                'type' => AccountType::ASSET->value,
                'description' => 'Total Assets',
                'level' => 0,
                'is_system' => true,
            ],
            [
                'code' => '1100',
                'name' => 'Current Assets',
                'type' => AccountType::ASSET->value,
                'description' => 'Current Assets',
                'parent_code' => '1000',
                'level' => 1,
                'is_system' => true,
            ],
            [
                'code' => '1110',
                'name' => 'Cash',
                'type' => AccountType::ASSET->value,
                'description' => 'Cash and cash equivalents',
                'parent_code' => '1100',
                'level' => 2,
                'is_system' => true,
            ],
            [
                'code' => '1120',
                'name' => 'Accounts Receivable',
                'type' => AccountType::ASSET->value,
                'description' => 'Amounts owed by customers',
                'parent_code' => '1100',
                'level' => 2,
                'is_system' => true,
            ],
            [
                'code' => '1200',
                'name' => 'Fixed Assets',
                'type' => AccountType::ASSET->value,
                'description' => 'Fixed Assets',
                'parent_code' => '1000',
                'level' => 1,
                'is_system' => true,
            ],
            [
                'code' => '1210',
                'name' => 'Equipment',
                'type' => AccountType::ASSET->value,
                'description' => 'Office equipment and machinery',
                'parent_code' => '1200',
                'level' => 2,
                'is_system' => true,
            ],

            // Liabilities (2000s)
            [
                'code' => '2000',
                'name' => 'Liabilities',
                'type' => AccountType::LIABILITY->value,
                'description' => 'Total Liabilities',
                'level' => 0,
                'is_system' => true,
            ],
            [
                'code' => '2100',
                'name' => 'Current Liabilities',
                'type' => AccountType::LIABILITY->value,
                'description' => 'Current Liabilities',
                'parent_code' => '2000',
                'level' => 1,
                'is_system' => true,
            ],
            [
                'code' => '2110',
                'name' => 'Accounts Payable',
                'type' => AccountType::LIABILITY->value,
                'description' => 'Amounts owed to suppliers',
                'parent_code' => '2100',
                'level' => 2,
                'is_system' => true,
            ],
            [
                'code' => '2120',
                'name' => 'Accrued Expenses',
                'type' => AccountType::LIABILITY->value,
                'description' => 'Accrued expenses',
                'parent_code' => '2100',
                'level' => 2,
                'is_system' => true,
            ],

            // Equity (3000s)
            [
                'code' => '3000',
                'name' => 'Equity',
                'type' => AccountType::EQUITY->value,
                'description' => 'Total Equity',
                'level' => 0,
                'is_system' => true,
            ],
            [
                'code' => '3100',
                'name' => 'Capital',
                'type' => AccountType::EQUITY->value,
                'description' => 'Owner\'s capital',
                'parent_code' => '3000',
                'level' => 1,
                'is_system' => true,
            ],
            [
                'code' => '3200',
                'name' => 'Retained Earnings',
                'type' => AccountType::EQUITY->value,
                'description' => 'Retained earnings',
                'parent_code' => '3000',
                'level' => 1,
                'is_system' => true,
            ],

            // Revenue (4000s)
            [
                'code' => '4000',
                'name' => 'Revenue',
                'type' => AccountType::REVENUE->value,
                'description' => 'Total Revenue',
                'level' => 0,
                'is_system' => true,
            ],
            [
                'code' => '4100',
                'name' => 'Sales Revenue',
                'type' => AccountType::REVENUE->value,
                'description' => 'Revenue from sales',
                'parent_code' => '4000',
                'level' => 1,
                'is_system' => true,
            ],
            [
                'code' => '4200',
                'name' => 'Service Revenue',
                'type' => AccountType::REVENUE->value,
                'description' => 'Revenue from services',
                'parent_code' => '4000',
                'level' => 1,
                'is_system' => true,
            ],

            // Expenses (5000s)
            [
                'code' => '5000',
                'name' => 'Expenses',
                'type' => AccountType::EXPENSE->value,
                'description' => 'Total Expenses',
                'level' => 0,
                'is_system' => true,
            ],
            [
                'code' => '5100',
                'name' => 'Cost of Goods Sold',
                'type' => AccountType::EXPENSE->value,
                'description' => 'Cost of goods sold',
                'parent_code' => '5000',
                'level' => 1,
                'is_system' => true,
            ],
            [
                'code' => '5200',
                'name' => 'Operating Expenses',
                'type' => AccountType::EXPENSE->value,
                'description' => 'Operating expenses',
                'parent_code' => '5000',
                'level' => 1,
                'is_system' => true,
            ],
            [
                'code' => '5210',
                'name' => 'Salaries and Wages',
                'type' => AccountType::EXPENSE->value,
                'description' => 'Employee salaries and wages',
                'parent_code' => '5200',
                'level' => 2,
                'is_system' => true,
            ],
            [
                'code' => '5220',
                'name' => 'Rent Expense',
                'type' => AccountType::EXPENSE->value,
                'description' => 'Office rent',
                'parent_code' => '5200',
                'level' => 2,
                'is_system' => true,
            ],
            [
                'code' => '5230',
                'name' => 'Utilities',
                'type' => AccountType::EXPENSE->value,
                'description' => 'Utility expenses',
                'parent_code' => '5200',
                'level' => 2,
                'is_system' => true,
            ],
        ];

        foreach ($accounts as $accountData) {
            $parentId = null;
            if (isset($accountData['parent_code'])) {
                $parent = Account::query()->where('code', Arr::get($accountData, 'parent_code'))->first();
                $parentId = $parent?->id;
                unset($accountData['parent_code']);
            }

            Account::query()->updateOrCreate(['code' => Arr::get($accountData, 'code')], array_merge($accountData, [
                'parent_id' => $parentId,
                'is_active' => true,
                'opening_balance' => 0,
                'current_balance' => 0,
                'currency' => 'USD',
                'sort_order' => (int) mb_substr(Arr::get($accountData, 'code'), -1),
            ]));
        }
    }
}
