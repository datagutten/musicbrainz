<?php

namespace datagutten\musicbrainz\AcoustID;

use datagutten\musicbrainz\exceptions\AcoustIdException;
use datagutten\tools\SimpleArrayAccess;
use FileNotFoundException;
use Symfony\Component\Process\Process;

class Fingerprint extends SimpleArrayAccess
{
    public float $duration;
    public string $fingerprint;

    public function __construct($fingerprint, $duration)
    {
        $this->fingerprint = $fingerprint;
        $this->duration = $duration;
    }

    /**
     * Get fingerprint from file
     * @param string $file File path
     * @return static Fingerprint object
     * @throws FileNotFoundException
     * @throws AcoustIdException Fingerprint extraction failed
     */
    public static function fromFile(string $file): Fingerprint
    {
        if (!file_exists($file))
            throw new FileNotFoundException($file);
        $process = new Process(['fpcalc', '-json', $file]);
        $status = $process->run();
        if ($status != 0)
        {
            $error = $process->getErrorOutput();
            throw new AcoustIdException($error);
        }
        $output = json_decode($process->getOutput(), true);
        return new static($output['fingerprint'], $output['duration']);
    }
}