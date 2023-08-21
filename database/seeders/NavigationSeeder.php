<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NavigationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $sql = "
		INSERT INTO `navigations` (`id`, `parent_id`, `name`, `icon`, `link`, `action`, `position`, `status`, `created_at`, `updated_at`) VALUES
									   (1, NULL, 'Material', 'dns', '', 'null', 0, 1, '2023-05-02 06:07:37', '2023-05-02 06:07:37'),
									   (2, NULL, 'Warehouse', 'warehouse', '', 'null', 1, 1, '2023-05-02 06:10:59', '2023-05-02 06:10:59'),
									   (3, NULL, 'Vendor & Purchase Order', 'local_shipping', '', 'null', 2, 1, '2023-05-02 06:11:53', '2023-08-09 17:05:05'),
									   (5, NULL, 'Warehouse Activity', 'storefront', '', 'null', 4, 1, '2023-05-02 06:15:59', '2023-05-04 09:11:04'),
									   (6, NULL, 'Change Stock', 'inventory_2', '', 'null', 5, 1, '2023-05-02 06:16:50', '2023-05-02 06:17:02'),
									   (7, NULL, 'Report', 'summarize', '', 'null', 6, 1, '2023-05-02 06:17:36', '2023-05-02 06:17:36'),
									   (8, NULL, 'Settings', 'settings', '', 'null', 7, 1, '2023-05-02 06:17:57', '2023-05-02 06:17:57'),
									   (9, 1, 'Categories', '', 'categories', '[\"create\",\"update\",\"delete\"]', 0, 1, '2023-05-02 06:29:58', '2023-05-02 06:29:58'),
									   (10, 1, 'Material Items', '', 'materials', '[\"create\",\"update\",\"delete\"]', 1, 1, '2023-05-02 06:30:44', '2023-05-02 06:30:44'),
									   (11, 2, 'Main Warehouse', '', 'main-warehouses', '[\"create\",\"update\",\"delete\"]', 0, 1, '2023-05-02 10:45:48', '2023-05-02 10:45:48'),
									   (12, 2, 'Transit Warhouse', '', 'transit-warehouses', '[\"create\",\"update\",\"delete\"]', 1, 1, '2023-05-02 10:48:53', '2023-05-02 10:48:53'),
									   (13, 2, 'Lastmile Warehouse', '', 'lastmile-warehouses', '[\"create\",\"update\",\"delete\"]', 2, 1, '2023-05-02 10:49:13', '2023-05-02 10:52:36'),
									   (14, 3, 'Vendor', '', 'suppliers', '[\"create\",\"update\",\"delete\"]', 2, 1, '2023-05-02 10:53:04', '2023-08-09 17:06:27'),
									   (15, 3, 'List PO SAP', '', 'po-sap', '[\"update\",\"delete\"]', 2, 1, '2023-05-02 10:53:39', '2023-08-09 17:06:41'),
									   (16, 3, 'Purchase Order', '', 'purchase-orders', '[\"create\",\"update\",\"delete\"]', 4, 1, '2023-05-02 10:54:11', '2023-08-09 17:07:03'),
									   (17, 5, 'Supplier Inbound', '', 'supplier-inbound', '[\"create\",\"update\",\"delete\"]', 0, 1, '2023-05-02 10:54:46', '2023-05-04 09:12:11'),
									   (19, 5, 'Transfer Material', '', 'transfer-material', '[\"create\",\"update\",\"delete\"]', 1, 1, '2023-05-02 10:56:34', '2023-06-27 02:44:01'),
									   (20, 5, 'Material To Site', '', 'material-to-site', '[\"create\",\"update\",\"delete\"]', 2, 1, '2023-05-02 10:57:03', '2023-06-27 02:44:07'),
									   (21, 5, 'Transfer Project Code', '', 'transfer-project-code', '[\"create\",\"update\",\"delete\"]', 3, 1, '2023-05-02 10:57:34', '2023-07-11 07:00:08'),
									   (22, 6, 'Stock Opname', '', 'stock-opname', '[\"create\",\"update\",\"delete\"]', 1, 1, '2023-05-02 10:58:01', '2023-05-02 10:58:01'),
									   (23, 6, 'Material Disposal', '', 'material-disposal', '[\"create\",\"update\",\"delete\"]', 2, 1, '2023-05-02 10:58:26', '2023-05-02 10:58:26'),
									   (24, 8, 'Role', '', 'roles', '[\"create\",\"update\",\"delete\"]', 0, 1, '2023-05-02 10:59:20', '2023-05-02 10:59:20'),
									   (25, 8, 'Hierarchy', '', 'positions', '[\"create\",\"update\",\"delete\"]', 1, 1, '2023-05-02 10:59:36', '2023-05-09 06:27:35'),
									   (26, 8, 'User Account', '', 'users', '[\"update\"]', 2, 1, '2023-05-02 11:00:19', '2023-08-15 12:01:33'),
									   (27, 8, 'Approval', '', 'approval-settings', '[\"update\"]', 3, 1, '2023-05-02 11:00:41', '2023-05-09 06:27:44'),
									   (28, 3, 'Company', '', 'companies', '[\"create\",\"update\",\"delete\"]', 0, 1, '2023-05-02 11:01:16', '2023-08-09 17:07:14'),
									   (29, 3, 'Project Code', '', 'projects', '[\"create\",\"update\",\"delete\"]', 1, 1, '2023-05-02 11:01:35', '2023-08-09 17:06:11'),
									   (30, 7, 'Material Stock', '', 'report-material-stock', 'null', 0, 1, '2023-05-04 09:27:08', '2023-07-18 11:43:34'),
									   (31, 7, 'Stock Alert', '', 'report-stock-alert', 'null', 1, 1, '2023-05-04 09:27:29', '2023-07-18 11:43:57'),
									   (32, 6, 'Material Discrepancy', '', 'material-discrepancies', '[\"update\",\"delete\"]', 2, 1, '2023-06-27 02:43:17', '2023-07-11 07:00:33'),
									   (33, 6, 'Used Material Return', '', 'used-material-returns', '[\"create\",\"update\",\"delete\"]', 3, 1, '2023-06-27 02:44:45', '2023-07-11 07:00:56'),
									   (34, 7, 'Transaction', '', 'report-transaction', 'null', 2, 1, '2023-07-24 07:18:29', '2023-07-24 07:18:29'),
									   (35, 7, 'Site LongLat', '', 'report-site', 'null', 3, 1, '2023-08-02 06:48:53', '2023-08-02 06:49:13'),
									   (36, 8, 'Stock Alert Notification', '', 'stock-alert-notifications', '[\"create\",\"update\",\"delete\"]', 6, 1, '2023-08-07 15:42:12', '2023-08-07 16:29:55');
		";
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        DB::table('navigations')->truncate();
        DB::unprepared($sql);
    }
}
