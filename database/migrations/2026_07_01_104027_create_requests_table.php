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
        Schema::create('requests', function (Blueprint $table) {
            $table->id();                                                                                 // request_id, автоинкремент
            $table->string('source');                                                             // bot / manual / call / whatsapp / avito
            $table->string('client_name')->nullable();                                            // имя — желательно, не обязательно
            $table->string('phone');                                                              // телефон — обязателен (шов к clients v0.2)
            $table->string('car_info')->nullable();                                               // марка/модель/год свободным текстом
            $table->text('problem');                                                              // описание проблемы — обязательно
            $table->string('urgency')->nullable();                                                // срочность
            $table->json('files')->nullable();                                                    // массив ссылок на фото
            $table->string('status')->default('new');                                       // статус, по умолчанию «новая»
            $table->foreignId('responsible_id')->nullable()->constrained('users');          // ответственный
            $table->text('comment')->nullable();                                                  // внутренний комментарий
            $table->timestamp('next_contact_at')->nullable();                                     // дата повторного касания
            $table->string('request_type')->default('client');                              // шов к supplier v0.3
            $table->timestamps();                                                                         // created_at + updated_at автоматом
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
