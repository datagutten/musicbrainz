<?Php
require_once 'tools/DOMDocument_createElement_simple.php';
require_once 'tools/dependcheck.php';
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
		$this->depend=new dependcheck;
	}
	function api_request($uri)
	{
		curl_setopt($this->ch,CURLOPT_URL,'https://musicbrainz.org/ws/2'.$uri);

		if($this->last_request_time==time())
			sleep(1);

		$xml_string=curl_exec($this->ch);
		$this->last_request_time=time();
		if($xml_string===false)
		{
			$this->error=curl_error($this->ch);
			return false;
		}
		$xml=simplexml_load_string($xml_string);
		if($xml===false)
		{
			$this->error='Invalid XML';
			return false;
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

	function getrelease($id_or_metadata,$include='artist-credits+labels+discids+recordings+tags+media+label-rels')
	{
		if(is_string($id_or_metadata))
			$id=$id_or_metadata;
		elseif(!empty($id_or_metadata['MUSICBRAINZ_ALBUMID']))
			$id=$id_or_metadata['MUSICBRAINZ_ALBUMID'];
		else
		{
			throw new Exception('Parameter has invalid data type: '.gettype($id_or_metadata));
			return false;
		}

		//curl_setopt($this->ch,CURLOPT_URL,'http://musicbrainz.org/ws/2/release/'.$id.'?inc='.$include);
		$release=$this->api_request('/release/'.$id.'?inc='.$include);
		if($release===false)
			return false;
		return $release;
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
		$mediumkey=0;
		foreach($release->release->{'medium-list'}->medium as $medium)
		{
			if($mediumkey>0)
				die("Multi disc need testing");
			foreach($medium->{'track-list'}->track as $track)
			{
				$recording_id=(string)$track->recording->attributes()['id'];
				$position=(int)$track->position;

				$recording=$dom->createElement_simple('recording',$recording_list,array('id'=>$recording_id));
				$isrc_list=$dom->createElement_simple('isrc-list',$recording,array('count'=>'1'));
				if(!isset($isrc_tracks[$position]))
					die('Track count mismatch');
				$isrc=$isrc_tracks[$position];
				$dom->createElement_simple('isrc',$isrc_list,array('id'=>$isrc));

				//$recording[$mediumkey][$position]=$recording_id;
			}
			$mediumkey++;
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
		//Reset cURL
		curl_setopt($this->ch,CURLOPT_HTTPGET,true);
		curl_setopt($this->ch,CURLOPT_HTTPAUTH,false);

		return $return;
	}	
}
