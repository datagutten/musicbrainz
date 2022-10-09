<?php

namespace datagutten\musicbrainz_tests\seed;

use datagutten\musicbrainz\seed\Artist;
use datagutten\musicbrainz\seed\Track;
use PHPUnit\Framework\TestCase;

class TrackTest extends TestCase
{
    public function testSave()
    {
        $track = new Track(['id' => '8dda590c-54c0-4965-a202-208cf061ba0b', 'title' => 'Tordivelen og flua']);
        $artist = $track->artist(['id' => '61533837-6c9d-410d-aadf-f3b5d4a5234e', 'name' => 'Viggo Sandvik']);
        $this->assertInstanceOf(Artist::class, $artist);
        $seed = $track->save('test.');
        $this->assertEquals('61533837-6c9d-410d-aadf-f3b5d4a5234e', $seed['test.artist_credit.names.0.mbid']);
        $this->assertEquals('8dda590c-54c0-4965-a202-208cf061ba0b', $seed['test.recording']);
    }
}
