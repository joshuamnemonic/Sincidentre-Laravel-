<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function isSqlite(): bool
    {
        return DB::getDriverName() === 'sqlite';
    }

    private function columnExists(string $table, string $column): bool
    {
        return Schema::hasColumn($table, $column);
    }

    private function foreignKeyExists(string $table, string $foreignKeyName): bool
    {
        if ($this->isSqlite()) {
            // SQLite does not expose MySQL information_schema metadata used below.
            return true;
        }

        $result = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = "FOREIGN KEY" LIMIT 1',
            [$table, $foreignKeyName]
        );

        return $result !== null;
    }

    private function dropForeignKeyIfExists(string $table, string $foreignKeyName): void
    {
        if ($this->isSqlite()) {
            return;
        }

        if ($this->foreignKeyExists($table, $foreignKeyName)) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$foreignKeyName}`");
        }
    }

    public function up(): void
    {
        if ($this->isSqlite()) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (!$this->columnExists('users', 'is_department_student_discipline_officer')) {
                $table->boolean('is_department_student_discipline_officer')->default(0)->after('password');
            }
        });

        if ($this->columnExists('users', 'is_discipline_officer')) {
            DB::statement('UPDATE users SET is_department_student_discipline_officer = COALESCE(is_discipline_officer, 0)');
        } elseif ($this->columnExists('users', 'is_admin')) {
            DB::statement('UPDATE users SET is_department_student_discipline_officer = COALESCE(is_admin, 0)');
        }

        Schema::table('users', function (Blueprint $table) {
            if ($this->columnExists('users', 'is_discipline_officer')) {
                $table->dropColumn('is_discipline_officer');
            }

            if ($this->columnExists('users', 'is_admin')) {
                $table->dropColumn('is_admin');
            }
        });

        Schema::table('report_responses', function (Blueprint $table) {
            if (!$this->columnExists('report_responses', 'dsdo_id')) {
                $table->unsignedBigInteger('dsdo_id')->nullable()->after('report_id');
            }
        });

        if ($this->columnExists('report_responses', 'department_student_discipline_officer_id')) {
            DB::statement('UPDATE report_responses SET dsdo_id = department_student_discipline_officer_id WHERE dsdo_id IS NULL');

            $this->dropForeignKeyIfExists('report_responses', 'report_responses_department_student_discipline_officer_id_foreign');

            Schema::table('report_responses', function (Blueprint $table) {
                $table->dropColumn('department_student_discipline_officer_id');
            });
        }

        if ($this->columnExists('report_responses', 'admin_id')) {
            DB::statement('UPDATE report_responses SET dsdo_id = admin_id WHERE dsdo_id IS NULL');

            $this->dropForeignKeyIfExists('report_responses', 'report_responses_admin_id_foreign');
            $this->dropForeignKeyIfExists('report_responses', 'rr_admin_id_fk');

            Schema::table('report_responses', function (Blueprint $table) {
                $table->dropColumn('admin_id');
            });
        }

        if (!$this->foreignKeyExists('report_responses', 'rr_dsdo_id_fk')) {
            DB::statement('ALTER TABLE `report_responses` ADD CONSTRAINT `rr_dsdo_id_fk` FOREIGN KEY (`dsdo_id`) REFERENCES `users`(`id`) ON DELETE SET NULL');
        }

        Schema::table('activity_logs', function (Blueprint $table) {
            if (!$this->columnExists('activity_logs', 'dsdo_id')) {
                $table->unsignedBigInteger('dsdo_id')->nullable()->after('id');
            }
        });

        if ($this->columnExists('activity_logs', 'department_student_discipline_officer_id')) {
            DB::statement('UPDATE activity_logs SET dsdo_id = department_student_discipline_officer_id WHERE dsdo_id IS NULL');

            $this->dropForeignKeyIfExists('activity_logs', 'activity_logs_department_student_discipline_officer_id_foreign');

            Schema::table('activity_logs', function (Blueprint $table) {
                $table->dropColumn('department_student_discipline_officer_id');
            });
        }

        if ($this->columnExists('activity_logs', 'admin_id')) {
            DB::statement('UPDATE activity_logs SET dsdo_id = admin_id WHERE dsdo_id IS NULL');

            $this->dropForeignKeyIfExists('activity_logs', 'activity_logs_admin_id_foreign');
            $this->dropForeignKeyIfExists('activity_logs', 'al_admin_id_fk');

            Schema::table('activity_logs', function (Blueprint $table) {
                $table->dropColumn('admin_id');
            });
        }

        if (!$this->foreignKeyExists('activity_logs', 'al_dsdo_id_fk')) {
            DB::statement('ALTER TABLE `activity_logs` ADD CONSTRAINT `al_dsdo_id_fk` FOREIGN KEY (`dsdo_id`) REFERENCES `users`(`id`) ON DELETE SET NULL');
        }
    }

    public function down(): void
    {
        if ($this->isSqlite()) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (!$this->columnExists('users', 'is_admin')) {
                $table->boolean('is_admin')->default(0)->after('password');
            }
        });

        if ($this->columnExists('users', 'is_department_student_discipline_officer')) {
            DB::statement('UPDATE users SET is_admin = COALESCE(is_department_student_discipline_officer, 0)');

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_department_student_discipline_officer');
            });
        }

        Schema::table('report_responses', function (Blueprint $table) {
            if (!$this->columnExists('report_responses', 'admin_id')) {
                $table->unsignedBigInteger('admin_id')->nullable()->after('report_id');
            }
        });

        if ($this->columnExists('report_responses', 'dsdo_id')) {
            DB::statement('UPDATE report_responses SET admin_id = dsdo_id WHERE admin_id IS NULL');

            $this->dropForeignKeyIfExists('report_responses', 'rr_dsdo_id_fk');
            $this->dropForeignKeyIfExists('report_responses', 'report_responses_dsdo_id_foreign');

            Schema::table('report_responses', function (Blueprint $table) {
                $table->dropColumn('dsdo_id');
            });
        }

        if (!$this->foreignKeyExists('report_responses', 'rr_admin_id_fk')) {
            DB::statement('ALTER TABLE `report_responses` ADD CONSTRAINT `rr_admin_id_fk` FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE SET NULL');
        }

        Schema::table('activity_logs', function (Blueprint $table) {
            if (!$this->columnExists('activity_logs', 'admin_id')) {
                $table->unsignedBigInteger('admin_id')->nullable()->after('id');
            }
        });

        if ($this->columnExists('activity_logs', 'dsdo_id')) {
            DB::statement('UPDATE activity_logs SET admin_id = dsdo_id WHERE admin_id IS NULL');

            $this->dropForeignKeyIfExists('activity_logs', 'al_dsdo_id_fk');
            $this->dropForeignKeyIfExists('activity_logs', 'activity_logs_dsdo_id_foreign');

            Schema::table('activity_logs', function (Blueprint $table) {
                $table->dropColumn('dsdo_id');
            });
        }

        if (!$this->foreignKeyExists('activity_logs', 'al_admin_id_fk')) {
            DB::statement('ALTER TABLE `activity_logs` ADD CONSTRAINT `al_admin_id_fk` FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE SET NULL');
        }
    }
};
