<?php

namespace datagutten\musicbrainz_tests;

use datagutten\musicbrainz\seed;
use PHPUnit\Framework\TestCase;

class seedTest extends TestCase
{
    public function testArtistNoNum()
    {
        $this->assertEquals('Paul Gordon', seed\Utils::stripDiscogsNumber('Paul Gordon (7)'));
    }

    public function testTrack()
    {
        $track = new seed\Track(['name' => 'Gøy på landet']);
        $this->assertSame('Gøy på landet', $track->title);
        $array = $track->save('mediums.1.track.0.');
        $this->assertArrayHasKey('mediums.1.track.0.name', $array);
        $this->assertSame('Gøy på landet', $array['mediums.1.track.0.name']);
    }

    public function testTrackWithMBID()
    {
        $track = new seed\Track(['name' => 'Gøy på landet', 'id' => '6c9b42ca-a659-41e6-9db2-d2b2d3e72f0b']);
        $this->assertSame('Gøy på landet', $track->title);
        $array = $track->save('mediums.1.track.0.');
        $this->assertArrayHasKey('mediums.1.track.0.name', $array);
        $this->assertSame('Gøy på landet', $array['mediums.1.track.0.name']);
        $this->assertSame('6c9b42ca-a659-41e6-9db2-d2b2d3e72f0b', $array['mediums.1.track.0.recording']);
    }

    public function testTrackListTrack()
    {
        $release = new seed\Release(['name' => 'Du og jeg og vi to']);
        $medium = $release->medium([]);
        $track = $medium->track(['name' => 'Gøy på landet']);
        $track->length = 1200;
        $this->assertSame(1200, $medium->tracks[0]->length);
        $release_array = $release->save();
        $this->assertSame($release_array['name'], 'Du og jeg og vi to');
    }
}
