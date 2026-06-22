<div class="flex flex-col gap-3">
    @if ($calls->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400">
            This token has not made any tool calls yet.
        </p>
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400">
            The {{ $calls->count() }} most recent tool calls made with this token.
        </p>

        <ul class="divide-y divide-gray-100 dark:divide-white/10">
            @foreach ($calls as $call)
                <li class="flex flex-col gap-1 py-2">
                    <div class="flex items-center justify-between gap-3">
                        <span class="flex items-center gap-2 text-sm font-medium text-gray-950 dark:text-white">
                            @if ($call->success)
                                <x-filament::icon icon="heroicon-m-check-circle" class="h-4 w-4 text-success-500" />
                            @else
                                <x-filament::icon icon="heroicon-m-x-circle" class="h-4 w-4 text-danger-500" />
                            @endif

                            <span class="font-mono">{{ $call->tool_name }}</span>
                        </span>

                        <span class="whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                            {{ $call->created_at?->diffForHumans() }}
                            @if ($call->duration_ms !== null)
                                · {{ $call->duration_ms }} ms
                            @endif
                        </span>
                    </div>

                    @if (! empty($call->arguments))
                        <details class="text-xs text-gray-500 dark:text-gray-400">
                            <summary class="cursor-pointer select-none">Arguments</summary>
                            <pre class="mt-1 overflow-x-auto rounded-md bg-gray-50 p-2 dark:bg-white/5">{{ json_encode($call->arguments, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </details>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</div>
