<?php


namespace datagutten\musicbrainz\seed;


use DateTime;

class Event extends Element
{
    /**
     * @var DateTime Event date
     */
    public $dateTime;

    function __construct(DateTime $dateTime)
    {
        $this->dateTime = $dateTime;
    }

    function save(string $prefix): array
    {
        return [
            $prefix.'date.year' => $this->dateTime->format('Y'),
            $prefix.'date.month' => $this->dateTime->format('m'),
            $prefix.'date.day' => $this->dateTime->format('d')
        ];
    }
}