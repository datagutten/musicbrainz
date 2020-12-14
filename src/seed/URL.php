<?php


namespace datagutten\musicbrainz\seed;


class URL extends Element
{
    protected $fields = ['url', 'link_type'];
    public $url;
    public $link_type;
    /**
     * Stream for free
     */
    const stream_for_free = 85;

    public function __construct($args)
    {
        $this->register_fields($args);
    }
}