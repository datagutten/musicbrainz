<?php


namespace datagutten\musicbrainz\seed;


class Track extends Element
{
    protected $fields = ['name', 'number', 'recording', 'length'];
    /**
     * @var string Track name
     */
    public $name;
    /**
     * @var string Track number
     */
    public $number;
    /**
     * @var string Recording MBID
     */
    public $recording;
    /**
     * @var int Track length in milliseconds
     */
    public $length;

    /**
     * @var Artist[] Artists
     */
    public $artists = [];

    /**
     * Track constructor.
     * @param $args
     */
    public function __construct($args)
    {
        $this->register_fields($args);
    }

    public function artist($args)
    {
        $artist = new Artist($args);
        $this->artists[] = $artist;
        return $artist;
    }

    public function save(string $prefix)
    {
        $data = parent::save($prefix);
        foreach ($this->artists as $key => $artist)
        {
            $data += $artist->save(sprintf('%sartist_credit.names.%d.', $prefix, $key));
        }
        return $data;
    }
}