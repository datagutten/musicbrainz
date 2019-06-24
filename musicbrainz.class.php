<?Php
require_once 'vendor/autoload.php';
class musicbrainz
{
	public $ch;
	public $error;
	public $last_request_time;
	public $depend;
    /**
     * @var Requests_Session
     */
	public $session;
	public $version = '2.0';
	function __construct()
	{
		$this->ch=curl_init();
		curl_setopt($this->ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($this->ch,CURLOPT_USERAGENT,'MusicBrainz PHP class/0.0.1 ( https://github.com/datagutten/musicbrainz )');
        $this->session = new Requests_Session(
            'https://musicbrainz.org/ws/2',
            array(),
            array('useragent'=>sprintf('MusicBrainz PHP class/%s ( https://github.com/datagutten/musicbrainz )', $this->version)));
	}

    /**
     * Do a HTTP GET to MusicBrainz
     * @param string $url URL
     * @return Requests_Response
     * @throws Exception HTTP response code not 200
     */
    function get($url)
    {
        if($this->last_request_time!==false) //Do not sleep on first execution
        {
            $diff=microtime(true)-$this->last_request_time;
		    if($diff<1)
				time_sleep_until($this->last_request_time+1);
        }
        $response = $this->session->get($url);
        $this->last_request_time=microtime(true);
        if($response->status_code===200)
            return $response;
        else
            throw new Exception(sprintf('MusicBrainz returned code %d, body %s', $response->status_code, $response->body));
    }

    /**
     * @param string $uri URI
     * @param bool $json Fetch as json
     * @return array|SimpleXMLElement Returns array if $json=true
     * @throws MusicBrainzException
     * @throws Exception
     */
    function api_request($uri, $json=false)
    {
        $url='https://musicbrainz.org/ws/2'.$uri;
        if($json)
            $url.='&fmt=json';
        return $this->handle_response($this->get($url));
	}

    /**
     * Handle response from MusicBrainz
     * @param Requests_Response $response
     * @throws MusicBrainzException Error from MusicBrainz
     * @return array|SimpleXMLElement
     */
	public static function handle_response($response)
    {
        if($response->body[0]=='{')
        {
            $data = json_decode($response->body, true);
            if(!empty($data['error']))
                throw new MusicBrainzException($data['error'], $response);
            else
                return $data;
        }
        elseif(substr($response->body, 0, 5)==='<?xml')
        {
            $data = simplexml_load_string($response->body);

            if(!empty($data->{'text'}))
                throw new MusicBrainzException($data->{'text'}[0], $response);
            else
                return $data;
        }
        else
            throw new MusicBrainzException("Unknown response format", $response);
    }

    /**
     * Find recording by ISRC
     * @param string $isrc ISRC to find
     * @param string $inc
     * @return SimpleXMLElement
     * @throws MusicBrainzException
     */
	function lookup_isrc($isrc,$inc='releases')
	{
		return $this->api_request(sprintf('/isrc/%s?inc=%s',$isrc,$inc));
	}

    /**
     * Find recording by ISRC and cache the result
     * @param string $isrc ISRC to find
     * @return SimpleXMLElement
     * @throws MusicBrainzException
     */
	function lookup_isrc_cache($isrc)
	{
		$cache_dir=__DIR__.'/isrc_cache';
		if(file_exists($cache_file=$cache_dir.'/'.$isrc.'.xml'))
			return simplexml_load_file($cache_file);
		else
		{
			$xml=$this->lookup_isrc($isrc);
			if(!file_exists($cache_dir))
				mkdir($cache_dir);
			$xml->asXML($cache_file);
			return $xml;
		}
	}

    /**
     * Get release information
     * @param string|array $id_or_metadata ID string or metadata array
     * @param string $include
     * @param bool $json Fetch as json
     * @return array
     * @throws MusicBrainzException
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
     * @throws Exception File not found
     */
	public static function firstfile($dir)
	{
		$file=glob(sprintf('/%s/01*.flac',$dir));
		if(!empty($file))
			return $file[0];
		elseif(!empty($file=glob(sprintf('/%s/1-01*.flac',$dir))))
			return $file[0];
		else
            throw new Exception('Unable to find first file in '.$dir);
	}

    /**
     * Get tags
     * @param SimpleXMLElement $xml
     * @return array
     */
	function tags($xml)
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
    function build_isrc_list_array($array)
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

	/* Submit ISRCs for a release
	First argument should be an array with track numbers as keys and ISRCs as values
	Second argument should be the return of getrelease() with 'recordings' as second parameter
	*/
	function build_isrc_list($isrc_tracks,$release)
	{
		$dom=new DOMDocumentCustom;
		$dom->formatOutput=true;
		if(!is_object($release))
		{
			$this->error='$release is not object';
			return false;
		}
		if(empty($release->release->{'medium-list'}))
		{
			$this->error='$release does not contain mediums';
			return false;
		}
		$metadata=$dom->createElement_simple('metadata',false,array('xmlns'=>'http://musicbrainz.org/ns/mmd-2.0#'));
		$recording_list=$dom->createElement_simple('recording-list',$metadata);
		$medium_number=1;
		foreach($release->release->{'medium-list'}->medium as $medium)
		{
			foreach($medium->{'track-list'}->track as $track)
			{
				$recording_id=(string)$track->recording->attributes()['id'];
				$track_number=$medium_number.'-'.$track->position;
				$recording=$dom->createElement_simple('recording',$recording_list,array('id'=>$recording_id));
				$isrc_list=$dom->createElement_simple('isrc-list',$recording,array('count'=>'1'));
				if(!isset($isrc_tracks[$track_number]))
				{
					$this->error=sprintf('Track count mismatch, track %s not found',$track_number);
					return false;
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
     * @return array
     * @throws MusicBrainzException
     * @throws Requests_Exception
     */
	function send_isrc_list($xml)
	{
		$config = require 'config.php';
		$options = array('auth'=>new Requests_Auth_Digest(array($config['username'], $config['password'])));
        $response = $this->session->post('/ws/2/recording/?client=datagutten-musicbrainz-'.$this->version.'&fmt=json', array('Content-Type'=>'text/xml'), $xml, $options);
        return $this->handle_response($response);
	}
}
