<?php

namespace Mattiasgeniar\FilamentMcp\Contracts;

use Illuminate\Database\Eloquent\Model;

interface PreparesRecordData
{
    /**
     * Massage validated input before it is written to the model. Use this to
     * mirror logic that normally lives in a Filament page (e.g. slug generation).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function __invoke(array $data, ?Model $record): array;
}
