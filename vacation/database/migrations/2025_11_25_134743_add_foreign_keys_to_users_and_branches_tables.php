<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users_and_branches_tables', function (Blueprint $table) {
            //

        });
        Schema::table('users', function (Blueprint $table) {
    $table->foreign('branch_id')
        ->references('id')->on('branches')
        ->nullOnDelete();
});

Schema::table('branches', function (Blueprint $table) {
    $table->foreign('manager_id')
        ->references('id')->on('users')
        ->nullOnDelete();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users_and_branches_tables', function (Blueprint $table) {
            //
        });
          Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
        });
    }
};
