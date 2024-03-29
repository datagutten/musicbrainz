<?php


namespace datagutten\musicbrainz\seed;


use DateTime;

class Release extends Element
{
    public static string $entity = 'release';
    protected array $fields = ['id', 'title', 'type', 'status', 'script', 'packaging', 'edit_note', 'redirect_url', 'barcode'];

    /**
     * @var string Release MBID
     */
    public string $id;

    /**
     * @var string Release name
     */
    public string $title;
    public string $type;
    public string $status;
    public string $script;
    public string $packaging;
    public string $action = 'https://musicbrainz.org/release/add';
    public string $barcode;

    /**
     * @var Event[] Release events
     */
    public array $events = [];

    /**
     * @var Artist[] Release artists
     */
    public array $artists = [];

    /**
     * @var Label[] Release label
     */
    public array $labels = [];
    /**
     * @var URL[] Release URL
     */
    public array $urls = [];

    /**
     * @var Medium[] Release mediums containing tracks
     */
    public array $mediums = [];

    /**
     * @var string Edit note
     */
    public string $edit_note;
    /**
     * @var string Redirect URL
     */
    public string $redirect_uri;

    function __construct($args = [])
    {
        $this->register_fields($args);

        foreach ($args['artist-credit'] ?? [] as $artist)
        {
            $artist = [
                'artist_name' => $artist['artist']['name'],
                'id' => $artist['artist']['id'],
                'type' => $artist['artist']['type'] ?? null,
                'name' => $artist['name'],
                'disambiguation' => $artist['artist']['disambiguation'] ?? null,
                'sort_name' => $artist['artist']['sort-name'],
                'join_phrase' => $artist['joinphrase'] ?? null,
            ];
            $this->artist($artist);
        }

        foreach ($args['label-info'] ?? [] as $label)
        {
            $label['label']['catalog_number'] = $label['catalog-number'];
            $this->labels[] = new Label($label['label']);
        }

        foreach ($args['release-events'] ?? [] as $event)
        {
            $date = DateTime::createFromFormat('Y-m-d', $event['date']);
            if ($date !== false)
                $this->event($date);
        }

        foreach ($args['media'] ?? [] as $medium)
        {
            $this->medium($medium);
        }

        foreach ($args['relations'] ?? [] as $relation)
        {
            if ($relation['target-type'] == 'url')
                $this->url([
                    'id' => $relation['url']['id'],
                    'url' => $relation['url']['resource'],
                    'type' => $relation['type'],
                ]);
        }
    }

    public function artist($args): Artist
    {
        $artist = new Artist($args);
        $this->artists[] = $artist;
        return $artist;
    }

    public function event(DateTime $dateTime, $country=''): Event
    {
        $event = new Event($dateTime, $country);
        $this->events[] = $event;
        return $event;
    }

    public function label($args): Label
    {
        $label = new Label($args);
        $this->labels[] = $label;
        return $label;
    }

    public function url($args): URL
    {
        $url = new URL($args);
        $this->urls[] = $url;
        return $url;
    }

    public function medium($args): Medium
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

    public function save(string $prefix = ''): array
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