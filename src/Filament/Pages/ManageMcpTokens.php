<?php

namespace Mattiasgeniar\FilamentMcp\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Mattiasgeniar\FilamentMcp\FilamentMcp;
use Mattiasgeniar\FilamentMcp\Models\FilamentMcpToken;
use UnitEnum;

class ManageMcpTokens extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament-mcp::filament.pages.manage-mcp-tokens';

    public string $activeTokenTab = 'active';

    public function updatedActiveTokenTab(): void
    {
        $this->resetPage();
    }

    public static function canAccess(): bool
    {
        if (! config('filament-mcp.ui.enabled', true)) {
            return false;
        }

        $user = Filament::auth()->user();

        if (! $user instanceof Authenticatable) {
            return false;
        }

        return FilamentMcp::authorize($user);
    }

    public static function getNavigationGroup(): string | UnitEnum | null
    {
        return config('filament-mcp.ui.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return (string) config('filament-mcp.ui.navigation.label', 'Tokens');
    }

    public static function getNavigationIcon(): string | BackedEnum | Htmlable | null
    {
        return config('filament-mcp.ui.navigation.icon', 'heroicon-o-key');
    }

    public static function getNavigationSort(): ?int
    {
        $sort = config('filament-mcp.ui.navigation.sort');

        if ($sort === null) {
            return null;
        }

        return (int) $sort;
    }

    public function getTitle(): string | Htmlable
    {
        return 'MCP access tokens';
    }

    public function showSetupGuide(): bool
    {
        return (bool) config('filament-mcp.ui.show_setup_guide', true);
    }

    public function mcpEndpointUrl(): string
    {
        return url(ltrim((string) config('filament-mcp.path', 'filament-mcp'), '/'));
    }

    public function mcpServerKey(): string
    {
        return Str::slug((string) config('filament-mcp.server.name', 'filament-mcp')) ?: 'filament-mcp';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->ownTokensQuery())
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('last_used_at')
                    ->label('Last used')
                    ->since()
                    ->placeholder('Never'),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->since(),
            ])
            ->recordActions([
                Action::make('revoke')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Revoking this token immediately blocks any client using it. This cannot be undone.')
                    ->visible(fn (FilamentMcpToken $record): bool => $record->revoked_at === null)
                    ->action(fn (FilamentMcpToken $record) => $record->revoke()),
            ])
            ->emptyStateHeading('No tokens yet')
            ->emptyStateDescription('Generate a token to connect an MCP client to this panel.')
            ->emptyStateIcon('heroicon-o-key');
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->generateAction(),
        ];
    }

    public function generateAction(): Action
    {
        return Action::make('generate')
            ->label('Generate token')
            ->icon('heroicon-m-plus')
            ->modalHeading('Generate a new token')
            ->modalSubmitActionLabel('Generate')
            ->schema([
                TextInput::make('name')
                    ->label('Name')
                    ->helperText('A label to recognise this token later.')
                    ->required()
                    ->maxLength(255)
                    ->default('Web'),
            ])
            ->action(function (array $data): void {
                ['plainText' => $plainText] = FilamentMcpToken::issue($this->currentUser(), $data['name']);

                $this->replaceMountedAction('revealToken', ['token' => $plainText]);
            });
    }

    /**
     * Reached only via the generate action's replaceMountedAction() call, so it
     * is defined as a method (resolvable by name) rather than a header button.
     * The plaintext travels as a mounted-action argument, not a persisted
     * property, so it is dropped the moment the modal is closed.
     */
    public function revealTokenAction(): Action
    {
        return Action::make('revealToken')
            ->modalHeading('Copy your new token')
            ->modalIcon('heroicon-o-key')
            ->modalContent(fn (array $arguments): View => app(ViewFactory::class)->make('filament-mcp::filament.modals.reveal-token', [
                'token' => $arguments['token'] ?? '',
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Done');
    }

    public function activeTokenCount(): int
    {
        return $this->ownTokensQuery('active')->count();
    }

    public function revokedTokenCount(): int
    {
        return $this->ownTokensQuery('revoked')->count();
    }

    /**
     * @return Builder<FilamentMcpToken>
     */
    protected function ownTokensQuery(?string $tab = null): Builder
    {
        $query = FilamentMcpToken::query()
            ->forUser($this->currentUser());

        if (($tab ?? $this->activeTokenTab) === 'revoked') {
            return $query->whereNotNull('revoked_at');
        }

        return $query->whereNull('revoked_at');
    }

    protected function currentUser(): Authenticatable
    {
        $user = Filament::auth()->user();

        abort_unless($user instanceof Authenticatable, 403);

        return $user;
    }
}
