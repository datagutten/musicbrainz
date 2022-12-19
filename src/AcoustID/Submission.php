<?php

namespace datagutten\musicbrainz\AcoustID;

use datagutten\tools\SimpleArrayAccess;
use FileNotFoundException;

class Submission extends SimpleArrayAccess
{
    /**
     * @var Fingerprint Fingerprint object
     */
    public Fingerprint $fingerprint;
    public static array $fields = ['duration', 'fingerprint', 'bitrate', 'fileformat', 'mbid', 'track', 'artist', 'album', 'albumartist', 'year', 'trackno', 'discno'];
    public static array $simple_fields = ['bitrate', 'fileformat', 'mbid', 'track', 'artist', 'album', 'albumartist', 'year', 'trackno', 'discno'];
    /**
     * @var int bitrate of the audio file
     */
    public int $bitrate;
    /**
     * @var string file format of the audio file
     */
    public string $fileformat;
    /**
     * @var string corresponding MusicBrainz recording ID
     */
    public string $mbid;
    /**
     * @var string track title
     */
    public string $track;
    /**
     * @var string track artist
     */
    public string $artist;
    /**
     * @var string album title
     */
    public string $album;
    /**
     * @var string album artist
     */
    public string $albumartist;
    /**
     * @var int album release year
     */
    public int $year;
    /**
     * @var int track number
     */
    public int $trackno;
    /**
     * @var int disc number
     */
    public int $discno;

    /**
     * Create submission object with fingerprint from audio file
     * @param string $file Audio file
     * @return Submission
     * @throws FileNotFoundException
     */
    public static function fromFile(string $file): Submission
    {
        $submission = new static();
        $submission->fingerprint = Fingerprint::fromFile($file);
        return $submission;
    }

    /**
     * Create a submission with metadata from TIDAL
     * @param \datagutten\Tidal\elements\Track $track
     * @return Submission
     */
    public static function fromTidal(\datagutten\Tidal\elements\Track $track): Submission
    {
        $submission = new static();
        $submission->track = $track->title;
        $submission->album = $track->album->title;
        $artists = [];
        foreach ($track->artists as $artist)
        {
            $artists[] = $artist->name; //TODO: Get relation
        }
        $submission->artist = implode(', ', $artists);
        $submission->year = (int)substr($track->album->releaseDate, 0, 4);
        $submission->trackno = $track->trackNumber;
        $submission->discno = $track->volumeNumber;

        return $submission;
    }

    public function save($key = 0): array
    {
        $query = [];
        $key = sprintf('.%d', $key);
        foreach ((array)$this as $field => $value)
        {
            $query[$field . $key] = $value;
        }

        $query['fingerprint' . $key] = $this->fingerprint->fingerprint;
        $query['duration' . $key] = (int)$this->fingerprint->duration;

        return $query;
    }
}