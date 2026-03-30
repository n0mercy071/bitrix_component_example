<?php

declare(strict_types=1);

use App\Entities\CarTable;
use App\Entities\PositionTable;
use Bitrix\Calendar\Sync\Exceptions\NotFoundException;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserTable;

/**
 * GET параметры ?TIME_START=14.01.2026 10:00&TIME_END=14.01.2026 13:00
 */
class DriverList extends CBitrixComponent
{
    public function onPrepareComponentParams($arParams): array
    {
        global $USER;

        if (!$USER->IsAuthorized()) {
            return $arParams;
        }

        $this->arResult['USER_ID'] = (int)$USER->GetID();

        try {
            if ($_GET['TIME_START']) {
                $this->arResult['TIME_START'] = new DateTime($_GET['TIME_START']);
            }

            if ($_GET['TIME_END']) {
                $this->arResult['TIME_END'] = new DateTime($_GET['TIME_END']);;
            }
        } catch (Throwable) {
        }

        return parent::onPrepareComponentParams($arParams);
    }

    public function executeComponent(): void
    {
        if (!$this->checkData()) {
            return;
        }

        Loader::includeModule('highloadblock');
        // Не стал выносить в композер или инит для упрощения структуры
        Loader::registerNamespace(
            'App',
            "./local/classes"
        );

        $this->setClassComfort();
        $this->setCarsVariant();

        // Элементы в $this->arResult['ITEMS']
        var_dump($this->arResult['ITEMS']);
    }

    private function checkData(): bool
    {
        if (
            $this->arResult['USER_ID'] <= 0 ||
            !isset($this->arResult['TIME_START']) ||
            !isset($this->arResult['TIME_END'])
        ) {
            return false;
        }

        return true;
    }

    private function setClassComfort(): void
    {
        try {
            $user = UserTable::getList([
                'select' => ['ID', 'POSITION', 'POSITION.CLASS_COMFORT_POSITION.CLASS_COMFORT'],
                'filter' => [
                    'ID' => $this->arResult['USER_ID'],
                    '!UF_POSITION' => '',
                ],
                'runtime' => [
                    new Reference(
                        'POSITION',
                        PositionTable::class,
                        ['=this.UF_POSITION' => 'ref.ID']
                    ),
                ],
            ])->fetchObject();

            if (!$user) {
                throw new NotFoundException('USER NOT FOUND');
            }

            $position = $user->get('POSITION');
            $this->arResult['USER'] = [
                'ID' => $user['ID'],
                'POSITION' => [
                    'ID' => $position->get('ID'),
                    'NAME' => $position->get('UF_NAME'),
                ],
            ];

            foreach ($user->get('POSITION')->get('CLASS_COMFORT_POSITION') as $classComfortPosition) {
                $classComfort = $classComfortPosition->get('CLASS_COMFORT');
                $this->arResult['CLASS_COMFORT_IDS'][] = $classComfort->get('ID');

                $this->arResult['USER']['POSITION']['CLASS_COMFORT'][] = [
                    'ID' => $classComfort->get('ID'),
                    'UF_NAME' => $classComfort->get('UF_NAME'),
                ];
            }
        } catch (Throwable) {}
    }

    private function setCarsVariant(): void
    {
        $res = [];

        try {
            if (!isset($this->arResult['CLASS_COMFORT_IDS'])) {
                throw new NotFoundException('CLASS_COMFORT');
            }

            // Получаем резервы по времени
            $reserved = $this->getReservedCar();

            // Получаем доступные машины исключая те, что в резерве
            $cars = CarTable::getList([
                'select' => [
                    'UF_MODEL',
                    'DRIVER',
                    'CLASS_COMFORT',
                ],
                'filter' => [
                    '=UF_CLASS_COMFORT_ID' => $this->arResult['CLASS_COMFORT_IDS'],
                    '!ID' => $reserved,
                ]
            ])->fetchCollection();

            foreach ($cars as $car) {
                $driver = $car->get('DRIVER');
                $classComfort = $car->get('CLASS_COMFORT');

                $res[] = [
                    'ID' => $car->get('ID'),
                    'MODEL' => $car->get('UF_MODEL'),
                    'DRIVER' => [
                        'ID' => $driver->get('ID'),
                        'NAME' => $driver->get('UF_NAME'),
                    ],
                    'CLASS_COMFORT' => [
                        'ID' => $classComfort->get('ID'),
                        'UF_MODEL' => $classComfort->get('UF_NAME'),
                    ]
                ];
            }
        } catch (Throwable) {}

        $this->arResult['ITEMS'] = $res;
    }

    /**
     * @return int[]
     */
    private function getReservedCar(): array
    {
        $res = [];

        try {
            $carUserEntity = HighloadBlockTable::compileEntity('CarUser');
            /** @var DataManager $carUser */
            $carUser = $carUserEntity->getDataClass();

            $reserved = $carUser::getList([
                'select' => ['UF_CAR_ID'],
                'filter' => [
                    [
                        'LOGIC' => 'OR',
                        // Начало времени в резерве
                        [
                            '<UF_TIME_START' => $this->arResult['TIME_START'],
                            '>UF_TIME_END' => $this->arResult['TIME_START'],
                        ],
                        // Конец времени в резерве
                        [
                            '<UF_TIME_START' => $this->arResult['UF_TIME_END'],
                            '>UF_TIME_END' => $this->arResult['UF_TIME_END'],
                        ],
                        // Время внутри резерва
                        [
                            '<UF_TIME_START' => $this->arResult['TIME_START'],
                            '>UF_TIME_END' => $this->arResult['TIME_END'],
                        ],
                        // Резерв внутри времени
                        [
                            '>UF_TIME_START' => $this->arResult['TIME_START'],
                            '<UF_TIME_END' => $this->arResult['TIME_END'],
                        ],
                    ]
                ]
            ])->fetchAll();

            $res = array_column($reserved, 'UF_CAR_ID');
        } catch (Throwable) {}

        return $res;
    }
}
