<?php

use Illuminate\Support\Js;

it('escapes the code value into the clipboard copy handler', function () {
    $code = "curl 'https://example.test' <script>alert(1)</script>";

    $html = view('filament-mcp::filament.partials.code-block', ['code' => $code])->render();

    expect($html)->not->toContain('@js(')
        ->and($html)->toContain('navigator.clipboard.writeText(')
        ->and($html)->toContain(htmlspecialchars((string) Js::from($code), ENT_QUOTES));
});
