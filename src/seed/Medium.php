<?php


namespace datagutten\musicbrainz\seed;


class Medium extends Element
{
    protected $fields = ['format', 'name']; //position is a valid field in the documentation, but gives error
    public $format;
    public $position;
    public $name;
    /**
     * @var Track[]
     */
    public $tracks;

    public function __construct($args)
    {
        if(!empty($args['position']))
            $this->position = $args['position'];
        $this->register_fields($args);
    }

    /**
     * @param $args
     * @return Track
     */
    public function track($args)
    {
        $track = new Track($args);
        $this->tracks[] = $track;
        return $track;
    }

    public function save(string $prefix)
    {
        $data = parent::save($prefix);
        foreach($this->tracks as $key=>$track)
        {
            $data += $track->save(sprintf('%strack.%d.', $prefix, $key));
        }
        return $data;
    }
}