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
        Schema::create('status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('requests')->cascadeOnDelete(); // какая заявка
            $table->string('old_status')->nullable();                                          // прошлый статус (у первой записи пусто)
            $table->string('new_status');                                                      // новый статус
            $table->foreignId('changed_by')->nullable()->constrained('users');           // кто сменил
            $table->timestamp('created_at')->nullable();                                       // когда
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_history');
    }
};
