<?php


namespace datagutten\musicbrainz\seed;


class URL extends Element
{
    protected array $fields = ['url', 'link_type'];
    public string $url;
    public int $link_type;
    /**
     * Stream for free
     */
    const stream_for_free = 85;

    public function __construct($args)
    {
        $this->register_fields($args);
    }
}