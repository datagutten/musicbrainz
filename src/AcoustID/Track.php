<?php


namespace datagutten\musicbrainz\AcoustID;


use datagutten\musicbrainz\objects\Recording;

class Track
{
    public float $duration;
    public string $fingerprint;
    public int $bitrate;
    public string $id;
    public array $recordings = [];

    public function __construct($data)
    {
        $this->id = $data['id'];
        if (!empty($data['recordings']))
        {
            foreach ($data['recordings'] as $recording)
            {
                $this->recordings[] = Recording::fromAcoustId($recording);
            }
        }
    }

}