<?php

declare(strict_types=1);

namespace App\Entities;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\SystemException;

class ClassComfortTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'b_hl_car_class_comfort';
    }

    /**
     * @throws SystemException
     */
    public static function getMap(): array
    {
        return [
            new IntegerField(
                'ID',
                ['primary' => true]
            ),
            new StringField(
                'UF_NAME',
                ['required' => true]
            ),
        ];
    }
}
