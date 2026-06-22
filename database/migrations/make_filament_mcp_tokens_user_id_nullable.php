<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('filament_mcp_tokens')) {
            return;
        }

        if (! Schema::hasColumn('filament_mcp_tokens', 'user_id')) {
            return;
        }

        $userId = collect(Schema::getColumns('filament_mcp_tokens'))
            ->firstWhere('name', 'user_id');

        if (($userId['nullable'] ?? true) === true) {
            return;
        }

        Schema::table('filament_mcp_tokens', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->change();
        });
    }
};
