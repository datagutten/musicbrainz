<?php

namespace seed;

use datagutten\musicbrainz\seed\Artist;
use PHPUnit\Framework\TestCase;

class ArtistTest extends TestCase
{

    public function testSave()
    {
        $artist = new Artist(['mbid' => '710e234d-e1e2-4f30-89ec-01bd71f6e4a3', 'name' => 'OsloEss', 'artist_name' => 'Oslo Ess', 'join_phrase' => 'feat.']);
        $fields = $artist->save('artist.0.');
        $this->assertSame([
            'artist.0.mbid' => '710e234d-e1e2-4f30-89ec-01bd71f6e4a3',
            'artist.0.name' => 'OsloEss',
            'artist.0.artist_name' => 'Oslo Ess',
            'artist.0.join_phrase' => 'feat.'
        ], $fields);
    }

    public function testArtist()
    {
        $artist = new Artist(['mbid' => '710e234d-e1e2-4f30-89ec-01bd71f6e4a3', 'name' => 'OsloEss', 'artist_name' => 'Oslo Ess', 'join_phrase' => 'feat.']);
        $this->assertSame('710e234d-e1e2-4f30-89ec-01bd71f6e4a3', $artist->id);
        $this->assertSame('OsloEss', $artist->name);
        $this->assertSame('Oslo Ess', $artist->artist_name);
        $this->assertSame('feat.', $artist->join_phrase);
    }
}
