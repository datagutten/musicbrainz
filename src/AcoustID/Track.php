<?php


namespace datagutten\musicbrainz\AcoustID;


use datagutten\musicbrainz\seed;
use datagutten\tools\SimpleArrayAccess;

class Track extends SimpleArrayAccess
{
    /**
     * @var float Track duration in seconds with decimals
     */
    public float $duration;
    /**
     * @var Fingerprint Fingerprint
     */
    public Fingerprint $fingerprint;
    /**
     * @var int Bitrate
     */
    public int $bitrate;
    /**
     * @var string Track ID
     */
    public string $id;
    /**
     * @var seed\Track[] Recordings
     */
    public array $recordings = [];

    public function __construct($data)
    {
        $this->id = $data['id'];
        if (!empty($data['recordings']))
        {
            foreach ($data['recordings'] as $recording)
            {
                $this->recordings[] = new seed\Track($recording);
            }
        }
    }
}