<?php
declare(strict_types=1);

namespace AutoRoute\Value;

class Ymd
{
    public function __construct(
        protected ?int $year = null,
        protected ?int $month = null,
        protected ?int $day = null
    ) {
    }

    public function getYear() : ?int
    {
        return $this->year;
    }

    public function getMonth() : ?int
    {
        return $this->month;
    }

    public function getDay() : ?int
    {
        return $this->day;
    }
}
