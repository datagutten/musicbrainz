<?php
/**
 * Created by PhpStorm.
 * User: abi
 * Date: 22.06.2019
 * Time: 17:45
 */

class MusicBrainzException extends Exception
{
    public $response;
    public function __construct($message, $response, $code = 0, Exception $previous = null) {
        $this->response = $response;
        parent::__construct($message, $code, $previous);
    }
}