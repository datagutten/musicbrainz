<?php


namespace datagutten\musicbrainz\seed;


use DateTime;

class Event extends Element
{
    /**
     * @var DateTime Event date
     */
    public $dateTime;
    /**
     * @var string The country of the release event. May be any valid country ISO code (for example: GB, US, FR)
     */
    public $country;

    function __construct(DateTime $dateTime, $country = '')
    {
        $this->dateTime = $dateTime;
        $this->country = $country;
    }

    function save(string $prefix): array
    {
        $output = [
            $prefix.'date.year' => $this->dateTime->format('Y'),
            $prefix.'date.month' => $this->dateTime->format('m'),
            $prefix.'date.day' => $this->dateTime->format('d')
        ];
        if (!empty($this->country))
            $output[$prefix . 'country'] = $this->country;

        return $output;
    }
}