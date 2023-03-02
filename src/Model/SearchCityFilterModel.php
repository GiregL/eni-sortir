<?php

namespace App\Model;

class SearchCityFilterModel
{
    private $cityName;

    /**
     * @return mixed
     */
    public function getCityName()
    {
        return $this->cityName;
    }

    /**
     * @param mixed $cityName
     */
    public function setCityName($cityName): void
    {
        $this->cityName = $cityName;
    }
}