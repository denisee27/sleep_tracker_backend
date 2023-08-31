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
									   (1, NULL, 'Asset Category', 'category', '', '[\"create\",\"update\",\"delete\"]', 0, 1, '2023-08-28 16:50:19', '2023-08-28 16:54:25'),
									   (2, 1, 'Category', '', 'categories', '[\"create\",\"update\",\"delete\"]', 1, 1, '2023-08-28 16:54:11', '2023-08-28 16:54:11'),
									   (3, 1, 'Sub Category', '', 'sub-categories', '[\"create\",\"update\",\"delete\"]', 1, 1, '2023-08-28 16:55:15', '2023-08-28 16:55:15'),
									   (4, NULL, 'Vendor & Asset Purchase', 'shopping_cart', '', 'null', 1, 1, '2023-08-29 11:55:53', '2023-08-29 12:55:25'),
									   (5, 4, 'Vendor', '', 'suppliers', '[\"create\",\"update\",\"delete\"]', 1, 1, '2023-08-29 12:46:37', '2023-08-29 12:56:12'),
									   (7, 4, 'List PO SAP', '', 'po-sap', '[\"update\",\"delete\"]', 2, 1, '2023-08-29 12:48:28', '2023-08-29 14:45:07'),
									   (8, 4, 'Request New Asset', '', 'request-asset', '[\"create\",\"update\",\"delete\"]', 3, 1, '2023-08-29 12:53:00', '2023-08-29 12:54:00'),
									   (9, 4, 'Purchase Order', '', 'purchase-orders', '[\"create\",\"update\",\"delete\"]', 4, 1, '2023-08-29 12:54:27', '2023-08-29 12:57:45'),
									   (10, 4, 'Company', '', 'companies', '[\"create\",\"update\",\"delete\"]', 0, 1, '2023-08-29 12:56:05', '2023-08-29 12:56:05'),
									   (11, NULL, 'Settings', 'settings', '', 'null', 2, 1, '2023-08-29 12:59:59', '2023-08-29 13:00:06'),
									   (12, 11, 'Role', '', 'roles', '[\"create\",\"update\",\"delete\"]', 0, 1, '2023-08-29 13:00:44', '2023-08-29 13:00:44'),
									   (13, 11, 'User Account', '', 'users', '[\"create\",\"update\",\"delete\"]', 1, 1, '2023-08-29 13:15:59', '2023-08-29 13:15:59'),
									   (14, 11, 'Asset Controller', '', 'asset-controller', '[\"create\",\"update\",\"delete\"]', 3, 1, '2023-08-29 13:16:38', '2023-08-31 19:36:29'),
									   (15, 11, 'Set Area', '', 'areas', '[\"create\",\"update\",\"delete\"]', 2, 1, '2023-08-31 19:36:15', '2023-08-31 19:48:46');
		";
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        DB::table('navigations')->truncate();
        DB::unprepared($sql);
    }
}
