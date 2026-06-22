<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('filament_mcp_tool_calls')) {
            return;
        }

        if (Schema::hasColumn('filament_mcp_tool_calls', 'filament_mcp_token_id')) {
            return;
        }

        Schema::table('filament_mcp_tool_calls', function (Blueprint $table): void {
            $table->unsignedBigInteger('filament_mcp_token_id')->nullable()->after('user_id');

            $table->index(['filament_mcp_token_id', 'created_at']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('filament_mcp_tool_calls')) {
            return;
        }

        if (! Schema::hasColumn('filament_mcp_tool_calls', 'filament_mcp_token_id')) {
            return;
        }

        Schema::table('filament_mcp_tool_calls', function (Blueprint $table): void {
            $table->dropIndex(['filament_mcp_token_id', 'created_at']);
            $table->dropColumn('filament_mcp_token_id');
        });
    }
};
