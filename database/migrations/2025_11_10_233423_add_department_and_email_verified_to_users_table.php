<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        // No-op: users table is created by 0001_01_01_000000_create_users_table.php.
    }

    public function down(): void
    {
        // No-op to avoid dropping base users table during rollback of this legacy migration.
    }
};

