<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('filament_mcp_tool_calls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('filament_mcp_token_id')->nullable();
            $table->string('tool_name');
            $table->json('arguments')->nullable();
            $table->boolean('success')->default(true);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['user_id', 'created_at']);
            $table->index(['filament_mcp_token_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('filament_mcp_tool_calls');
    }
};
