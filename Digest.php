<?php

use WpOrg\Requests;

/**
 * Digest Authentication provider
 *
 * @package Requests
 * @subpackage Authentication
 */

/**
 * Digest Authentication provider
 *
 * Provides a handler for Basic HTTP authentication via the Authorization
 * header.
 *
 * @package Requests
 * @subpackage Authentication
 */
class Requests_Auth_Digest implements Requests\Auth
{
    /**
     * Username
     *
     * @var string
     */
    public $user;

    /**
     * Password
     *
	 * @var string
	 */
	public $pass;
    /**
     * Request
     *
     * @var array
     */
    private array $request;
    private bool $response_sent = false;

    /**
     * Constructor
     *
     * @param array|null $args Array of user and password. Must have exactly two elements
     * @throws Requests\Exception On incorrect number of arguments (`authdigestbadargs`)
     */
	public function __construct(array $args = null)
    {
        if (is_array($args))
        {
            if (count($args) !== 2)
            {
                throw new Requests\Exception('Invalid number of arguments', 'authdigestbadargs');
            }

			list($this->user, $this->pass) = $args;
		}
	}

    /**
     * Register the necessary callbacks
     *
     * @param Requests\Hooks $hooks Hook system
     * @see fsockopen_header
     * @see curl_before_send
     */
	public function register(Requests\Hooks $hooks)
    {
        $hooks->register('curl.before_send', array(&$this, 'curl_before_send'));
        $hooks->register('fsockopen.after_request', array(&$this, 'fsockopen_request'));
        $hooks->register('requests.before_request', array(&$this, 'save_request'));
    }

    function save_request($url, $headers, $data, $type, $options)
    {
        $this->request = array('url'=>$url, 'headers'=>$headers, 'data'=>$data, 'type'=>$type, 'options'=>$options);
    }

	/**
	 * Set cURL parameters before the data is sent
	 *
	 * @param resource $handle cURL resource
	 */
	public function curl_before_send($handle) {
		curl_setopt($handle, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
		curl_setopt($handle, CURLOPT_USERPWD, $this->getAuthString());
	}

    /**
     * @param $response
     * @throws Exception
     */
	public function fsockopen_request(&$response)
    {
        if ($this->response_sent === true)
            return;
        preg_match('/WWW-Authenticate: Digest realm="(.+)", nonce="([a-z0-9]+)", qop="([a-z\-]+)", opaque="([a-z0-9]+)", algorithm=MD5, stale=FALSE/', $response, $matches);
        if(empty($matches))
            return;
        $uri = parse_url($this->request['url'], PHP_URL_PATH);
        $response_header = $this->calculate_response($matches[1], $matches[2], $matches[3], $matches[4], $this->request['type'], $uri);

        $transport = new Requests\Transport\fsockopen();

        $this->request['headers']['Authorization']=$response_header;
        $this->response_sent = true;
        $response = $transport->request($this->request['url'], $this->request['headers'], $this->request['data'], $this->request['options']);
    }

    /**
     * Generate the response
     * @param string $realm Realm from 401 response
     * @param string $nonce Nonce from 401 response
     * @param string $qop Qop from 401 response
     * @param string $opaque Opaque from 401 response
     * @param string $request_type Request method
     * @param string $uri URI
     * @return string
     */
    function calculate_response($realm, $nonce, $qop, $opaque, $request_type, $uri)
    {
        $a1 = md5(sprintf('%s:%s:%s', $this->user, $realm, $this->pass));
        $a2 = md5(sprintf('%s:%s', $request_type, $uri));

        $nc = '00000001';
        $cnonce = md5(uniqid());

        $digest_response = md5(sprintf('%s:%s:%s:%s:%s:%s', $a1, $nonce, $nc, $cnonce, $qop, $a2));

        return sprintf('Digest username="%s",realm="%s",nonce="%s",uri="%s",cnonce="%s",nc=%s,algorithm=MD5,response="%s",qop="%s",opaque="%s"',
            $this->user,
            $realm,
            $nonce,
            $uri,
            $cnonce,
            $nc,
            $digest_response,
            $qop,
            $opaque);
    }

	/**
	 * Get the authentication string (user:pass)
	 *
	 * @return string
	 */
	public function getAuthString() {
		return $this->user . ':' . $this->pass;
	}
}