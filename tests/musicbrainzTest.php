<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace datagutten\musicbrainz_tests;

use datagutten;
use datagutten\musicbrainz\exceptions;
use datagutten\musicbrainz\seed;
use datagutten\tools\files\files;
use PHPUnit\Framework\TestCase;

class musicbrainzTest extends TestCase
{
    public function testRecordingsFromISRC()
    {
        $mb = new datagutten\musicbrainz\musicbrainz();
        $data = $mb->recordingsFromISRC('NOUM70600600');
        $this->assertIsArray($data);
        $this->assertSame('Det snør, det snør, tiddelibom', $data[0]['title']);
        $this->assertInstanceOf(seed\Track::class, $data[0]);
    }

    /**
     * @throws exceptions\MusicBrainzErrorException|exceptions\NotFound
     * @requires PHPUnit >=9.5
     */
    public function testLookup_isrc_cache()
    {
        $mb = new datagutten\musicbrainz\musicbrainz();
        $mb->isrc_cache_folder = sys_get_temp_dir();
        $isrc_cache_file = files::path_join($mb->isrc_cache_folder, 'NOUM70600600.json');
        @unlink($isrc_cache_file);
        $this->assertFileDoesNotExist($isrc_cache_file);
        $data = $mb->lookup_isrc_cache('NOUM70600600');
        $this->assertSame('Det snør, det snør, tiddelibom', $data[0]['title']);
        $this->assertFileExists($isrc_cache_file);
        $data_cache = json_decode(file_get_contents($isrc_cache_file), true);
        $this->assertSame('Det snør, det snør, tiddelibom', $data_cache['recordings'][0]['title']);
    }

    public function testRecordingFromMBID()
    {
        $mb = new datagutten\musicbrainz\musicbrainz();
        $recording = $mb->recordingFromMBID('6a8acac2-14e1-4c97-8b4d-e52efe9f36ab');
        $this->assertInstanceOf(seed\Track::class, $recording);
        $this->assertEquals('Bam Bam', $recording->title);
        $this->assertEquals('King Charles', $recording->artists[0]->artist_name);
    }

    public function testArtistFromMBID()
    {
        $mb = new datagutten\musicbrainz\musicbrainz();
        $artist = $mb->artistFromMBID('fed809c7-6615-4d9f-8295-48ecec946e72');
        $this->assertInstanceOf(seed\Artist::class, $artist);
        $this->assertInstanceOf(seed\Release::class, $artist->releases[0]);
        $this->assertEquals('King Charles', $artist->name);
    }

    public function testReleaseFromMBID()
    {
        $mb = new datagutten\musicbrainz\musicbrainz();
        $release = $mb->releaseFromMBID('ae3c8989-1368-4f4f-a98d-8eb1cd94cd04', ['artists', 'recordings']);
        $this->assertInstanceOf(seed\Release::class, $release);
        $this->assertEquals('D\'e Glede', $release->title);
        $this->assertEquals('Intronasjonalen', $release->mediums[0]->tracks[0]->title);
        $this->assertEquals('9d9e6138-118e-41ea-8ec0-fdfe85f40e04', $release->mediums[0]->tracks[0]->id);
    }

    public function testReleaseLinks()
    {
        $mb = new datagutten\musicbrainz\musicbrainz();
        $release = $mb->releaseFromMBID('4f8b5b4d-4afa-4906-8ad9-4173c59f61eb', ['url-rels']);
        $this->assertInstanceOf(seed\Release::class, $release);
        $this->assertNotEmpty($release->urls);
        $this->assertInstanceOf(seed\URL::class, $release->urls[0]);
    }

    public function testReleaseLinks2()
    {
        $mb = new datagutten\musicbrainz\musicbrainz();
        $release = $mb->releaseFromMBID('b042ebf3-f3d0-4557-abd5-b568fbdc85c4', ['url-rels', 'release-group-rels', 'release-group-level-rels']);
        $this->assertInstanceOf(seed\Release::class, $release);
        $this->assertNotEmpty($release->urls);
        $this->assertInstanceOf(seed\URL::class, $release->urls[0]);
    }

    public function testDownloadFavicon()
    {
        $mb = new datagutten\musicbrainz\musicbrainz();
        $files = $mb->download_favicons(sys_get_temp_dir());
        $this->assertArrayHasKey('tidal', $files);
        $this->assertFileExists($files['tidal']);
    }

    public function testExternalLinks()
    {
        $mb = new datagutten\musicbrainz\musicbrainz();
        $links = $mb->get_links(new seed\Release(['id' => '896b6786-080f-44ac-bd18-fbdbee058cc3']));
        $this->assertIsArray($links);
        foreach ($links as $link)
        {
            $this->assertArrayHasKey('url', $link);
            if ($link['icon'] == 'applemusic')
                $this->assertEquals('https://music.apple.com/no/album/1443392780', $link['url']);
            elseif ($link['icon'] == 'tidal')
                $this->assertEquals('https://tidal.com/album/1315017', $link['url']);
        }
    }

    public function testReleaseRelation()
    {
        $mb = new datagutten\musicbrainz\musicbrainz();
        $release = $mb->releaseFromMBID('99ed91bd-1c6b-457a-9cdb-041c3f74468d', ['url-rels', 'recording-rels', 'artist-rels', 'artists', 'series-rels']);
        $this->assertIsArray($release->relations);
        $this->assertInstanceOf(seed\Artist::class, $release->relations[0]->target);
        $this->assertEquals('Giert Clausen', $release->relations[0]->target->name);
    }

    public function testBadISRCCacheFolder()
    {
        if (PHP_OS == 'WINNT')
            $this->markTestSkipped();

        $this->expectOutputString('Unable to create ISRC cache folder at /dev/null/foo');
        new datagutten\musicbrainz\musicbrainz(['isrc_cache_folder' => '/dev/null/foo']);
    }

    public function testLimit()
    {
        $mb = new datagutten\musicbrainz\Browse();
        $releases = $mb->releases('label', '36a73024-201f-4833-87b1-275bc609383e', limit: 100);
        $this->assertGreaterThan(25, count($releases));
    }
}
