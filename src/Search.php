<?php


namespace datagutten\musicbrainz;


use datagutten\musicbrainz\seed as entity;

class Search extends musicbrainz
{
    protected static function build_query(array $args): string
    {
        $query = '';
        foreach ($args as $field => $value)
        {
            if ($field == 'limit')
            {
                $limit = $value;
                continue;
            }
            if (!empty($query))
                $query .= ' AND';
            $query .= sprintf('%s:%s', $field, $value);
        }
        $args = ['query' => $query, 'limit' => $limit ?? null];

        return http_build_query($args);
    }

    /**
     * @param $query
     * @return mixed|void
     * @throws exceptions\MusicBrainzErrorException Error from MusicBrainz
     * @throws exceptions\NotFound No results
     */
    function artist($query)
    {
        $response = $this->api_request(sprintf('/artist/?query="%s"', urlencode($query)), true);
        foreach ($response['artists'] as $artist)
        {
            if ($artist['name'] == $query)
                return $artist;
        }
    }

    /**
     * @param ...$args
     * @return entity\Release[]
     * @throws exceptions\MusicBrainzErrorException
     * @throws exceptions\NotFound
     */
    public function release(...$args): array
    {
        $query = self::build_query($args);
        if (!empty($limit))
            $query .= '&limit=' . $limit;
        $response = $this->api_request(sprintf('/release/?%s', self::build_query($args)), true);
        $releases = [];
        foreach ($response['releases'] as $release)
        {
            $releases[] = new entity\Release($release);
        }
        return $releases;
    }
}