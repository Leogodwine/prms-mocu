<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Mandatory RBAC roles and permissions for production bootstrap.
     */
    public function run(): void
    {
        $roles = [
            ['role_name' => 'admin', 'description' => 'System Administrator'],
            ['role_name' => 'coordinator', 'description' => 'Project Coordinator'],
            ['role_name' => 'hod', 'description' => 'Head of Department'],
            ['role_name' => 'supervisor', 'description' => 'Academic Supervisor'],
            ['role_name' => 'student', 'description' => 'University Student'],
        ];

        foreach ($roles as $role) {
            \App\Models\Role::updateOrCreate(
                ['role_name' => $role['role_name']],
                $role
            );
        }

        $permissions = [
            ['permission_name' => 'manage_users', 'module' => 'users'],
            ['permission_name' => 'view_reports', 'module' => 'reports'],
            ['permission_name' => 'review_submissions', 'module' => 'projects'],
            ['permission_name' => 'submit_project', 'module' => 'projects'],
        ];

        foreach ($permissions as $permission) {
            \App\Models\Permission::updateOrCreate(
                ['permission_name' => $permission['permission_name']],
                $permission
            );
        }
    }
}
