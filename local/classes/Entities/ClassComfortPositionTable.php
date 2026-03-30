<?php

declare(strict_types=1);

namespace App\Entities;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\SystemException;

class ClassComfortPositionTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'b_hl_class_comfort_position';
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
            new IntegerField(
                'UF_POSITION_ID',
                ['required' => true]
            ),
            new IntegerField(
                'UF_CLASS_COMFORT',
                ['required' => true]
            ),

            new Reference(
                'POSITION',
                PositionTable::class,
                ['=this.UF_POSITION_ID' => 'ref.ID']
            ),

            new Reference(
                'CLASS_COMFORT',
                ClassComfortTable::class,
                ['=this.UF_CLASS_COMFORT' => 'ref.ID']
            ),
        ];
    }
}
