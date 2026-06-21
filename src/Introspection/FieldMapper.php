<?php

namespace Mattiasgeniar\FilamentMcp\Introspection;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Throwable;

class FieldMapper
{
    /**
     * Returns a field definition for a mappable text/scalar field, or null when
     * the component should be skipped (file uploads, custom components, or
     * anything that throws during headless introspection).
     */
    public function map(Field $field): ?FieldDefinition
    {
        try {
            return $this->mapField($field);
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    private function mapField(Field $field): ?FieldDefinition
    {
        $type = $this->typeFor($field);

        if ($type === null) {
            return null;
        }

        return new FieldDefinition(
            name: $field->getName(),
            type: $type,
            required: $field->isRequired(),
            rules: $this->stringRules($field),
            description: $this->descriptionFor($field),
            enumOptions: $type === FieldType::Enum ? $this->optionsFor($field) : null,
        );
    }

    private function typeFor(Field $field): ?FieldType
    {
        if ($field instanceof Select) {
            if ($field->isMultiple()) {
                return null;
            }

            return FieldType::Enum;
        }

        if ($this->isInstanceOfAny($field, [Checkbox::class, Toggle::class])) {
            return FieldType::Boolean;
        }

        if ($field instanceof DateTimePicker) {
            return FieldType::Date;
        }

        if ($this->isInstanceOfAny($field, [MarkdownEditor::class, RichEditor::class, Textarea::class, TextInput::class])) {
            return FieldType::String;
        }

        return null;
    }

    /**
     * @param  array<int, class-string>  $classes
     */
    private function isInstanceOfAny(Field $field, array $classes): bool
    {
        return collect($classes)->contains(fn (string $class): bool => $field instanceof $class);
    }

    /**
     * @return array<int, string>
     */
    private function stringRules(Field $field): array
    {
        return array_values(array_filter($field->getValidationRules(), 'is_string'));
    }

    private function descriptionFor(Field $field): ?string
    {
        $label = $field->getLabel();

        return is_string($label) && $label !== '' ? $label : null;
    }

    /**
     * @return array<int, string>|null
     */
    private function optionsFor(Field $field): ?array
    {
        if (! $field instanceof Select) {
            return null;
        }

        $keys = array_keys($field->getOptions());

        if ($keys === []) {
            return null;
        }

        return array_map('strval', $keys);
    }
}
