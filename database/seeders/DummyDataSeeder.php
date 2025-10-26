<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Suppress foreign key checks to avoid errors during seeding.
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Clear existing data from the tables to start fresh.
        DB::table('users')->truncate();
        DB::table('user_employees')->truncate();
        DB::table('user_interns')->truncate();
        DB::table('tasks')->truncate();
        DB::table('task_user')->truncate();
        DB::table('accomplishments')->truncate();
        DB::table('accomplishment_task')->truncate();
        DB::table('projects')->truncate();
        DB::table('department_project')->truncate();
        DB::table('leaves')->truncate();

        // Re-enable foreign key checks.
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Create a super admin user.
        DB::table('users')->insert([
            'id' => 1,
            'user_type_id' => 1,
            'status_id' => 10,
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password'),
        ]);

        // Define department IDs for easy reference.
        $admin_dept_id = 1;
        $creative_dept_id = 2;
        $developer_dept_id = 3;
        $technical_dept_id = 4;
        $it_support_dept_id = 5;
        $architecture_dept_id = 6;

        // Initialize user ID counter.
        $user_id = 2;

        // --- Create Employee Users ---
        $employee_users = [];

        // Create Developer employees.
        for ($i = 0; $i < 5; $i++) {
            $hierarchy = ($i < 2) ? 'Leader' : 'Member'; // First 2 are Leaders
            DB::table('users')->insert([
                'id' => $user_id,
                'user_type_id' => 2,
                'status_id' => 10,
                'name' => 'Dev Employee ' . ($i + 1),
                'email' => 'devemployee' . ($i + 1) . '@example.com',
                'password' => Hash::make('password'),
            ]);
            DB::table('user_employees')->insert([
                'user_id' => $user_id,
                'department_id' => $developer_dept_id,
                'hierarchy' => $hierarchy,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $employee_users[$developer_dept_id][] = $user_id;
            $user_id++;
        }

        // Create Admin employees.
        for ($i = 0; $i < 5; $i++) {
            $hierarchy = ($i < 2) ? 'Leader' : 'Member'; // First 2 are Leaders
            DB::table('users')->insert([
                'id' => $user_id,
                'user_type_id' => 2,
                'status_id' => 10,
                'name' => 'Admin Employee ' . ($i + 1),
                'email' => 'adminemployee' . ($i + 1) . '@example.com',
                'password' => Hash::make('password'),
            ]);
            DB::table('user_employees')->insert([
                'user_id' => $user_id,
                'department_id' => $admin_dept_id,
                'hierarchy' => $hierarchy,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $employee_users[$admin_dept_id][] = $user_id;
            $user_id++;
        }

        // --- Create Intern Users ---
        $intern_users = [];

        // Create Developer interns.
        for ($i = 0; $i < 5; $i++) {
            DB::table('users')->insert([
                'id' => $user_id,
                'user_type_id' => 3,
                'status_id' => 13,
                'name' => 'Dev Intern ' . ($i + 1),
                'email' => 'devintern' . ($i + 1) . '@example.com',
                'password' => Hash::make('password'),
            ]);
            DB::table('user_interns')->insert([
                'user_id' => $user_id,
                'department_id' => $developer_dept_id,
                'school' => 'Tech University',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $intern_users[$developer_dept_id][] = $user_id;
            $user_id++;
        }

        // Create Admin interns.
        for ($i = 0; $i < 5; $i++) {
            DB::table('users')->insert([
                'id' => $user_id,
                'user_type_id' => 3,
                'status_id' => 13,
                'name' => 'Admin Intern ' . ($i + 1),
                'email' => 'adminintern' . ($i + 1) . '@example.com',
                'password' => Hash::make('password'),
            ]);
            DB::table('user_interns')->insert([
                'user_id' => $user_id,
                'department_id' => $admin_dept_id,
                'school' => 'Business College',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $intern_users[$admin_dept_id][] = $user_id;
            $user_id++;
        }

        // --- Create Projects ---
        $project1_id = DB::table('projects')->insertGetId([
            'title' => 'Project Alpha (All Departments)',
            'description' => 'A major company-wide initiative involving all teams.',
            'client' => 'Internal',
            'deadline' => Carbon::now()->addMonths(6),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $project2_id = DB::table('projects')->insertGetId([
            'title' => 'Project Beta (Admin & Dev)',
            'description' => 'A new software tool for administrative and development workflows.',
            'client' => 'Ops Team',
            'deadline' => Carbon::now()->addMonths(3),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $project3_id = DB::table('projects')->insertGetId([
            'title' => 'Project Gamma (Dev Only)',
            'description' => 'A highly technical backend refactor.',
            'client' => 'Tech Lead',
            'deadline' => Carbon::now()->addMonths(2),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Link Project 1 to all departments
        $all_department_ids = [$admin_dept_id, $creative_dept_id, $developer_dept_id, $technical_dept_id, $it_support_dept_id, $architecture_dept_id];
        foreach ($all_department_ids as $dept_id) {
            DB::table('department_project')->insert([
                'project_id' => $project1_id,
                'department_id' => $dept_id,
            ]);
        }

        // Link Project 2 to Admin and Developer
        DB::table('department_project')->insert([
            ['project_id' => $project2_id, 'department_id' => $admin_dept_id],
            ['project_id' => $project2_id, 'department_id' => $developer_dept_id],
        ]);

        // Link Project 3 to Developer
        DB::table('department_project')->insert([
            'project_id' => $project3_id,
            'department_id' => $developer_dept_id,
        ]);

        // --- Create Tasks and Assign Users ---
        $task_id_counter = 1;
        $employee_tasks = [];
        $intern_tasks = [];
        $priorities = ['low', 'medium', 'high'];

        // Helper function to create tasks and assign them.
        $create_and_assign_tasks = function ($user_type_id, $department_id, $users, &$tasks_array) use (&$task_id_counter, $priorities) {
            for ($i = 1; $i <= 5; $i++) {
                $task_id = $task_id_counter++;
                DB::table('tasks')->insert([
                    'id' => $task_id,
                    'department_id' => $department_id,
                    'user_type_id' => $user_type_id,
                    'status_id' => 1, // 'in progress'
                    'title' => "Sample Task {$task_id} for " . ($user_type_id == 2 ? 'Employees' : 'Interns'),
                    'description' => "This is a sample task description.",
                    'deadline' => Carbon::now()->addWeeks(2),
                    'collateral' => 'Sample Collateral',
                    'priority' => $priorities[array_rand($priorities)],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $tasks_array[$department_id][] = $task_id;

                // Assign users incrementally to the task.
                for ($j = 0; $j < $i && $j < count($users); $j++) {
                    DB::table('task_user')->insert([
                        'task_id' => $task_id,
                        'user_id' => $users[$j],
                    ]);
                }
            }
        };

        // Create tasks for employees.
        if (!empty($employee_users[$developer_dept_id])) {
            $create_and_assign_tasks(2, $developer_dept_id, $employee_users[$developer_dept_id], $employee_tasks);
        }
        if (!empty($employee_users[$admin_dept_id])) {
            $create_and_assign_tasks(2, $admin_dept_id, $employee_users[$admin_dept_id], $employee_tasks);
        }

        // Create tasks for interns.
        if (!empty($intern_users[$developer_dept_id])) {
            $create_and_assign_tasks(3, $developer_dept_id, $intern_users[$developer_dept_id], $intern_tasks);
        }
        if (!empty($intern_users[$admin_dept_id])) {
            $create_and_assign_tasks(3, $admin_dept_id, $intern_users[$admin_dept_id], $intern_tasks);
        }

        // --- Create Accomplishments ---
        $accomplishment_id = 1;

        // Helper function to create accomplishments for specific tasks.
        $create_accomplishments_for_task = function ($task_id, $users, $count) use (&$accomplishment_id) {
            for ($i = 0; $i < $count && $i < count($users); $i++) {
                DB::table('accomplishments')->insert([
                    'id' => $accomplishment_id,
                    'user_id' => $users[$i],
                    'title' => 'Finished my part of the task',
                    'description' => 'Successfully completed the assigned portion of the task.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('accomplishment_task')->insert([
                    'accomplishment_id' => $accomplishment_id,
                    'task_id' => $task_id,
                ]);
                $accomplishment_id++;
            }
        };

        // Create accomplishments for employee tasks.
        // For Developer Employees
        if (!empty($employee_tasks[$developer_dept_id]) && count($employee_tasks[$developer_dept_id]) >= 5) {
            $create_accomplishments_for_task($employee_tasks[$developer_dept_id][2], array_slice($employee_users[$developer_dept_id], 0, 3), 3); // Task 3, first 3 users
            $create_accomplishments_for_task($employee_tasks[$developer_dept_id][4], array_slice($employee_users[$developer_dept_id], 3, 2), 2); // Task 5, next 2 users
        }

        // For Admin Employees
        if (!empty($employee_tasks[$admin_dept_id]) && count($employee_tasks[$admin_dept_id]) >= 5) {
            $create_accomplishments_for_task($employee_tasks[$admin_dept_id][2], array_slice($employee_users[$admin_dept_id], 0, 3), 3); // Task 8, first 3 users
            $create_accomplishments_for_task($employee_tasks[$admin_dept_id][4], array_slice($employee_users[$admin_dept_id], 3, 2), 2); // Task 10, next 2 users
        }

        // Create accomplishments for intern tasks.
        // For Developer Interns
        if (!empty($intern_tasks[$developer_dept_id]) && count($intern_tasks[$developer_dept_id]) >= 5) {
            $create_accomplishments_for_task($intern_tasks[$developer_dept_id][2], array_slice($intern_users[$developer_dept_id], 0, 3), 3); // Task 13, first 3 users
            $create_accomplishments_for_task($intern_tasks[$developer_dept_id][4], array_slice($intern_users[$developer_dept_id], 3, 2), 2); // Task 15, next 2 users
        }

        // For Admin Interns
        if (!empty($intern_tasks[$admin_dept_id]) && count($intern_tasks[$admin_dept_id]) >= 5) {
            $create_accomplishments_for_task($intern_tasks[$admin_dept_id][2], array_slice($intern_users[$admin_dept_id], 0, 3), 3); // Task 18, first 3 users
            $create_accomplishments_for_task($intern_tasks[$admin_dept_id][4], array_slice($intern_users[$admin_dept_id], 3, 2), 2); // Task 20, next 2 users
        }

        // --- Create Leaves ---
        $getRandom = fn($ids) => collect($ids)->random(min(3, count($ids)));
        $users_for_leaves = $getRandom($employee_users[$admin_dept_id] ?? [])
            ->merge($getRandom($employee_users[$developer_dept_id] ?? []))
            ->all();

        // Leave Type IDs
        $leave_type_regular = 1;
        $leave_type_special = 2;

        // Leave Category IDs
        $cat_vacation = 1;
        $cat_sick = 2;
        $cat_paternity = 5;
        $cat_solo_parent = 6;

        if (!empty($users_for_leaves)) {
            foreach ($users_for_leaves as $user_leave_id) {
                // 3 Regular Leaves
                DB::table('leaves')->insert([
                    [
                        'user_id' => $user_leave_id,
                        'leave_type_id' => $leave_type_regular,
                        'leave_category_id' => $cat_vacation,
                        'status_id' => 5,
                        'reason' => 'Annual vacation.',
                        'request_date' => Carbon::now()->subWeeks(2),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'user_id' => $user_leave_id,
                        'leave_type_id' => $leave_type_regular,
                        'leave_category_id' => $cat_sick,
                        'status_id' => 5,
                        'reason' => 'Flu.',
                        'request_date' => Carbon::now()->subDays(5),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ]);

                // 3 Special Leaves
                DB::table('leaves')->insert([
                    [
                        'user_id' => $user_leave_id,
                        'leave_type_id' => $leave_type_special,
                        'leave_category_id' => $cat_paternity,
                        'status_id' => 5,
                        'reason' => 'Paternity leave.',
                        'request_date' => Carbon::now()->subMonth(1),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'user_id' => $user_leave_id,
                        'leave_type_id' => $leave_type_special,
                        'leave_category_id' => $cat_solo_parent,
                        'status_id' => 5,
                        'reason' => 'School event for child.',
                        'request_date' => Carbon::now()->addDays(3),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ]);
            }
        }
    }
}
