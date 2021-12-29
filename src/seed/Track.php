<?php


namespace datagutten\musicbrainz\seed;


class Track extends Element
{
    protected $fields = ['id', 'title', 'number', 'recording', 'length'];
    /**
     * @var string Track MBID
     */
    public string $id;
    /**
     * @var string Track name
     */
    public string $title;
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
        foreach ($args['artist-credit'] ?? [] as $credit)
        {
            $artist = $this->artist($credit['artist']);
            $artist->artist_name = $artist->name;
            $artist->name = $credit['name'];
            $artist->join_phrase = $credit['joinphrase'];
        }
    }

    public function artist($args): Artist
    {
        $artist = new Artist($args);
        $this->artists[] = $artist;
        return $artist;
    }

    public function save(string $prefix): array
    {
        $data = parent::save($prefix);
        foreach ($this->artists as $key => $artist)
        {
            $data += $artist->save(sprintf('%sartist_credit.names.%d.', $prefix, $key));
        }
        return $data;
    }
}