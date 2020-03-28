<?php


namespace datagutten\musicbrainz\exceptions;


use Exception;

class MusicBrainzException extends Exception
{
    public $response;
    public function __construct($message, $response, $code = 0, Exception $previous = null) {
        $this->response = $response;
        parent::__construct($message, $code, $previous);
    }
}