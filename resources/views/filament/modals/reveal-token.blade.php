<div
    x-data="{ copied: false }"
    class="flex flex-col gap-3"
>
    <p class="text-sm text-gray-500 dark:text-gray-400">
        Copy this token now. For your security, it will not be shown again.
    </p>

    <div class="flex items-center gap-2">
        <x-filament::input.wrapper class="flex-1">
            <x-filament::input
                type="text"
                readonly
                x-ref="token"
                :value="$token"
            />
        </x-filament::input.wrapper>

        <x-filament::button
            color="gray"
            icon="heroicon-m-clipboard"
            x-on:click="window.navigator.clipboard.writeText($refs.token.value); copied = true"
        >
            <span x-show="! copied">Copy</span>
            <span x-show="copied" x-cloak>Copied</span>
        </x-filament::button>
    </div>
</div>
