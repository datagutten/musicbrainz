<?php
/**
 * Created by PhpStorm.
 * User: abi
 * Date: 22.06.2019
 * Time: 17:45
 */

class MusicBrainzException extends Exception
{
    public function __construct($message, $code = 0, Exception $previous = null) {
        //$message = sprintf('File does not exist: %s', $file);
        parent::__construct($message, $code, $previous);
    }
}