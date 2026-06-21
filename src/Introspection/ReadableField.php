<?php

namespace Mattiasgeniar\FilamentMcp\Introspection;

class ReadableField
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description = null,
    ) {}
}
