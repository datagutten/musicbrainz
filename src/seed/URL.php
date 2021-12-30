<?php


namespace datagutten\musicbrainz\seed;


class URL extends Element
{
    public static string $entity = 'url';
    protected array $fields = ['id', 'url', 'link_type', 'type'];

    /**
     * @var string URL MBID
     */
    public string $id;

    /**
     * @var string URL
     */
    public string $url;

    /**
     * @var int URL type used in submission
     */
    public int $link_type;

    /**
     * @var string URL destination name (discogs, tidal, etc)
     */
    public string $type;

    /**
     * Stream for free
     */
    const stream_for_free = 85;

    /**
     * @var string[] URL type used in submission
     */
    public static array $url_types = [
        73 => 'get the music',
        74 => 'purchase for download',
        75 => 'download for free',
        79 => 'purchase for mail-order',
        85 => 'stream for free',
        980 => 'streaming page',
        906 => 'crowdfunding page',
        729 => 'show notes',
    ];

    public function __construct($args)
    {
        $this->register_fields($args);
    }
}