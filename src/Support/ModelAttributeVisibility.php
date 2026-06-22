<?php

namespace Mattiasgeniar\FilamentMcp\Support;

use Illuminate\Database\Eloquent\Model;

class ModelAttributeVisibility
{
    public static function allows(Model $model, string $attribute): bool
    {
        if (in_array($attribute, $model->getHidden(), true)) {
            return false;
        }

        $visible = $model->getVisible();

        return $visible === [] || in_array($attribute, $visible, true);
    }
}
