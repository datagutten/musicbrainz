<?php


namespace datagutten\musicbrainz\AcoustID;


use datagutten\musicbrainz\exceptions\AcoustIdException;
use datagutten\musicbrainz\exceptions\NotFound;
use FileNotFoundException;
use WpOrg\Requests;

class AcoustID
{
    /**
     * @var Requests\Session
     */
    private Requests\Session $session;
    /**
     * @var string
     */
    private string $client_key;
    /**
     * @var string
     */
    private string $user_key;
    private array $submissions = [];

    /**
     * @param string $client_key application's API key
     * @param string|null $user_key users API key
     */
    public function __construct(string $client_key, string $user_key = null)
    {
        $this->session = new Requests\Session('https://api.acoustid.org/v2/');
        $this->client_key = $client_key;
        if (!empty($user_key))
            $this->user_key = $user_key;
    }

    /**
     * @param Requests\Response $response
     * @return mixed
     * @throws AcoustIdException
     */
    protected static function handleResponse(Requests\Response $response): array
    {
        $data = json_decode($response->body, true);
        if (empty($data))
            throw new AcoustIdException('HTTP error: ' . $response->status_code);
        elseif ($data['status'] !== 'ok')
            throw new AcoustIdException($data['error']['message']);
        else
            return $data;
    }

    /**
     * Send a HTTP GET request
     * @param string $uri URI to get
     * @param array|null $query Query string to be appended to the URI
     * @return array
     * @throws AcoustIdException
     */
    private function get(string $uri, array $query = null): array
    {
        if (!empty($query))
            $query = http_build_query($query);
        try
        {
            $response = $this->session->get($uri . '?' . $query);
        }
            /** @noinspection PhpRedundantCatchClauseInspection */
        catch (Requests\Exception $e)
        {
            throw new AcoustIdException($e->getMessage(), 0, $e);
        }
        return self::handleResponse($response);
    }

    /**
     * Send a HTTP POST request
     * @throws AcoustIdException
     */
    protected function post(string $uri, array $data)
    {
        $response = $this->session->post($uri, [], $data);
        return self::handleResponse($response);
    }

    /**
     * Lookup a fingerprint
     * @param Fingerprint $fingerprint
     * @param string[] $meta Metadata to include in response (recordings, recordingids, releases, releaseids, releasegroups, releasegroupids, tracks, compress, usermeta, sources)
     * @return Track
     * @throws AcoustIdException Something went wrong
     * @throws NotFound Fingerprint was not found on AcoustID
     */
    public function lookup(Fingerprint $fingerprint, array $meta = ['recordings']): Track
    {
        $query = ['client' => $this->client_key, 'duration' => (int)$fingerprint->duration, 'fingerprint' => $fingerprint->fingerprint,
            'meta' => implode('+', $meta)];

        $response = $this->get('lookup', $query);
        if (empty($response['results']))
            throw new NotFound('Fingerprint was not found on AcoustID');
        $track = new Track($response['results'][0]);
        $track->fingerprint = $fingerprint;
        return $track;
    }

    /**
     * Lookup an array of fingerprints
     * @param Fingerprint[] $fingerprints
     * @param string[] $meta
     * @return Track[]
     * @throws AcoustIdException Something went wrong
     */
    public function lookupMulti(array $fingerprints, array $meta = ['recordings']): array
    {
        $tracks = [];
        foreach ($fingerprints as $fingerprint)
        {
            try
            {
                $tracks[] = $this->lookup($fingerprint);
            }
            catch (NotFound $e)
            {
                continue;
            }
        }
        return $tracks;
    }

    /**
     * Lookup a file on AcoustID
     * @param string $file File path
     * @param bool $single_result Return only best match
     * @return Track Track object
     * @throws FileNotFoundException
     * @throws AcoustIdException Something went wrong
     * @throws NotFound Fingerprint was not found on AcoustID
     */
    function lookup_file(string $file, bool $single_result = true): Track
    {
        if (!file_exists($file))
            throw new FileNotFoundException($file);
        $fingerprint = Fingerprint::fromFile($file);
        return $this->lookup($fingerprint);
    }

    public function addSubmission(Submission $submission)
    {
        $this->submissions[] = $submission;
    }

    /**
     * @param Submission[] $submissions
     */
    function submissionQuery(array $submissions = null): array
    {
        if (empty($submissions))
            $submissions = $this->submissions;

        $query = ['client' => $this->client_key, 'user' => $this->user_key];
        foreach ($submissions as $key => $submission)
        {
            $query += $submission->save($key);
        }
        return $query;
    }

    /**
     * Submit fingerprints and metadata
     * @return array Submission status
     * @throws AcoustIdException
     */
    public function submit(): array
    {
        $query = $this->submissionQuery($this->submissions);
        return $this->post('https://api.acoustid.org/v2/submit', $query);
    }
}