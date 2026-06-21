<?php

namespace Mattiasgeniar\FilamentMcp\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Mattiasgeniar\FilamentMcp\Contracts\PreparesRecordData;

class UppercasesTitle implements PreparesRecordData
{
    public function __invoke(array $data, ?Model $record): array
    {
        if (isset($data['title'])) {
            $data['title'] = strtoupper($data['title']);
        }

        return $data;
    }
}
