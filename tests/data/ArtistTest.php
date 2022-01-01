<?php

namespace datagutten\musicbrainz_tests\data;

use datagutten\musicbrainz\exceptions;
use datagutten\musicbrainz\musicbrainz;
use datagutten\musicbrainz\seed;
use PHPUnit\Framework\TestCase;

class ArtistTest extends TestCase
{
    private musicbrainz $mb;

    protected function setUp(): void
    {
        $this->mb = new musicbrainz();
    }

    /**
     * @throws exceptions\MusicBrainzErrorException
     * @throws exceptions\NotFound
     */
    public function testURLRelations()
    {
        $artist = $this->mb->artistFromMBID('6789067f-1501-4741-bc32-4a2dce824894', ['url-rels']);
        $this->assertIsArray($artist->relations);
        $this->assertInstanceOf(seed\Relation::class, $artist->relations[0]);
        $this->assertInstanceOf(seed\URL::class, $artist->relations[0]->target);
    }

    /**
     * @throws exceptions\MusicBrainzErrorException
     * @throws exceptions\NotFound
     */
    public function testArtistRelations()
    {
        $artist = $this->mb->artistFromMBID('6789067f-1501-4741-bc32-4a2dce824894', ['artist-rels']);
        $this->assertIsArray($artist->relations);
        $this->assertInstanceOf(seed\Relation::class, $artist->relations[0]);
        $this->assertInstanceOf(seed\Artist::class, $artist->relations[0]->target);
    }
}
