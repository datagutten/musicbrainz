<?php

namespace datagutten\musicbrainz_tests;

use datagutten\musicbrainz\CoverArt;
use datagutten\musicbrainz\exceptions\MusicBrainzException;
use datagutten\musicbrainz\musicbrainz;
use PHPUnit\Framework\TestCase;


class CoverArtTest extends TestCase
{

    public function testCover()
    {
        $mb = new musicbrainz();
        $art = $mb->cover('989aaa74-e7d8-4f48-8ab7-febb058a0ac4');
        $this->assertInstanceOf(CoverArt::class, $art);
        $this->assertEquals('http://coverartarchive.org/release/989aaa74-e7d8-4f48-8ab7-febb058a0ac4/34369326133.png', $art->image());
    }

    public function testInvalidSize()
    {
        $mb = new musicbrainz();
        $art = $mb->cover('989aaa74-e7d8-4f48-8ab7-febb058a0ac4');
        $this->expectException(MusicBrainzException::class);
        $this->expectExceptionMessage('Invalid image size, valid sizes are: 1200, 250, 500, large, small, full');
        $art->image(size: 'bad');
    }

    public function testInvalidPosition()
    {
        $mb = new musicbrainz();
        $art = $mb->cover('989aaa74-e7d8-4f48-8ab7-febb058a0ac4');
        $this->expectException(MusicBrainzException::class);
        $this->expectExceptionMessage('No image found for position "bad"');
        $art->image('bad');
    }

    public function testNoCover()
    {
        $mb = new musicbrainz();
        $this->expectException(MusicBrainzException::class);
        $this->expectExceptionMessage('No cover art for MBID e047716e-d47d-4b96-a336-41c626e7743b');
        $mb->cover('e047716e-d47d-4b96-a336-41c626e7743b');
    }
}
