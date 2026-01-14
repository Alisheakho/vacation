<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete();

            $table->enum('leave_type', [
                'annual',
                'sick',
                'emergency',
                'unpaid',
                'occasion',
                'official',
            ]);

            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('days');

            $table->enum('status', [
                'under_review',
                'approved',
                'rejected',
            ])->default('under_review');

            $table->boolean('escalated')->default(false);
$table->text('admin_notes')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
