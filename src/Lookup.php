<?php

namespace datagutten\musicbrainz;

use datagutten\musicbrainz\seed as entity;

class Lookup extends musicbrainz
{
    public function label($mbid, $inc = ['releases'], $limit = 25): entity\Label
    {
        $data = $this->lookup('label', $mbid, $inc, $limit);
        return new entity\Label($data);
    }

    /**
     * Get release from MBID
     * @param string $mbid Release MBID
     * @param string[] $inc Include fields (artists, collections, labels, recordings or release-groups)
     * @return entity\Release Release object
     * @throws exceptions\NotFound Release not found
     * @throws exceptions\MusicBrainzErrorException Error from MusicBrainz API
     */
    public function release(string $mbid, array $inc = ['artists']): entity\Release
    {
        $data = $this->lookup('release', $mbid, $inc);
        return new entity\Release($data);
    }
}
