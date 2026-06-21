<?php

namespace Mattiasgeniar\FilamentMcp\Server;

use Laravel\Mcp\Enums\ProtocolVersion;
use Laravel\Mcp\Schema\Implementation;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\ServerContext;

class McpServer extends Server
{
    public function createContext(): ServerContext
    {
        return new ServerContext(
            supportedProtocolVersions: $this->supportedProtocolVersion ?: ProtocolVersion::supported(),
            serverCapabilities: $this->capabilities,
            implementation: new Implementation(
                name: (string) config('filament-mcp.server.name', 'Filament MCP'),
                version: '1.0.0',
                icons: $this->resolvedIcons(),
            ),
            instructions: (string) config('filament-mcp.server.instructions', ''),
            maxPaginationLength: $this->maxPaginationLength,
            defaultPaginationLength: $this->defaultPaginationLength,
            tools: app(ToolFactory::class)->make(),
            resources: $this->resources,
            prompts: $this->prompts,
        );
    }
}
