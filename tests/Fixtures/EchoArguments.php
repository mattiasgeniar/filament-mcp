<?php

namespace Mattiasgeniar\FilamentMcp\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Mattiasgeniar\FilamentMcp\Actions\ResourceAction;

class EchoArguments extends ResourceAction
{
    public function description(): string
    {
        return 'Echo the validated arguments back.';
    }

    public function rules(): array
    {
        return ['note' => ['nullable', 'string']];
    }

    public function handle(Model $record, array $arguments): mixed
    {
        return ['arguments' => $arguments];
    }
}
