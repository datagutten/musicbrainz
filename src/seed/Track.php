<?php


namespace datagutten\musicbrainz\seed;


class Track extends Element
{
    public static string $entity = 'track';
    protected array $fields = ['id', 'title', 'number', 'length'];
    /**
     * @var string Recording MBID
     */
    public string $id;
    /**
     * @var string Track name
     */
    public string $title;
    /**
     * @var string Track number
     */
    public string $number;
    /**
     * @var int Track length in milliseconds
     */
    public int $length;

    /**
     * @var Artist[] Artists
     */
    public array $artists = [];

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