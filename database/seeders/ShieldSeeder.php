<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // App Settings (singleton - only view and update)
            'view_app::settings', 'view_any_app::settings', 'update_app::settings',
            // Printer Config
            'view_printer::config', 'view_any_printer::config', 'create_printer::config', 'update_printer::config', 'restore_printer::config', 'restore_any_printer::config', 'replicate_printer::config', 'reorder_printer::config', 'delete_printer::config', 'delete_any_printer::config', 'force_delete_printer::config', 'force_delete_any_printer::config',
            // Payment Gateway Config
            'view_payment::gateway::config', 'view_any_payment::gateway::config', 'create_payment::gateway::config', 'update_payment::gateway::config', 'restore_payment::gateway::config', 'restore_any_payment::gateway::config', 'replicate_payment::gateway::config', 'reorder_payment::gateway::config', 'delete_payment::gateway::config', 'delete_any_payment::gateway::config', 'force_delete_payment::gateway::config', 'force_delete_any_payment::gateway::config',
            // Product Stock
            'view_product::stock', 'view_any_product::stock', 'create_product::stock', 'update_product::stock', 'restore_product::stock', 'restore_any_product::stock', 'replicate_product::stock', 'reorder_product::stock', 'delete_product::stock', 'delete_any_product::stock', 'force_delete_product::stock', 'force_delete_any_product::stock',
            // Category
            'view_category', 'view_any_category', 'create_category', 'update_category', 'restore_category', 'restore_any_category', 'replicate_category', 'reorder_category', 'delete_category', 'delete_any_category', 'force_delete_category', 'force_delete_any_category',
            'view_debt::payment', 'view_any_debt::payment', 'create_debt::payment', 'update_debt::payment', 'restore_debt::payment', 'restore_any_debt::payment', 'replicate_debt::payment', 'reorder_debt::payment', 'delete_debt::payment', 'delete_any_debt::payment', 'force_delete_debt::payment', 'force_delete_any_debt::payment',
            'view_financial::record', 'view_any_financial::record', 'create_financial::record', 'update_financial::record', 'restore_financial::record', 'restore_any_financial::record', 'replicate_financial::record', 'reorder_financial::record', 'delete_financial::record', 'delete_any_financial::record', 'force_delete_financial::record', 'force_delete_any_financial::record',
            'view_product', 'view_any_product', 'create_product', 'update_product', 'restore_product', 'restore_any_product', 'replicate_product', 'reorder_product', 'delete_product', 'delete_any_product', 'force_delete_product', 'force_delete_any_product',
            'view_product::return', 'view_any_product::return', 'delete_product::return', 'delete_any_product::return',
            'view_purchase::order', 'view_any_purchase::order', 'create_purchase::order', 'update_purchase::order', 'restore_purchase::order', 'restore_any_purchase::order', 'replicate_purchase::order', 'reorder_purchase::order', 'delete_purchase::order', 'delete_any_purchase::order', 'force_delete_purchase::order', 'force_delete_any_purchase::order',
            'view_receipt::template', 'view_any_receipt::template', 'create_receipt::template', 'update_receipt::template', 'restore_receipt::template', 'restore_any_receipt::template', 'replicate_receipt::template', 'reorder_receipt::template', 'delete_receipt::template', 'delete_any_receipt::template', 'force_delete_receipt::template', 'force_delete_any_receipt::template',
            'view_role', 'view_any_role', 'create_role', 'update_role', 'delete_role', 'delete_any_role',
            'view_stock::adjustment', 'view_any_stock::adjustment', 'create_stock::adjustment', 'update_stock::adjustment', 'restore_stock::adjustment', 'restore_any_stock::adjustment', 'replicate_stock::adjustment', 'reorder_stock::adjustment', 'delete_stock::adjustment', 'delete_any_stock::adjustment', 'force_delete_stock::adjustment', 'force_delete_any_stock::adjustment',
            'view_stock::opname', 'view_any_stock::opname', 'create_stock::opname', 'update_stock::opname', 'restore_stock::opname', 'restore_any_stock::opname', 'replicate_stock::opname', 'reorder_stock::opname', 'delete_stock::opname', 'delete_any_stock::opname', 'force_delete_stock::opname', 'force_delete_any_stock::opname',
            'view_store', 'view_any_store', 'create_store', 'update_store', 'restore_store', 'restore_any_store', 'replicate_store', 'reorder_store', 'delete_store', 'delete_any_store', 'force_delete_store', 'force_delete_any_store',
            'view_store::credit', 'view_any_store::credit',
            'view_supplier', 'view_any_supplier', 'create_supplier', 'update_supplier', 'restore_supplier', 'restore_any_supplier', 'replicate_supplier', 'reorder_supplier', 'delete_supplier', 'delete_any_supplier', 'force_delete_supplier', 'force_delete_any_supplier',
            'view_supplier::debt', 'view_any_supplier::debt', 'create_supplier::debt', 'update_supplier::debt', 'restore_supplier::debt', 'restore_any_supplier::debt', 'replicate_supplier::debt', 'reorder_supplier::debt', 'delete_supplier::debt', 'delete_any_supplier::debt', 'force_delete_supplier::debt', 'force_delete_any_supplier::debt',
            'view_transaction', 'view_any_transaction', 'create_transaction', 'update_transaction', 'restore_transaction', 'restore_any_transaction', 'replicate_transaction', 'reorder_transaction', 'delete_transaction', 'delete_any_transaction', 'force_delete_transaction', 'force_delete_any_transaction',
            'view_user', 'view_any_user', 'create_user', 'update_user', 'restore_user', 'restore_any_user', 'replicate_user', 'reorder_user', 'delete_user', 'delete_any_user', 'force_delete_user', 'force_delete_any_user',
            'view_customer', 'view_any_customer', 'create_customer', 'update_customer', 'restore_customer', 'restore_any_customer', 'replicate_customer', 'reorder_customer', 'delete_customer', 'delete_any_customer', 'force_delete_customer', 'force_delete_any_customer',
            'view_discount', 'view_any_discount', 'create_discount', 'update_discount', 'restore_discount', 'restore_any_discount', 'replicate_discount', 'reorder_discount', 'delete_discount', 'delete_any_discount', 'force_delete_discount', 'force_delete_any_discount',
            'page_DebtReport', 'page_EndOfDayReport', 'page_ProfitLossReport', 'page_PurchaseReport', 'page_ReturnReport', 'page_ProcessReturn', 'page_SalesReport', 'page_StockCardReport',
            'widget_StatsOverview', 'widget_TransactionsChart', 'widget_FinancialRecordsChart', 'widget_LowStockAlertWidget', 'widget_SalesChartWidget', 'widget_PaymentMethodChartWidget', 'widget_TopProductsWidget', 'widget_ProfitChartWidget',
        ];

        $insertedPermissions = [];
        foreach ($permissions as $permission) {
            $insertedPermissions[] = Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web']
            )->id;
        }

        $superAdminRole = Role::firstOrCreate(
            ['name' => 'super_admin', 'guard_name' => 'web']
        );
        $superAdminRole->syncPermissions($insertedPermissions);

        $panelUserRole = Role::firstOrCreate(
            ['name' => 'panel_user', 'guard_name' => 'web']
        );

        $adminRole = Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'web']
        );
        $adminRole->syncPermissions($insertedPermissions);

        $managerPermissions = [
            'view_category', 'view_any_category', 'create_category', 'update_category',
            'view_product', 'view_any_product', 'create_product', 'update_product',
            'view_transaction', 'view_any_transaction', 'create_transaction',
            'view_purchase::order', 'view_any_purchase::order', 'create_purchase::order', 'update_purchase::order',
            'view_supplier', 'view_any_supplier', 'create_supplier', 'update_supplier',
            'view_stock::adjustment', 'view_any_stock::adjustment', 'create_stock::adjustment',
            'view_stock::opname', 'view_any_stock::opname', 'create_stock::opname',
            'view_financial::record', 'view_any_financial::record',
            'view_customer', 'view_any_customer', 'create_customer', 'update_customer',
            'view_discount', 'view_any_discount', 'create_discount', 'update_discount', 'delete_discount',
            'view_product::return', 'view_any_product::return', 'delete_product::return', 'delete_any_product::return',
            'view_store::credit', 'view_any_store::credit',
            'page_DebtReport', 'page_EndOfDayReport', 'page_ProfitLossReport', 'page_PurchaseReport', 'page_ReturnReport', 'page_ProcessReturn', 'page_SalesReport', 'page_StockCardReport',
            'widget_StatsOverview', 'widget_TransactionsChart', 'widget_FinancialRecordsChart', 'widget_LowStockAlertWidget', 'widget_SalesChartWidget', 'widget_PaymentMethodChartWidget', 'widget_TopProductsWidget', 'widget_ProfitChartWidget',
        ];
        $managerRole = Role::firstOrCreate(
            ['name' => 'manager', 'guard_name' => 'web']
        );
        $managerRole->syncPermissions($managerPermissions);

        $kasirPermissions = [
            'view_product', 'view_any_product',
            'view_transaction', 'view_any_transaction', 'create_transaction',
            'view_customer', 'view_any_customer',
            'view_discount', 'view_any_discount',
            'view_product::return', 'view_any_product::return',
            'page_EndOfDayReport', 'page_ProcessReturn',
            'widget_StatsOverview', 'widget_LowStockAlertWidget',
        ];
        $kasirRole = Role::firstOrCreate(
            ['name' => 'kasir', 'guard_name' => 'web']
        );
        $kasirRole->syncPermissions($kasirPermissions);
    }
}
