<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('export_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'products' o 'orders'
            $table->string('format'); // 'excel', 'pdf', 'csv'
            $table->integer('records_count'); // Cantidad de registros exportados
            $table->string('filename'); // Nombre del archivo generado
            $table->string('user_session')->nullable(); // ID de sesión del usuario
            $table->decimal('file_size_kb', 10, 2)->nullable(); // Tamaño del archivo en KB
            $table->integer('execution_time_ms')->nullable(); // Tiempo de ejecución en milisegundos
            $table->json('export_params')->nullable(); // Parámetros usados en la exportación
            $table->boolean('success')->default(true); // Si la exportación fue exitosa
            $table->text('error_message')->nullable(); // Mensaje de error si falló
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('export_logs');
    }
};