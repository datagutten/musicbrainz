<?Php
require_once 'vendor/autoload.php';
class musicbrainz
{
	public $ch;
	public $error;
	public $last_request_time;
	public $depend;
	function __construct()
	{
		$this->ch=curl_init();
		curl_setopt($this->ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($this->ch,CURLOPT_USERAGENT,'MusicBrainz PHP class/0.0.1 ( https://github.com/datagutten/musicbrainz )');
	}
	function api_request($uri)
	{
		curl_setopt($this->ch,CURLOPT_URL,'https://musicbrainz.org/ws/2'.$uri);
		$time=microtime(true);
		$diff=microtime(true)-$this->last_request_time;
		if($diff<1)
			time_sleep_until($this->last_request_time+1);

		$xml_string=curl_exec($this->ch);
		$this->last_request_time=microtime(true);
		if(substr($xml_string,0,1)=='{' && substr($xml_string,-1,1)=='}') //Server returned JSON
		{
			$this->error=print_r(json_decode($xml_string,true),true);
			return false;
		}
		if($xml_string===false)
		{
			$this->error=curl_error($this->ch);
			return false;
		}
		$xml=simplexml_load_string($xml_string);
		if(!empty($xml->body)) //Server returned HTML
		{
			$this->error=(string)$xml->body->p;
			return false;
		}
		if(empty($xml))
		{
			throw new Exception('Invalid XML: '.$xml_string);
		}
		if(!empty($xml->message))
		{
			$this->error="Musicbrainz returned message:\n".implode("\n",(array)$xml->message->text);
			return;
		}
		if(!empty($xml->text))
		{
			$this->error="Musicbrainz returned text:\n".implode("\n",(array)$xml->text);
			return false;
		}
		return $xml;
	}

    function api_request_json($uri)
    {
        $url='https://musicbrainz.org/ws/2'.$uri.'&fmt=json';
        $string=$this->get($url);
        if($string===false)
            return false;
        $data=json_decode($string,true);
        if(isset($data['error']))
        {
            //TODO: Add custom MusicBrainzException
            throw new Exception($data['error']);
        }
        return $data;
    }

	//Find track by ISRC
	function lookup_isrc($isrc,$inc='releases')
	{
		$xml=$this->api_request(sprintf('/isrc/%s?inc=%s',$isrc,$inc));
		if(isset($xml->text)/* && $xml->text[0]=='Not found'*/)
		{
			$this->error=sprintf('ISRC %s not found',$isrc);
			return false;
		}
		return $xml;
	}

	//Find track by ISRC and cache the result
	function lookup_isrc_cache($isrc)
	{
		$cachedir=__DIR__.'/isrc_cache';
		if(file_exists($cachefile=$cachedir.'/'.$isrc.'.xml'))
			return simplexml_load_file($cachefile);
		else
		{
			$xml=$this->lookup_isrc($isrc);
			//$xml=false;
			if($xml===false)
				return false;
			if(!file_exists($cachedir))
				mkdir($cachedir);
			$xml->asXML($cachefile);
			return $xml;
		}
	}

	function getrelease($id_or_metadata,$include='artist-credits+labels+discids+recordings+tags+media+label-rels', $json=false)
	{
		if(is_string($id_or_metadata))
			$id=$id_or_metadata;
		if(is_array($id_or_metadata) && empty($id_or_metadata['MUSICBRAINZ_ALBUMID']))
		{
            throw new InvalidArgumentException('Tag MUSICBRAINZ_ALBUMID not set');
	   	}
		elseif(!empty($id_or_metadata['MUSICBRAINZ_ALBUMID']))
			$id=$id_or_metadata['MUSICBRAINZ_ALBUMID'];
		else
		{
			throw new InvalidArgumentException('Parameter has invalid data type: '.gettype($id_or_metadata));
		}

        if($json)
            return $this->api_request_json('/release/'.$id.'?inc='.$include);
        else
            return $this->api_request('/release/'.$id.'?inc='.$include);
	}
	//Find the first flac file in a folder
	function firstfile($dir)
	{
		$file=glob(sprintf('/%s/01*.flac',$dir));
		if(!empty($file))
			return $file[0];
		elseif(!empty($file=glob(sprintf('/%s/1-01*.flac',$dir))))
			return $file[0];
		else
		{
			$this->error='Unable to find first file in '.$dir;
			return false;
		}
			
	}

	//Get metadata from a file using metaflac
	function metadata($file)
	{
		if(is_dir($file))
			$file=$this->firstfile($file);
		if(!is_file($file))
			return false;
		$metadata_raw=shell_exec(sprintf('metaflac --list "%s"',$file));
		preg_match_all('/comment\[[0-9]+\]\: ([A-Z\_]+)=(.+)/',$metadata_raw,$metadata_raw2);
		$metadata=array_combine($metadata_raw2[1],$metadata_raw2[2]);
		return $metadata;
	}

	function tags($xml)
	{
		if(empty($xml->release->{'artist-credit'}->{'name-credit'}->artist->{'tag-list'}))
			return false;
		foreach($xml->release->{'artist-credit'}->{'name-credit'}->artist->{'tag-list'}->tag as $tag)
		{
			//print_r($tag->attributes());
			//print_r($tag);
			if($tag->attributes()['count']<1)
				continue;
			$tags[]=(string)$tag->name;
		}
		if(!isset($tags))
			return false;
		return $tags;
		//MISSING: Check tags for duplicate words (Split by - or space)
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
	/* Submit ISRCs for a release
	Argument should be a XML string returned by build_isrc_list()
	*/
	function send_isrc_list($xml)
	{
		require 'config.php';
		curl_setopt($this->ch,CURLOPT_HTTPAUTH,CURLAUTH_DIGEST);
		curl_setopt($this->ch,CURLOPT_USERPWD,sprintf('%s:%s',$config['username'],$config['password']));
		curl_setopt($this->ch,CURLOPT_POSTFIELDS,$xml);
		//curl_setopt( $this->ch, CURLOPT_POST, true );

		curl_setopt($this->ch,CURLOPT_HTTPHEADER,array('Content-Type: text/xml'));
		$return=$this->api_request('/recording/?client=isrc.submit-0.0.1');
		if($return===false)
			$this->error='Error sending ISRC list to MusicBrainz: '.$this->error;
		//Reset cURL
		curl_setopt($this->ch,CURLOPT_HTTPGET,true);
		curl_setopt($this->ch,CURLOPT_HTTPAUTH,false);

		return $return;
	}	
}
