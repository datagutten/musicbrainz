<?php

namespace datagutten\musicbrainz;

use datagutten\musicbrainz\seed as entity;

class Browse extends musicbrainz
{
    /**
     * Get releases related to the specified entity
     * @return entity\Release[]
     */
    public function releases(string $entity, string $value, array $inc = [], int $limit=null): array
    {
        $result = $this->browse('release', $entity, $value, $limit, inc: $inc);
        $releases = [];
        foreach($result['releases'] as $release)
        {
            $releases[] = new entity\Release($release);
        }
        return $releases;
    }
}
