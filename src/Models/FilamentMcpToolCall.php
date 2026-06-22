<?php

namespace Mattiasgeniar\FilamentMcp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property int|null $filament_mcp_token_id
 * @property string $tool_name
 * @property array<string, mixed>|null $arguments
 * @property bool $success
 * @property int|null $duration_ms
 * @property Carbon|null $created_at
 */
class FilamentMcpToolCall extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'filament_mcp_token_id',
        'tool_name',
        'arguments',
        'success',
        'duration_ms',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'arguments' => 'array',
            'success' => 'boolean',
            'duration_ms' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Model, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(FilamentMcpToken::userModel(), 'user_id');
    }
}
