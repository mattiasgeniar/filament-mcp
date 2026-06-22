<x-filament-panels::page>
    {{ $this->table }}

    @if ($this->showSetupGuide())
        @php
            $endpoint = $this->mcpEndpointUrl();
            $key = $this->mcpServerKey();

            $claudeJson = <<<JSON
            {
              "mcpServers": {
                "{$key}": {
                  "type": "http",
                  "url": "{$endpoint}",
                  "headers": { "Authorization": "Bearer fmcp_..." }
                }
              }
            }
            JSON;

            $claudeCli = "claude mcp add --transport http {$key} {$endpoint} \\\n  --header \"Authorization: Bearer fmcp_...\"";

            $cursorJson = <<<JSON
            {
              "mcpServers": {
                "{$key}": {
                  "url": "{$endpoint}",
                  "headers": { "Authorization": "Bearer fmcp_..." }
                }
              }
            }
            JSON;

            $vscodeJson = <<<JSON
            {
              "servers": {
                "{$key}": {
                  "type": "http",
                  "url": "{$endpoint}",
                  "headers": { "Authorization": "Bearer fmcp_..." }
                }
              }
            }
            JSON;
        @endphp

        <x-filament::section
            icon="heroicon-o-bolt"
            icon-color="primary"
        >
            <x-slot name="heading">Connect a client</x-slot>

            <x-slot name="description">
                Generate a token above, then drop it into your AI agent's MCP config.
                The endpoint below is already filled in for this app; replace
                <code>fmcp_...</code> with the token you copied.
            </x-slot>

            <div x-data="{ tab: 'claude' }" class="flex flex-col gap-4">
                <x-filament::tabs>
                    <x-filament::tabs.item
                        :alpine-active="'tab === \'claude\''"
                        x-on:click="tab = 'claude'"
                    >
                        Claude Code
                    </x-filament::tabs.item>

                    <x-filament::tabs.item
                        :alpine-active="'tab === \'cursor\''"
                        x-on:click="tab = 'cursor'"
                    >
                        Cursor
                    </x-filament::tabs.item>

                    <x-filament::tabs.item
                        :alpine-active="'tab === \'vscode\''"
                        x-on:click="tab = 'vscode'"
                    >
                        VS Code
                    </x-filament::tabs.item>
                </x-filament::tabs>

                <div x-show="tab === 'claude'" class="flex flex-col gap-3">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Add this to your project's <code>.mcp.json</code>, or run the CLI command.
                    </p>

                    @include('filament-mcp::filament.partials.code-block', ['code' => $claudeJson, 'label' => '.mcp.json'])
                    @include('filament-mcp::filament.partials.code-block', ['code' => $claudeCli, 'label' => 'Terminal'])
                </div>

                <div x-show="tab === 'cursor'" x-cloak class="flex flex-col gap-3">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Add this to <code>.cursor/mcp.json</code> (project) or <code>~/.cursor/mcp.json</code> (global).
                    </p>

                    @include('filament-mcp::filament.partials.code-block', ['code' => $cursorJson, 'label' => '.cursor/mcp.json'])
                </div>

                <div x-show="tab === 'vscode'" x-cloak class="flex flex-col gap-3">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Add this to <code>.vscode/mcp.json</code> in your workspace, then start the server from the editor.
                    </p>

                    @include('filament-mcp::filament.partials.code-block', ['code' => $vscodeJson, 'label' => '.vscode/mcp.json'])
                </div>

                <p class="text-xs text-gray-400 dark:text-gray-500">
                    Using Filament tenancy? Also send the tenant route key in the
                    <code>{{ config('filament-mcp.tenant_header', 'X-Filament-Mcp-Tenant') }}</code> header.
                </p>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
