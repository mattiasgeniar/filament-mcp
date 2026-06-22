<?php

namespace Mattiasgeniar\FilamentMcp\Filament\Pages;

use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Mattiasgeniar\FilamentMcp\Filament\Concerns\ResolvesPanelUser;
use Mattiasgeniar\FilamentMcp\Models\FilamentMcpToken;
use Mattiasgeniar\FilamentMcp\Models\FilamentMcpToolCall;

class TokenActivity extends Page implements HasTable
{
    use InteractsWithTable;
    use ResolvesPanelUser;

    protected string $view = 'filament-mcp::filament.pages.token-activity';

    protected static bool $shouldRegisterNavigation = false;

    public FilamentMcpToken $token;

    public static function canAccess(): bool
    {
        return ManageMcpTokens::canAccess();
    }

    /**
     * The route parameter is deliberately not named `token`: that would collide
     * with the typed `$token` property and let Livewire resolve it via unscoped
     * route-model binding, bypassing the per-user scoping in mount().
     */
    public static function getRoutePath(Panel $panel): string
    {
        return '/manage-mcp-tokens/{tokenId}/activity';
    }

    public function mount(int | string $tokenId): void
    {
        $this->token = FilamentMcpToken::query()
            ->forUser($this->currentUser())
            ->findOrFail($tokenId);
    }

    public function getTitle(): string | Htmlable
    {
        return "Activity for {$this->token->name}";
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->toolCallsQuery())
            ->defaultSort('created_at', 'desc')
            ->columns([
                IconColumn::make('success')
                    ->label('Status')
                    ->boolean()
                    ->tooltip(fn (bool $state): string => $state ? 'Succeeded' : 'Failed'),
                TextColumn::make('tool_name')
                    ->label('Tool')
                    ->searchable(),
                TextColumn::make('duration_ms')
                    ->label('Duration')
                    ->suffix(' ms')
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime()
                    ->since()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('details')
                    ->label('Details')
                    ->icon('heroicon-m-eye')
                    ->color('gray')
                    ->modalHeading(fn (FilamentMcpToolCall $record): string => "{$record->tool_name} call")
                    ->schema([
                        TextEntry::make('success')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Succeeded' : 'Failed')
                            ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                        TextEntry::make('duration_ms')
                            ->label('Duration')
                            ->suffix(' ms')
                            ->placeholder('—'),
                        TextEntry::make('created_at')
                            ->label('Time')
                            ->dateTime(),
                        TextEntry::make('arguments')
                            ->placeholder('No arguments')
                            ->html()
                            ->state(fn (FilamentMcpToolCall $record): string => $this->formatArguments($record)),
                    ])
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->emptyStateHeading('No activity yet')
            ->emptyStateDescription('This token has not made any tool calls.')
            ->emptyStateIcon('heroicon-o-list-bullet');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back to tokens')
                ->icon('heroicon-m-arrow-left')
                ->color('gray')
                ->url(ManageMcpTokens::getUrl()),
        ];
    }

    /**
     * @return Builder<FilamentMcpToolCall>
     */
    protected function toolCallsQuery(): Builder
    {
        return FilamentMcpToolCall::query()
            ->where('filament_mcp_token_id', $this->token->getKey());
    }

    protected function formatArguments(FilamentMcpToolCall $call): string
    {
        if (blank($call->arguments)) {
            return '';
        }

        $json = json_encode($call->arguments, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            $json = 'Unable to display arguments.';
        }

        return '<pre class="overflow-x-auto rounded-lg bg-gray-50 p-4 text-xs text-gray-700 dark:bg-white/5 dark:text-gray-300">' . e($json) . '</pre>';
    }
}
