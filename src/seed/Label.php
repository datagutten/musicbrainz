<?php


namespace datagutten\musicbrainz\seed;


class Label extends Element
{
    public static string $entity = 'label';
    public string $mbid;
    public string $name;
    protected array $fields = ['id', 'catalog_number', 'name'];

    public function __construct(array $args)
    {
        $this->register_fields($args);
    }
}