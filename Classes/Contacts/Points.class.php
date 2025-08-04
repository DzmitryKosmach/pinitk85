<?php

declare(strict_types=1);

class Points extends ExtDbList
{
    /**
     * @var string
     */
    static string $tab = 'points';

    public function __construct()
    {
        self::setTable(self::$tab);
    }

    public function getList(): array
    {
        $points = [];
        $city = '';
// `weight` asc,
        $data = $this->get('*', '', '`city` asc, `address`');

        foreach ($data as $point) {
            if ($city != $point['city']) {
                $city = $point['city'];
            }

            $points[$city][] = $point['address'];
        }

        //dd($points);

        return $points;
    }
}
