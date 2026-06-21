<?php

namespace Mattiasgeniar\FilamentMcp\Introspection;

class FieldDefinition
{
    /**
     * @param  array<int, string>  $rules
     * @param  array<int, string>|null  $enumOptions
     */
    public function __construct(
        public readonly string $name,
        public readonly FieldType $type,
        public readonly bool $required,
        public readonly array $rules,
        public readonly ?string $description = null,
        public readonly ?array $enumOptions = null,
    ) {}
}
