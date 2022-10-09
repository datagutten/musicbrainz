<?php

namespace datagutten\musicbrainz_tests;

use datagutten\musicbrainz\Search;
use datagutten\musicbrainz\seed\Release;
use PHPUnit\Framework\TestCase;

class SearchTest extends TestCase
{

    /*public function testArtist()
    {

    }*/

    public function testRelease()
    {
        $search = new Search();
        $release = $search->release(catno: 'NACD090')[0];
        $this->assertInstanceOf(Release::class, $release);
        $this->assertEquals('Brøllopet i Kanaan', $release->title);
    }
}
