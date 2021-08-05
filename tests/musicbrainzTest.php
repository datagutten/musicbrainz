<?php


use datagutten\musicbrainz\exceptions\MusicBrainzErrorException;
use datagutten\musicbrainz\objects;
use datagutten\musicbrainz\seed;
use datagutten\tools\files\files;
use PHPUnit\Framework\TestCase;

class musicbrainzTest extends TestCase
{

    public function testLookup_isrc()
    {
        $mb = new datagutten\musicbrainz\musicbrainz();
        $data = $mb->lookup_isrc('NOUM70600600');
        $this->assertIsArray($data['recordings']);
        $this->assertSame('Det snør, det snør, tiddelibom', $data['recordings'][0]['title']);
    }

    public function testRecordingsFromISRC()
    {
        $mb = new datagutten\musicbrainz\musicbrainz();
        $data = $mb->recordingsFromISRC('NOUM70600600');
        $this->assertIsArray($data);
        $this->assertSame('Det snør, det snør, tiddelibom', $data[0]['title']);
        $this->assertInstanceOf(objects\Recording::class, $data[0]);
    }

    /**
     * @throws MusicBrainzErrorException
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
        $this->assertSame('Det snør, det snør, tiddelibom', $data['recordings'][0]['title']);
        $this->assertFileExists($isrc_cache_file);
        $data_cache = json_decode(file_get_contents($isrc_cache_file), true);
        $this->assertSame('Det snør, det snør, tiddelibom', $data_cache['recordings'][0]['title']);
    }

    public function testRecordingFromMBID()
    {
        $mb = new datagutten\musicbrainz\musicbrainz();
        $recording = $mb->recordingFromMBID('6a8acac2-14e1-4c97-8b4d-e52efe9f36ab');
        $this->assertInstanceOf(\datagutten\musicbrainz\objects\Recording::class, $recording);
        $this->assertEquals('Bam Bam', $recording->title);
        $this->assertEquals('King Charles', $recording->artists[0]->artist_name);
    }

    public function testArtistFromMBID()
    {
        $mb = new datagutten\musicbrainz\musicbrainz();
        $artist = $mb->artistFromMBID('fed809c7-6615-4d9f-8295-48ecec946e72');
        $this->assertInstanceOf(\datagutten\musicbrainz\seed\Artist::class, $artist);
        $this->assertInstanceOf(seed\Release::class, $artist->releases[0]);
        $this->assertEquals('King Charles', $artist->name);
    }
}
