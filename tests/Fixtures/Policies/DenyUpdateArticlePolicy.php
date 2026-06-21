<?php

namespace Mattiasgeniar\FilamentMcp\Tests\Fixtures\Policies;

use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Models\Article;
use Mattiasgeniar\FilamentMcp\Tests\Fixtures\Models\User;

class DenyUpdateArticlePolicy
{
    public function update(User $user, Article $article): bool
    {
        return false;
    }
}
