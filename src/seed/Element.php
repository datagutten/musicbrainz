<?php


namespace datagutten\musicbrainz\seed;


use datagutten\musicbrainz\SimpleArrayAccess;
use RuntimeException;

abstract class Element extends SimpleArrayAccess
{
    /**
     * @var string Entity name as used in MusicBrainz URL
     */
    public static string $entity;
    /**
     * @var array Field names
     */
    protected array $fields = [];
    /**
     * @var array Field name aliases for release editor seeding. API field name as key, seed field name as value
     */
    protected array $field_aliases = ['id' => 'mbid', 'title' => 'name'];
    /**
     * @var array Fields to exclude from release editor seeding
     */
    protected array $fields_non_seed = [];
    /**
     * @var array Raw data from API
     */
    public array $data;
    /**
     * @var string MBID
     */
    public string $id;

    /**
     * Register valid arguments as properties
     * @param array $args
     */
    public function register_fields(array $args)
    {
        $this->data = $args; //Save raw data
        if (empty($this->fields))
            throw new RuntimeException('No fields found');

        foreach ($this->fields as $field)
        {
            if (!empty($args[$field]))
                $this->$field = $args[$field];
        }

        //Register alias fields
        foreach ($this->field_aliases as $field => $alias)
        {
            if (!empty($args[$alias]))
                $this->$field = $args[$alias];
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
        foreach (array_diff($this->fields, $this->fields_non_seed) as $field)
        {
            if (!empty($this->$field))
            {
                if (!empty($this->field_aliases[$field]))
                {
                    $alias = $this->field_aliases[$field];
                    $data[$prefix . $alias] = $this->$field;
                }
                else
                    $data[$prefix . $field] = $this->$field;
            }
        }
        return $data;
    }

    /**
     * Get MusicBrainz URL
     * @return string
     */
    public function link(): string
    {
        return sprintf('https://musicbrainz.org/%s/%s', static::$entity, $this->id);
    }
}