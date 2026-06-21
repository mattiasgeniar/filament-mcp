<?php

namespace Mattiasgeniar\FilamentMcp\Introspection;

enum FieldType
{
    case String;
    case Integer;
    case Number;
    case Boolean;
    case Date;
    case Enum;
}
