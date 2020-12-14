<?php


namespace datagutten\musicbrainz\seed;


class Artist extends Element
{
    protected $fields = ['mbid', 'name', 'artist_name', 'join_phrase'];
    public $mbid;
    public $name;
    public $artist_name;
    public $join_phrase;

    public function __construct(array $args)
    {
        $this->register_fields($args);
    }

/*    public function save(string $prefix)
    {
        $data = [];
        foreach ($this->fields as $field)
        {
            if (empty($this->$field))
                continue;

            $key = $prefix . $field;
            $data[$key] = $this->$field;
        }
        return $data;
    }*/
}