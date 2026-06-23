<?php

namespace Mattiasgeniar\FilamentMcp;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Mcp\Facades\Mcp;
use Mattiasgeniar\FilamentMcp\Commands\IssueTokenCommand;
use Mattiasgeniar\FilamentMcp\Http\Middleware\Authenticate;
use Mattiasgeniar\FilamentMcp\Models\FilamentMcpToolCall;
use Mattiasgeniar\FilamentMcp\Server\McpServer;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentMcpServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-mcp';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasConfigFile()
            ->hasViews()
            ->hasMigrations([
                'create_filament_mcp_tokens_table',
                'create_filament_mcp_tool_calls_table',
            ])
            ->runsMigrations()
            ->hasCommand(IssueTokenCommand::class)
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command
                    ->publishConfigFile()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('mattiasgeniar/filament-mcp');
            });
    }

    public function packageBooted(): void
    {
        if (! config('filament-mcp.enabled', true)) {
            return;
        }

        $path = '/' . ltrim((string) config('filament-mcp.path', 'filament-mcp'), '/');

        /** @var array<int, string> $middleware */
        $middleware = config('filament-mcp.middleware', []);

        Mcp::web($path, McpServer::class)
            ->middleware([Authenticate::class, ...$middleware]);

        $this->scheduleAuditPruning();
    }

    protected function scheduleAuditPruning(): void
    {
        if ((int) config('filament-mcp.audit.retention_days', 365) <= 0) {
            return;
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('model:prune', ['--model' => [FilamentMcpToolCall::class]])
                ->daily();
        });
    }
}
