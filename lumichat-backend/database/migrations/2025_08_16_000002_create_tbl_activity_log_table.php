<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tbl_activity_log', function (Blueprint $table) {
            $table->id();
            $table->string('event', 80)->index();                // e.g. user.registered
            $table->text('description')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();  // users.id
            $table->string('subject_type', 120)->nullable();     // App\Models\ChatSession, ...
            $table->unsignedBigInteger('subject_id')->nullable();// id of subject
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['subject_type','subject_id']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_activity_log');
    }
};
