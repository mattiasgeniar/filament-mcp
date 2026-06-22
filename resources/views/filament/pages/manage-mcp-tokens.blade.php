<x-filament-panels::page>
    <x-filament::tabs>
        <x-filament::tabs.item
            :active="$activeTokenTab === 'active'"
            :badge="$this->activeTokenCount()"
            wire:click="$set('activeTokenTab', 'active')"
        >
            Active
        </x-filament::tabs.item>

        <x-filament::tabs.item
            :active="$activeTokenTab === 'revoked'"
            :badge="$this->revokedTokenCount()"
            wire:click="$set('activeTokenTab', 'revoked')"
        >
            Revoked
        </x-filament::tabs.item>
    </x-filament::tabs>

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

            $claudeCliRemove = "claude mcp remove {$key}";

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

            $codexToml = <<<TOML
            [mcp_servers.{$key}]
            url = "{$endpoint}"
            http_headers = { "Authorization" = "Bearer fmcp_..." }
            TOML;
        @endphp

        <x-filament::section
            icon="heroicon-o-bolt"
            icon-color="primary"
        >
            <x-slot name="heading">Connect a client</x-slot>

            <x-slot name="description">
                Click the tabs below to find instructions for your favorite AI agent.
            </x-slot>

            <div x-data="{ tab: null }" style="display: flex; flex-direction: column; gap: 1rem;">
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

                    <x-filament::tabs.item
                        :alpine-active="'tab === \'codex\''"
                        x-on:click="tab = 'codex'"
                    >
                        Codex
                    </x-filament::tabs.item>
                </x-filament::tabs>

                <div x-show="tab === 'claude'" x-cloak style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <p style="font-size: 0.875rem; color: rgb(107 114 128);">
                        Add this to your project's <code>.mcp.json</code>, or run the CLI command.
                    </p>

                    @include('filament-mcp::filament.partials.code-block', ['code' => $claudeJson, 'label' => '.mcp.json'])
                    @include('filament-mcp::filament.partials.code-block', ['code' => $claudeCli, 'label' => 'Terminal'])

                    <p style="font-size: 0.875rem; color: rgb(107 114 128);">
                        To remove it again, run:
                    </p>

                    @include('filament-mcp::filament.partials.code-block', ['code' => $claudeCliRemove, 'label' => 'Terminal'])
                </div>

                <div x-show="tab === 'cursor'" x-cloak style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <p style="font-size: 0.875rem; color: rgb(107 114 128);">
                        Add this to <code>.cursor/mcp.json</code> (project) or <code>~/.cursor/mcp.json</code> (global).
                    </p>

                    @include('filament-mcp::filament.partials.code-block', ['code' => $cursorJson, 'label' => '.cursor/mcp.json'])

                    <p style="font-size: 0.875rem; color: rgb(107 114 128);">
                        To remove it again, delete the <code>{{ $key }}</code> entry from that file.
                    </p>
                </div>

                <div x-show="tab === 'vscode'" x-cloak style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <p style="font-size: 0.875rem; color: rgb(107 114 128);">
                        Add this to <code>.vscode/mcp.json</code> in your workspace, then start the server from the editor.
                    </p>

                    @include('filament-mcp::filament.partials.code-block', ['code' => $vscodeJson, 'label' => '.vscode/mcp.json'])

                    <p style="font-size: 0.875rem; color: rgb(107 114 128);">
                        To remove it again, delete the <code>{{ $key }}</code> entry from that file.
                    </p>
                </div>

                <div x-show="tab === 'codex'" x-cloak style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <p style="font-size: 0.875rem; color: rgb(107 114 128);">
                        Add this to <code>~/.codex/config.toml</code>.
                    </p>

                    @include('filament-mcp::filament.partials.code-block', ['code' => $codexToml, 'label' => '~/.codex/config.toml'])

                    <p style="font-size: 0.875rem; color: rgb(107 114 128);">
                        To remove it again, delete the <code>[mcp_servers.{{ $key }}]</code> block from that file.
                    </p>
                </div>

                <p style="font-size: 0.75rem; color: rgb(156 163 175);">
                    Using Filament tenancy? Also send the tenant route key in the
                    <code>{{ config('filament-mcp.tenant_header', 'X-Filament-Mcp-Tenant') }}</code> header.
                </p>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
