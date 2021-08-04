<?php

namespace datagutten\musicbrainz\objects;

use datagutten\musicbrainz\seed\Artist;
use datagutten\musicbrainz\seed\Element;

class Recording extends Element
{
    /**
     * @var string Recording MBID
     */
    public string $mbid;
    /**
     * @var Artist[] Recording Artists
     */
    public array $artists = [];
    /**
     * @var string Recording title
     */
    public string $title;

    public static array $required_fields = ['id', 'artists'];
    public $fields = ['mbid', 'artists', 'length', 'title'];

    public function __construct($data)
    {
        $this->mbid = $data['mbid'] ?? $data['id'];
        if (isset($data['artists']))
        {
            foreach ($data['artists'] as $artist)
            {
                $this->artists[] = new Artist(['mbid' => $artist['id'], 'name' => $artist['name']]);
            }
        }
        elseif (isset($data['artist-credit']))
        {
            foreach ($data['artist-credit'] as $credit)
            {
                $this->artists[] = new Artist(
                    ['mbid' => $credit['artist']['id'],
                        'artist_name' => $credit['artist']['name'],
                        'join_phrase' => $credit['joinphrase']
                    ]);
            }
        }
        $this->title = $data['title'];
    }

    public static function fromAcoustId($data): static
    {
        return new static([
            'mbid' => $data['id'],
            'title' => $data['title'],
            'artists' => $data['artists'],
            'duration' => $data['duration'] ?? null,
        ]);
    }
}