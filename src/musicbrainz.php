<?php


namespace datagutten\musicbrainz;


use Composer\InstalledVersions;
use datagutten\musicbrainz\exceptions\NotFound;
use datagutten\musicbrainz\objects\Recording;
use datagutten\musicbrainz\seed\Artist;
use datagutten\tools\files\files;
use DOMDocumentCustom;
use InvalidArgumentException;
use Requests_Auth_Digest;
use Requests_Exception;
use Requests_Response;
use Requests_Session;
use SimpleXMLElement;

class musicbrainz
{
	public $error;
	public $last_request_time;
	public $depend;
    /**
     * @var Requests_Session
     */
	public $session;
    /**
     * @var string Folder for ISRC cache files
     */
	public $isrc_cache_folder;
    /**
     * @var string
     */
    public $version;

    function __construct($config=['isrc_cache_folder'=>''])
	{
		$this->version = InstalledVersions::getVersion('datagutten/musicbrainz');
        $this->session = new Requests_Session(
            'https://musicbrainz.org/ws/2',
            array(),
            array('useragent'=>sprintf('MusicBrainz PHP class/%s ( https://github.com/datagutten/musicbrainz )', $this->version)));
        if(!empty($config['isrc_cache_folder']))
            $this->isrc_cache_folder = $config['isrc_cache_folder'];
        else
            $this->isrc_cache_folder = files::path_join(__DIR__, 'cache', 'ISRC');

        if(!file_exists($this->isrc_cache_folder))
        {
            $status = @mkdir($this->isrc_cache_folder, 0777, true);
            if($status===false)
                printf('Unable to create ISRC cache folder at %s', $this->isrc_cache_folder);
        }
	}

    /**
     * Do a HTTP GET to MusicBrainz
     * @param string $url URL
     * @return Requests_Response
     */
    protected function get(string $url): Requests_Response
    {
        if($this->last_request_time!==false) //Do not sleep on first execution
        {
            $diff=microtime(true)-$this->last_request_time;
		    if($diff<1)
				time_sleep_until($this->last_request_time+1);
        }
        $response = $this->session->get($url);
        $this->last_request_time=microtime(true);
		return $response;
    }

    /**
     * @param string $uri URI
     * @param bool $json Fetch as json
     * @return array|SimpleXMLElement Returns array if $json=true
     * @throws exceptions\MusicBrainzErrorException Error from MusicBrainz
     * @throws exceptions\NotFound Query returned HTTP 404
     */
    public function api_request(string $uri, bool $json = false)
    {
        $url = 'https://musicbrainz.org/ws/2' . $uri;
        if ($json)
            $url .= '&fmt=json';
        return self::handle_response($this->get($url));
    }

    /**
     * Handle response from MusicBrainz
     * @param Requests_Response $response
     * @return array|SimpleXMLElement
     * @throws exceptions\MusicBrainzErrorException Error from MusicBrainz
     * @throws exceptions\NotFound Query returned HTTP 404
     */
	public static function handle_response(Requests_Response $response)
    {
        if($response->body[0]=='{')
        {
            $data = json_decode($response->body, true);
            if (!empty($data['error']))
                if ($response->status_code == 404)
                    throw new exceptions\NotFound($data['error']);
                else
                    throw new exceptions\MusicBrainzErrorException($data['error'], $response);
            else
                return $data;
        }
        elseif (str_starts_with($response->body, '<?xml'))
        {
            $data = simplexml_load_string($response->body);

            if (!empty($data->{'text'}))
                throw new exceptions\MusicBrainzErrorException($data->{'text'}[0], $response);
            else
                return $data;
        }
        else
            throw new exceptions\MusicBrainzErrorException("Unknown response format", $response);
    }

    /**
     * Get object from MusicBrainz API
     * @param string $entity Entity
     * @param string $value Query value (MBID)
     * @param array $inc Include fields
     * @return array
     * @throws NotFound Query returned HTTP 404
     * @throws exceptions\MusicBrainzErrorException Error from MusicBrainz API
     */
    protected function lookup(string $entity, string $value, array $inc): array
    {
        return $this->api_request(sprintf('/%s/%s?inc=%s', $entity, $value,
            implode('+', $inc)), true);
    }

    /**
     * Get recording from MBID
     * @param string $mbid Recording MBID
     * @param string[] $inc Include fields (artists, releases, isrcs or url-rels)
     * @return Recording Recording object
     * @throws exceptions\MusicBrainzErrorException Error from MusicBrainz
     * @throws exceptions\NotFound Recording not found
     */
    public function recordingFromMBID(string $mbid, array $inc = ['artists']): Recording
    {
        $data = $this->lookup('recording', $mbid, $inc);
        return new Recording($data);
    }

