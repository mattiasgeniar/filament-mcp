@php($label ??= null)

<div x-data="{ copied: false }" class="fi-mcp-code-block">
    @if ($label)
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400" style="margin-bottom: 0.375rem;">
            {{ $label }}
        </p>
    @endif

    <div style="position: relative;">
        <pre style="overflow-x: auto; border-radius: 0.5rem; background-color: rgb(17 24 39); color: rgb(243 244 246); padding: 1rem; padding-inline-end: 3rem; font-size: 0.75rem; line-height: 1.25rem; margin: 0;"><code>{{ $code }}</code></pre>

        <div style="position: absolute; top: 0.5rem; inset-inline-end: 0.5rem;">
            <x-filament::icon-button
                x-show="! copied"
                icon="heroicon-m-clipboard-document"
                label="Copy to clipboard"
                color="gray"
                size="sm"
                x-on:click="copied = true; window.navigator.clipboard.writeText(@js($code)); setTimeout(() => copied = false, 2000)"
            />

            <x-filament::icon-button
                x-show="copied"
                x-cloak
                icon="heroicon-m-check"
                label="Copied"
                color="success"
                size="sm"
            />
        </div>
    </div>
</div>
