<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mattiasgeniar\FilamentMcp\Models\FilamentMcpToken;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('filament_mcp_tokens')) {
            return;
        }

        if (! Schema::hasColumn('filament_mcp_tokens', 'tokenable_type')) {
            Schema::table('filament_mcp_tokens', function (Blueprint $table): void {
                $table->nullableMorphs('tokenable');
            });
        }

        if (! Schema::hasColumn('filament_mcp_tokens', 'user_id')) {
            return;
        }

        $userModel = FilamentMcpToken::userModel();

        DB::table('filament_mcp_tokens')
            ->whereNull('tokenable_type')
            ->orderBy('id')
            ->select(['id', 'user_id'])
            ->chunkById(100, function ($tokens) use ($userModel): void {
                foreach ($tokens as $token) {
                    DB::table('filament_mcp_tokens')
                        ->where('id', $token->id)
                        ->update([
                            'tokenable_type' => $userModel,
                            'tokenable_id' => $token->user_id,
                        ]);
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('filament_mcp_tokens')) {
            return;
        }

        if (! Schema::hasColumn('filament_mcp_tokens', 'tokenable_type')) {
            return;
        }

        Schema::table('filament_mcp_tokens', function (Blueprint $table): void {
            $table->dropMorphs('tokenable');
        });
    }
};
