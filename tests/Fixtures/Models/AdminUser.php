<?php

namespace Mattiasgeniar\FilamentMcp\Tests\Fixtures\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class AdminUser extends Authenticatable
{
    protected $table = 'admin_users';

    protected $guarded = [];
}
