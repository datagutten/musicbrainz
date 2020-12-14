<?php


namespace datagutten\musicbrainz\seed;


class Utils
{

    /** Returns the name without the numerical suffix Discogs adds as disambiguation
     * ie. "ABC (123)" -> "ABC"
     * @param string $artist_name
     * @return string
     */
    public static function stripDiscogsNumber(string $artist_name)
    {
        return preg_replace('/ \(\d+\)$/', '', $artist_name);
    }
}