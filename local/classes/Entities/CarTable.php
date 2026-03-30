<?php

declare(strict_types=1);

namespace App\Entities;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\SystemException;

class CarTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'b_hl_car';
    }

    /**
     * @throws SystemException
     * @throws LoaderException
     */
    public static function getMap(): array
    {
        Loader::includeModule('highloadblock');
        $driverEntity = HighloadBlockTable::compileEntity('Driver');

        return [
            new IntegerField(
                'ID',
                ['primary' => true]
            ),
            new StringField(
                'UF_MODEL',
                ['required' => true]
            ),
            new IntegerField(
                'UF_DRIVER_ID',
                ['required' => true]
            ),
            new IntegerField(
                'UF_CLASS_COMFORT_ID',
                ['required' => true]
            ),

            new Reference(
                'CLASS_COMFORT',
                ClassComfortTable::class,
                ['=this.UF_CLASS_COMFORT_ID' => 'ref.ID']
            ),
            new Reference(
                'DRIVER',
                $driverEntity->getDataClass(),
                ['=this.UF_DRIVER_ID' => 'ref.ID']
            ),
        ];
    }
}
