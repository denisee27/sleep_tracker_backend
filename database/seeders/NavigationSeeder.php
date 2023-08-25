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
									   (1, NULL, 'Asset Category', 'category', '', 'null', 0, 1, '2023-08-22 11:18:22', '2023-08-22 11:22:41'),
									   (2, 1, 'Category', '', 'categories', '[\"create\",\"update\",\"delete\"]', 0, 1, '2023-08-22 11:19:04', '2023-08-22 11:19:04'),
									   (3, 1, 'Sub Category', '', 'sub-categories', '[\"create\",\"update\",\"delete\"]', 1, 1, '2023-08-22 11:19:27', '2023-08-22 11:19:27'),
									   (4, NULL, 'Asset Purchase', 'receipt', '', 'null', 1, 1, '2023-08-22 11:22:31', '2023-08-22 11:22:31'),
									   (5, 4, 'List PO/Non PO SAP', '', 'purchase-orders', '[\"create\",\"update\",\"delete\"]', 0, 1, '2023-08-22 11:23:37', '2023-08-22 13:08:18'),
									   (6, 4, 'Request New Asset', '', 'request-asset', '[\"create\",\"update\",\"delete\"]', 1, 1, '2023-08-22 11:24:00', '2023-08-22 11:24:00'),
									   (7, NULL, 'Settings', 'settings', '', 'null', 5, 1, '2023-08-22 14:50:16', '2023-08-22 14:50:16'),
									   (8, 7, 'Role', '', 'roles', '[\"create\",\"update\",\"delete\"]', 1, 1, '2023-08-22 14:50:38', '2023-08-24 13:10:59'),
									   (9, 7, 'User Account', '', 'users', '[\"create\",\"update\",\"delete\"]', 2, 1, '2023-08-22 14:50:58', '2023-08-24 13:11:06'),
									   (10, 7, 'Asset Controller', '', 'asset-controller', '[\"create\",\"update\",\"delete\"]', 3, 1, '2023-08-22 14:51:34', '2023-08-24 13:11:11'),
									   (11, 7, 'Company', '', 'companies', '[\"create\",\"update\",\"delete\"]', 0, 1, '2023-08-24 13:11:30', '2023-08-24 13:11:30');
		";
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        DB::table('navigations')->truncate();
        DB::unprepared($sql);
    }
}
