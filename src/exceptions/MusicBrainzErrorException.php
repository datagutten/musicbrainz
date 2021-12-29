<?php


namespace datagutten\musicbrainz\exceptions;


use Exception;
use WpOrg\Requests;

/**
 * MusicBrainz API returned error
 * @package datagutten\musicbrainz\exceptions
 */
class MusicBrainzErrorException extends MusicBrainzException
{
    public Requests\Response $response;
    public function __construct($message, $response, $code = 0, Exception $previous = null) {
        $this->response = $response;
        parent::__construct($message, $code, $previous);
    }
}