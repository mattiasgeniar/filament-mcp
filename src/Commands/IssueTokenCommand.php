<?php

namespace Mattiasgeniar\FilamentMcp\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Mattiasgeniar\FilamentMcp\FilamentMcp;
use Mattiasgeniar\FilamentMcp\Models\FilamentMcpToken;
use Throwable;

class IssueTokenCommand extends Command
{
    protected $signature = 'filament-mcp:token
        {user : The id or email of the user the token belongs to}
        {--name=CLI : A label to recognise this token later}
        {--force : Issue even if the user is not currently authorized}';

    protected $description = 'Issue a personal Filament MCP access token for a user';

    public function handle(): int
    {
        $identifier = $this->argument('user');

        $user = $this->resolveUser($identifier);

        if ($user === null) {
            $this->error('No user found for that id or email.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! FilamentMcp::authorize($user)) {
            $this->error('That user is not authorized to use the MCP server. Use --force to issue anyway.');

            return self::FAILURE;
        }

        $name = $this->option('name');

        ['plainText' => $plainText] = FilamentMcpToken::issue($user, is_string($name) ? $name : 'CLI');

        $this->info('MCP token issued. Store it now, it will not be shown again:');
        $this->newLine();
        $this->line($plainText);

        return self::SUCCESS;
    }

    private function resolveUser(string $identifier): ?Authenticatable
    {
        /** @var class-string<Model> $model */
        $model = FilamentMcpToken::userModel();

        if (is_numeric($identifier)) {
            $user = $model::query()->find((int) $identifier);

            return $user instanceof Authenticatable ? $user : null;
        }

        try {
            $user = $model::query()->where('email', $identifier)->first();
        } catch (Throwable) {
            return null;
        }

        return $user instanceof Authenticatable ? $user : null;
    }
}
