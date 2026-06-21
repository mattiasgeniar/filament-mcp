<?php

namespace Mattiasgeniar\FilamentMcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Mattiasgeniar\FilamentMcp\Server\ToolFactory;

#[IsReadOnly]
class DescribeResourcesTool extends Tool
{
    public function name(): string
    {
        return 'describe_resources';
    }

    public function description(): string
    {
        return 'List the resources this MCP server exposes, with their operations, custom actions, and readable/writable fields.';
    }

    public function handle(Request $request): Response
    {
        return Response::text((string) json_encode([
            'resources' => app(ToolFactory::class)->describe(),
        ], JSON_PRETTY_PRINT));
    }
}
