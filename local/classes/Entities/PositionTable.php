<?php

namespace App\Entities;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\SystemException;

class PositionTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'b_hl_position';
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

            new OneToMany(
                'CLASS_COMFORT_POSITION',
                ClassComfortPositionTable::class,
                'POSITION',
            ),
        ];
    }
}