    /**
     * Find recordings by ISRC
     * @param string $isrc ISRC to find
     * @param string[] $inc Include fields (artists, releases, isrcs or url-rels)
     * @return Recording[] Array with recording objects
     * @throws exceptions\MusicBrainzErrorException Error from MusicBrainz API
     * @throws exceptions\NotFound Recording not found
     */
    public function recordingsFromISRC(string $isrc, array $inc = ['artists']): array
    {
        $recordings = [];
        $data = $this->lookup('isrc', $isrc, $inc);
        if (empty($data['recordings']))
            throw new NotFound('No recordings found for ISRC ' . $isrc);
        foreach ($data['recordings'] as $recording)
        {
            $recordings[] = new Recording($recording);
        }
        return $recordings;
    }

    /**
     * Get artist from MBID
     * @param string $mbid Artist MBID
     * @param string[] $inc Include fields (recordings, releases, release-groups or works)
     * @return Artist Artist object
     * @throws NotFound Artist not found
     * @throws exceptions\MusicBrainzErrorException Error from MusicBrainz API
     */
    public function artistFromMBID(string $mbid, array $inc = ['releases']): Artist
    {
        $data = $this->lookup('artist', $mbid, $inc);
        return new Artist($data);
    }

    /**
     * Find recording by ISRC
     * @param string $isrc ISRC to find
     * @param string $inc Include fields (artists, releases, isrcs or url-rels)
     * @return array
     * @throws exceptions\MusicBrainzErrorException Error from MusicBrainz
     * @throws exceptions\NotFound Recording not found
     * @deprecated Use recordingsFromISRC
     */
    function lookup_isrc(string $isrc, string $inc = 'releases'): array
    {
        return $this->api_request(sprintf('/isrc/%s?inc=%s', $isrc, $inc), true);
    }

    /**
     * Find recording by ISRC and cache the result
     * @param string $isrc ISRC to find
     * @return Recording[] Array with recording objects
     * @throws exceptions\MusicBrainzErrorException Error from MusicBrainz API
     * @throws exceptions\NotFound Recording not found
     */
    function lookup_isrc_cache(string $isrc): array
    {
        $recordings = [];
        $cache_file = files::path_join($this->isrc_cache_folder, $isrc . '.json');
        if (file_exists($cache_file))
            $data = json_decode(file_get_contents($cache_file), true);
        else
        {
            $data = $this->lookup('isrc', $isrc, ['releases']);
            if (empty($data['recordings']))
                throw new NotFound('No recordings found for ISRC ' . $isrc);
            file_put_contents($cache_file, json_encode($data));
        }

        foreach ($data['recordings'] as $recording)
        {
            $recordings[] = new Recording($recording);
        }
        return $recordings;
    }

    /**
     * Get release information
     * @param string|array $id_or_metadata ID string or metadata array
     * @param string $include
     * @param bool $json Fetch as json
     * @return array|SimpleXMLElement Return SimpleXmlElement if $json=false
     * @throws exceptions\MusicBrainzErrorException Error from MusicBrainz
     * @throws exceptions\NotFound Query returned HTTP 404
     */
	function getrelease($id_or_metadata,$include='artist-credits+labels+discids+recordings+tags+media+label-rels', $json=false)
	{
		if(is_string($id_or_metadata))
			$id=$id_or_metadata;
		elseif(is_array($id_or_metadata) && empty($id_or_metadata['MUSICBRAINZ_ALBUMID']))
		{
            throw new InvalidArgumentException('Tag MUSICBRAINZ_ALBUMID not set');
	   	}
		elseif(!empty($id_or_metadata['MUSICBRAINZ_ALBUMID']))
			$id=$id_or_metadata['MUSICBRAINZ_ALBUMID'];
		else
		{
			throw new InvalidArgumentException('Parameter has invalid data type: '.gettype($id_or_metadata));
		}

		return $this->api_request('/release/'.$id.'?inc='.$include, $json);
	}

    /**
     * Find the first flac file in a folder
     * @param string $dir Directory to search
     * @return string File name
     * @throws exceptions\MusicBrainzException File not found
     */
	public static function firstfile(string $dir): string
    {
		$file=glob(sprintf('/%s/01*.flac',$dir));
		if(!empty($file))
			return $file[0];
		elseif(!empty($file=glob(sprintf('/%s/1-01*.flac',$dir))))
			return $file[0];
		else
            throw new exceptions\MusicBrainzException('Unable to find first file in '.$dir);
	}

    /**
     * Get tags
     * @param SimpleXMLElement $xml
     * @return array
     */
	function tags(SimpleXMLElement $xml): array
    {
		if(empty($xml->{'release'}->{'artist-credit'}->{'name-credit'}->artist->{'tag-list'}))
			return array();
		foreach($xml->{'release'}->{'artist-credit'}->{'name-credit'}->artist->{'tag-list'}->tag as $tag)
		{
			if($tag->attributes()['count']<1)
				continue;
			$tags[]=(string)$tag->{'name'};
		}
		if(!isset($tags))
			return array();
		return $tags;
		//TODO: Check tags for duplicate words (Split by - or space)
	}

