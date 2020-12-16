<?php


namespace datagutten\musicbrainz\seed;


use DateTime;

class Release extends Element
{
    protected $fields = ['name', 'type', 'status', 'script', 'packaging'];
    public $data = [];

    public $name;
    public $type;
    public $status;
    public $script;
    public $packaging;
    public $action = 'https://musicbrainz.org/release/add';

    /**
     * @var Event[]
     */
    public $events = [];

    /**
     * @var Artist[]
     */
    public $artists = [];

    /**
     * @var Label[];
     */
    public $labels = [];
    /**
     * @var URL[]
     */
    public $urls = [];

    /**
     * @var Medium[]
     */
    public $mediums = [];

    function __construct($args = [])
    {
        $this->register_fields($args);
    }

    public function artist($args)
    {
        $artist = new Artist($args);
        $this->artists[] = $artist;
        return $artist;
    }

    public function event(DateTime $dateTime)
    {
        $event = new Event($dateTime);
        $this->events[] = $event;
        return $event;
    }

    public function label($args)
    {
        $label = new Label($args);
        $this->labels[] = $label;
        return $label;
    }

    public function url($args)
    {
        $url = new URL($args);
        $this->urls[] = $url;
        return $url;
    }

    public function medium($args)
    {
        $medium = new Medium($args);
        $this->mediums[] = $medium;
        return $medium;
    }

    /*    public function trackList()
        {
            if (empty($this->track_list))
            {
                $track_list = new TrackList();
                $this->track_list = $track_list;
                return $track_list;
            } else
                return $this->track_list;
        }*/

    public function save(string $prefix = '')
    {
        $output = parent::save($prefix);

        foreach ($this->urls as $key => $url)
        {
            $output += $url->save(sprintf('urls.%d.', $key));
        }

        foreach ($this->artists as $key => $artist)
        {
            $output += $artist->save(sprintf('artist_credit.names.%d.', $key));
        }

        foreach ($this->events as $key => $event)
        {
            $output += $event->save(sprintf('events.%d.', $key));
        }

        foreach ($this->labels as $key => $label)
        {
            $output += $label->save(sprintf('labels.%d.', $key));
        }

        foreach ($this->mediums as $key => $medium)
        {
            $output += $medium->save(sprintf('mediums.%d.', $key));
        }

        return $output;
    }
}