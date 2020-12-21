<?php


use datagutten\musicbrainz\exceptions\MusicBrainzErrorException;
use datagutten\musicbrainz\musicbrainz;
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
}
