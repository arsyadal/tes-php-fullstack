<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menjalankan migration untuk membuat tabel my_client.
     */
    public function up(): void
    {
        Schema::create('my_client', function (Blueprint $table) {
            $table->id();
            $table->string('name', 250);
            $table->string('slug', 100)->unique();
            $table->char('is_project', 32)->default('0');
            $table->char('self_capture', 1)->default('1');
            $table->char('client_prefix', 4);
            $table->string('client_logo', 255)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    /**
     * Rollback migration - drop tabel my_client.
     */
    public function down(): void
    {
        Schema::dropIfExists('my_client');
    }
};
