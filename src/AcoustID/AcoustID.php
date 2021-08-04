<?php


namespace datagutten\musicbrainz\AcoustID;


use datagutten\musicbrainz\exceptions\AcoustIdException;
use FileNotFoundException;
use Requests_Exception;
use Requests_Response;
use Requests_Session;

class AcoustID
{
    /**
     * @var Requests_Session
     */
    private Requests_Session $session;
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
        $this->session = new Requests_Session('https://api.acoustid.org/v2/');
        $this->client_key = $client_key;
        if (!empty($user_key))
            $this->user_key = $user_key;
    }

    /**
     * @param Requests_Response $response
     * @return mixed
     * @throws AcoustIdException
     */
    protected static function handleResponse(Requests_Response $response): array
    {
        if(!$response->success)
            throw new AcoustIdException('HTTP error: '.$response->status_code);

        $data = json_decode($response->body, true);
        if ($data['status'] !== 'ok')
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
        catch (Requests_Exception $e)
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
     * @throws AcoustIdException
     */
    public function lookup(Fingerprint $fingerprint, array $meta = ['recordings']): Track
    {
        $query = ['client' => $this->client_key, 'duration' => (int)$fingerprint->duration, 'fingerprint' => $fingerprint->fingerprint,
            'meta' => implode('+', $meta)];

        $response = $this->get('lookup', $query);
        $track = new Track($response['results'][0]);
        $track->fingerprint = $fingerprint;
        return $track;
    }

    /**
     * Lookup a file on AcoustID
     * @param string $file File path
     * @param bool $single_result Return only best match
     * @return Track Track object
     * @throws FileNotFoundException
     * @throws AcoustIdException
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