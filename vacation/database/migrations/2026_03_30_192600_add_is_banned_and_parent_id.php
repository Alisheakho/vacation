<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // إضافة حقل الحظر لجدول المستخدمين
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_banned')->default(false)->after('annual_balance');
        });

        // إضافة parent_id لجدول الفروع (فرع تابع لفرع أب)
        if (!Schema::hasColumn('branches', 'parent_id')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->foreignId('parent_id')
                    ->nullable()
                    ->after('name')
                    ->constrained('branches')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_banned');
        });

        if (Schema::hasColumn('branches', 'parent_id')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->dropForeign(['parent_id']);
                $table->dropColumn('parent_id');
            });
        }
    }
};
