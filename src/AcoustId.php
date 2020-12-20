<?php


namespace datagutten\musicbrainz;


use FileNotFoundException;

class AcoustId
{
	/**
	 * @var string API key
	 */
	public $api_key;
	public $last_request_time;
	public $number_of_requests=0;
	public $ch;
	function __construct($api_key)
	{
		$this->api_key = $api_key;
		$this->ch=curl_init();
		curl_setopt($this->ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 5);
	}

    /**
     * Lookup a fingerprint
     * @param string $fingerprint Fingerprint
     * @param int $duration Duration
     * @return array
     * @throws exceptions\AcoustIdException AcoustId returned error
     */
	function lookup(string $fingerprint, int $duration): array
    {
		$url=sprintf('http://api.acoustid.org/v2/lookup?format=json&client=%s&duration=%d&fingerprint=%s&meta=recordingids',$this->api_key,$duration,$fingerprint);
		//var_dump($url);
		curl_setopt($this->ch,CURLOPT_URL,$url);
		if($this->number_of_requests==3 && $this->last_request_time==time())
		{
			sleep(1);
			$this->number_of_requests=0;
		}
		/*$response = Requests::get($url);
		$response->throw_for_status();*/
		$info=json_decode(curl_exec($this->ch),true);

		$this->last_request_time=time();
		$this->number_of_requests++;

		if($info['status']!='ok')
		{
			throw new exceptions\AcoustIdException('AcoustId returned error: '.$info['error']['message']);
		}
		return $info;
	}

	/**
	 * Calculate the fingerprint for a file
	 * @param $file
	 * @return array
	 */
	public static function fingerprint($file): array
    {
		$info=shell_exec(sprintf('fpcalc -json "%s"',$file));
		$info=json_decode($info,true);
		return $info;
	}

    /**
     * Lookup a file on AcoustID
     * @param string $file File path
     * @param bool $single_result Return only best match
     * @return array
     * @throws FileNotFoundException
     * @throws exceptions\AcoustIdException
     */
	function lookup_file(string $file, $single_result = true): array
    {
		if(!file_exists($file))
			throw new FileNotFoundException($file);
		$info=self::fingerprint($file);
		$result=$this->lookup($info['fingerprint'],$info['duration']);
		if(!$single_result)
		    return $result;
		elseif(!empty($result['results']))
			return $result['results'][0];
		else
			return [];
	}
}