<?php

namespace Mattiasgeniar\FilamentMcp\Introspection;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

class SchemaCompiler
{
    /**
     * Build the JSON Schema input properties for a set of fields.
     *
     * @param  Collection<int, FieldDefinition>  $fields
     * @return array<string, Type>
     */
    public function inputProperties(JsonSchema $schema, Collection $fields, bool $forUpdate): array
    {
        $properties = [];

        foreach ($fields as $field) {
            $type = $this->typeFor($schema, $field);

            $description = $this->describe($field);

            if ($description !== null) {
                $type = $type->description($description);
            }

            if (! $forUpdate && $field->required) {
                $type = $type->required();
            }

            $properties[$field->name] = $type;
        }

        return $properties;
    }

    /**
     * Build the Laravel validation rules for a set of fields.
     *
     * @param  Collection<int, FieldDefinition>  $fields
     * @return array<string, array<int, string|In>>
     */
    public function validationRules(Collection $fields, bool $forUpdate): array
    {
        $rules = [];

        foreach ($fields as $field) {
            $fieldRules = $field->rules;

            if ($forUpdate) {
                $fieldRules = array_values(array_filter($fieldRules, fn (string $rule): bool => $rule !== 'required'));
                array_unshift($fieldRules, 'sometimes');
            }

            $fieldRules = array_values(array_unique($fieldRules));

            if ($field->type === FieldType::Enum && $field->enumOptions !== null) {
                $fieldRules[] = Rule::in($field->enumOptions);
            }

            $rules[$field->name] = $fieldRules;
        }

        return $rules;
    }

    private function typeFor(JsonSchema $schema, FieldDefinition $field): Type
    {
        return match ($field->type) {
            FieldType::Boolean => $schema->boolean(),
            FieldType::Integer => $schema->integer(),
            FieldType::Number => $schema->number(),
            FieldType::Date => $schema->string(),
            FieldType::Enum => $field->enumOptions !== null
                ? $schema->string()->enum($field->enumOptions)
                : $schema->string(),
            FieldType::String => $schema->string(),
        };
    }

    private function describe(FieldDefinition $field): ?string
    {
        if ($field->type === FieldType::Date) {
            $hint = 'Date/time in the app timezone, e.g. 2026-06-20 14:00';

            return $field->description !== null ? "{$field->description} ({$hint})" : $hint;
        }

        return $field->description;
    }
}
