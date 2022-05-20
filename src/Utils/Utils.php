<?php

namespace WMG\Migration\Utils;

use InvalidArgumentException;


class Utils
{
    public static function definedRelations(): array
    {
        $reflector = new \ReflectionClass(get_called_class());

        return collect($reflector->getMethods())
            ->filter(
                fn ($method) => !empty($method->getReturnType()) &&
                    str_contains(
                        $method->getReturnType(),
                        'Illuminate\Database\Eloquent\Relations'
                    )
            )
            ->pluck('name')
            ->all();
    }

    public static function castToObject($instance, $className)
    {
        if (!is_object($instance)) {
            throw new InvalidArgumentException(
                'Argument 1 must be an Object'
            );
        }
        if (!class_exists($className)) {
            throw new InvalidArgumentException(
                'Argument 2 must be an existing Class'
            );
        }
        return unserialize(
            sprintf(
                'O:%d:"%s"%s',
                strlen($className),
                $className,
                strstr(strstr(serialize($instance), '"'), ':')
            )
        );
    }
}
