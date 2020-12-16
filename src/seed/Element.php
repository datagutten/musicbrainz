<?php


namespace datagutten\musicbrainz\seed;


use RuntimeException;

abstract class Element
{
    /**
     * @var array Field names
     */
    protected $fields = [];

    /**
     * Register valid arguments as properties
     * @param array $args
     */
    public function register_fields(array $args)
    {
        if(empty($this->fields))
            throw new RuntimeException('No fields found');

        foreach ($this->fields as $field)
        {
            if (!empty($args[$field]))
                $this->$field = $args[$field];
        }
    }

    /**
     * Get fields from current class
     * @param string $prefix Field name prefix
     * @return array
     */
    public function save(string $prefix): array
    {
        $data = [];
        foreach ($this->fields as $field)
        {
            if (!empty($this->$field))
                $data[$prefix.$field] = $this->$field;
        }
        return $data;
    }
}