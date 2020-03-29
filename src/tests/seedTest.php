<?php

namespace datagutten\musicbrainz\tests;

use datagutten\musicbrainz\seed;
use PHPUnit\Framework\TestCase;

class seedTest extends TestCase
{

    public function testArtistNoNum()
    {
        $this->assertEquals('Paul Gordon', seed::artistNoNum('Paul Gordon (7)'));
    }
}
