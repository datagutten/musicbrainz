<?php

namespace datagutten\musicbrainz;

use datagutten\musicbrainz\exceptions\MusicBrainzException;

class CoverArt
{
    protected array $images;
    /**
     * @var string Release MBID
     */
    public string $mbid;

    public function __construct($images)
    {
        $this->mbid = musicbrainz::mbidFromURL($images['release']);
        $this->images = $images['images'];
    }

    /**
     * Get cover image
     * @param string $position Cover position
     * @param string $size Image size
     * @return string Image URL
     * @throws MusicBrainzException
     */
    public function image(string $position = 'Front', string $size = 'full'): string
    {
        foreach ($this->images as $image)
        {
            if (!in_array($position, $image['types']))
                continue;
            if ($size == 'full')
                return $image['image'];
            elseif (!empty($image['thumbnails'][$size]))
                return $image['thumbnails'][$size];
            else
            {
                $sizes = array_keys($image['thumbnails']);
                $sizes[] = 'full';
                throw new MusicBrainzException(sprintf('Invalid image size, valid sizes are: %s', implode(', ', $sizes)));
            }
        }
        throw new MusicBrainzException(sprintf('No image found for position "%s"', $position));
    }
}