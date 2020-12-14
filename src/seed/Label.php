<?php


namespace datagutten\musicbrainz\seed;


class Label extends Element
{
    public $mbid;
    public $catalog_number;
    public $name;
    protected $fields = ['mbid', 'catalog_number', 'name'];

    public function __construct(array $args)
    {
        $this->register_fields($args);
    }
}