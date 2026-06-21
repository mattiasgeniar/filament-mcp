<div
    x-data="{ copied: false }"
    class="flex flex-col gap-3"
>
    <p class="text-sm text-gray-500 dark:text-gray-400">
        Copy this token now. For your security, it will not be shown again.
    </p>

    <x-filament::input.wrapper>
        <x-slot name="suffix">
            <x-filament::icon-button
                x-show="! copied"
                icon="heroicon-m-clipboard-document"
                label="Copy to clipboard"
                color="gray"
                x-on:click="copied = true; window.navigator.clipboard.writeText($refs.token.value); setTimeout(() => copied = false, 2000)"
            />

            <x-filament::icon-button
                x-show="copied"
                x-cloak
                icon="heroicon-m-check"
                label="Copied"
                color="success"
            />
        </x-slot>

        <x-filament::input
            type="text"
            readonly
            x-ref="token"
            :value="$token"
        />
    </x-filament::input.wrapper>
</div>