    /**
     * Build ISRC list from array
     * @param array $array Array with id as key and ISRC as value
     * @return string
     */
    public static function build_isrc_list_array(array $array): string
    {
        $dom=new DOMDocumentCustom;
        $dom->formatOutput=true;

        $metadata=$dom->createElement_simple('metadata',false,array('xmlns'=>'http://musicbrainz.org/ns/mmd-2.0#'));
        $recording_list=$dom->createElement_simple('recording-list',$metadata);

        foreach($array as $recording_id=>$isrc)
        {
            $recording=$dom->createElement_simple('recording',$recording_list,array('id'=>$recording_id));
            $isrc_list=$dom->createElement_simple('isrc-list',$recording,array('count'=>'1'));
            $dom->createElement_simple('isrc',$isrc_list,array('id'=>$isrc));
        }
        return $dom->saveXML($metadata);
    }

    /**
     * Build XML for ISRC submission
     * @param array $isrc_tracks Array with track numbers as keys and ISRCs as values
     * @param SimpleXMLElement $release Return of getrelease() with 'recordings' as second parameter
     * @return string
     * @throws exceptions\MusicBrainzException
     */
	public static function build_isrc_list(array $isrc_tracks, SimpleXMLElement $release): string
    {
		$dom=new DOMDocumentCustom;
		$dom->formatOutput=true;
		if(!is_object($release))
		{
			throw new InvalidArgumentException('$release is not object');
		}
		if(empty($release->{'release'}->{'medium-list'}))
		{
            throw new InvalidArgumentException('$release does not contain mediums');
		}
		$metadata=$dom->createElement_simple('metadata',false,array('xmlns'=>'http://musicbrainz.org/ns/mmd-2.0#'));
		$recording_list=$dom->createElement_simple('recording-list',$metadata);
		$medium_number=1;
		foreach($release->{'release'}->{'medium-list'}->medium as $medium)
		{
			foreach($medium->{'track-list'}->{'track'} as $track)
			{
				$recording_id=(string)$track->{'recording'}->attributes()['id'];
				$track_number=$medium_number.'-'.$track->{'position'};
				$recording=$dom->createElement_simple('recording',$recording_list,array('id'=>$recording_id));
				$isrc_list=$dom->createElement_simple('isrc-list',$recording,array('count'=>'1'));
				if(!isset($isrc_tracks[$track_number]))
				{
					throw new exceptions\MusicBrainzException(sprintf('Track count mismatch, track %s not found',$track_number));
				}
				$isrc=$isrc_tracks[$track_number];
				$dom->createElement_simple('isrc',$isrc_list,array('id'=>$isrc));
			}
			$medium_number++;
		}

		return $dom->saveXML($metadata);
	}

    /**
     * Submit ISRCs for a release
     * @param string $xml XML string returned by build_isrc_list()
     * @param string $client Client string
     * @return array Response from MusicBrainz
     * @throws exceptions\MusicBrainzErrorException
     * @throws exceptions\MusicBrainzException
     */
	function send_isrc_list(string $xml, $client='')
	{
	    if(empty($client))
            $client = 'datagutten-musicbrainz-'.$this->version;
	    $client = urlencode($client);

		$config = require 'config.php';
        try
        {
            $options = array('auth' => new Requests_Auth_Digest(array($config['mb_username'], $config['mb_password'])));
            $response = $this->session->post('/ws/2/recording/?client=' . $client . '&fmt=json', array('Content-Type' => 'text/xml'), $xml, $options);
        }
        catch (Requests_Exception $e)
        {
            throw new exceptions\MusicBrainzException($e->getMessage(), 0, $e);
        }

        return $this->handle_response($response);
    }

    /**
     * Get release by barcode
     * @param string $barcode Release barcode
     * @return string Release MBID
     * @throws exceptions\MusicBrainzErrorException Error from MusicBrainz
     * @throws exceptions\NotFound Release not found
     */
    function release_by_barcode(string $barcode): string
    {
        $result = $this->api_request('/release?query=' . $barcode, true);
        if ($result['count'] == 1)
        {
            $release = $result['releases'][0];
            if ($release['barcode'] == $barcode || $release['barcode'] == '0' . $barcode)
                return $release['id'];
        }
        else
        {
            foreach ($result['releases'] as $release)
            {
                if ($release['barcode'] == $barcode)
                    return $release['id'];
            }
        }

        throw new exceptions\NotFound('No release found with barcode ' . $barcode);
    }
}
