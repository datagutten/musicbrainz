<?php


namespace datagutten\musicbrainz\seed;


class Label extends Element
{
    public string $mbid;
    public string $name;
    protected array $fields = ['mbid', 'catalog_number', 'name'];

    public function __construct(array $args)
    {
        $this->register_fields($args);
    }
}