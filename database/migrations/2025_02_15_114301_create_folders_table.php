<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folders', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('folders')->cascadeOnDelete()->cascadeOnUpdate();
            $table->enum('type', ['folder', 'file']);
            $table->index('parent_id');
            $table->string('name')->nullable();
            $table->string('path')->nullable();
            $table->index('path');
            $table->bigInteger('size')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('folders');
    }
};
