<?php

namespace Mattiasgeniar\FilamentMcp\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $bio
 * @property string $secret_token
 */
class Profile extends Model
{
    protected $guarded = [];

    protected $hidden = ['secret_token'];
}
