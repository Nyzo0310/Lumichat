<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tbl_appointment', function (Blueprint $table) {
            $table->id();
            // FK to users (no prefix in your DB)
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('counselor_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('scheduled_at')->index();
            $table->string('status')->default('pending'); // pending|confirmed|cancelled|completed
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_appointment');
    }
};
