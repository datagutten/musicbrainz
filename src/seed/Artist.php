<?php


namespace datagutten\musicbrainz\seed;


class Artist extends Element
{
    protected $fields = ['mbid', 'name', 'artist_name', 'join_phrase'];
    /**
     * @var string Artist MBID
     */
    public string $mbid;
    /**
     * @var string The name of the artist, as credited on the release. Optional, if omitted it will default to the artist’s current name
     *
     */
    public string $name;
    /**
     * @var string The name of the artist as it is usually referred too. Optional if you already specified both credited name and MBID
     */
    public string $artist_name;
    /**
     * @var string An optional phrase to join this artist with the next artist. For example, you could use “ & ” to join “Calvin” with “Hobbes” to get the final text “Calvin & Hobbes”
     */
    public string $join_phrase;
    /**
     * @var Release[] Artist releases
     */
    public array $releases;

    public function __construct(array $args)
    {
        $this->register_fields($args);
        if (!empty($args['releases']))
        {
            foreach ($args['releases'] as $release)
            {
                $this->releases[] = new Release($release);
            }
        }
    }
}