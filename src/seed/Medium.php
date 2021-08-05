<?php


namespace datagutten\musicbrainz\seed;


class Medium extends Element
{
    protected $fields = ['format', 'title', 'position']; //position is a valid field in the documentation, but gives error
    protected array $field_aliases = ['title' => 'name'];
    protected array $fields_non_seed = ['position'];

    /**
     * @var string Medium format (CD, Digital Media, etc.)
     */
    public string $format;
    public $position;
    /**
     * @var string Medium title
     */
    public string $title;
    /**
     * @var Track[]
     */
    public array $tracks = [];

    public function __construct($args)
    {
        if (!empty($args['position']))
            $this->position = $args['position'];
        $this->register_fields($args);
        foreach ($args['tracks'] as $track)
        {
            $this->track($track);
        }
    }

    /**
     * @param $args
     * @return Track
     */
    public function track($args): Track
    {
        $track = new Track($args);
        $this->tracks[] = $track;
        return $track;
    }

    public function save(string $prefix): array
    {
        $data = parent::save($prefix);
        foreach ($this->tracks as $key => $track)
        {
            $data += $track->save(sprintf('%strack.%d.', $prefix, $key));
        }
        return $data;
    }
}