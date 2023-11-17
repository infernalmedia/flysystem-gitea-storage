<?php

namespace InfernalMedia\FlysystemGitea;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use InvalidArgumentException;
use OwenVoke\Gitea\Client as GiteaClient;
use Psr\Http\Message\StreamInterface;

/**
 * Class GiteaAdapter
 *
 * @package InfernalMedia\FlysystemGitea
 */
class Client
{
    const VERSION_URI = "/api/v1";

    /**
     * @var ?string
     */
    protected $personalAccessToken;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $repository;

    /**
     * @var string
     */
    protected $branch;

    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * Client constructor.
     *
     * @param  string  $projectId
     * @param  string  $branch
     * @param  string  $baseUrl
     * @param  string|null  $personalAccessToken
     */
    public function __construct(string $username, string $repository, string $branch, string $baseUrl, ?string $personalAccessToken = null)
    {
        $this->username = $username;
        $this->repository = $repository;
        $this->branch = $branch;
        $this->baseUrl = $baseUrl;
        $this->personalAccessToken = $personalAccessToken;
    }

    /**
     * @param $path
     *
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function readRaw(string $path): string
    {
        $path = urlencode($path);

        $response = $this->request('GET', "raw/$path");

        return $this->responseContents($response, false);
    }

    /**
     * @param $path
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function read($path)
    {
        $response = $this->request('GET', "contents/$path");
        $body = $response->getBody()->getContents();

        return json_decode($body, true);
    }

    /**
     * @param $path
     *
     * @return resource|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function readStream($path)
    {
        $path = urlencode($path);

        $response = $this->request('GET', "raw/$path");

        return $response->getBody()->detach();
    }

    /**
     * @param $sha
     *
     * @return mixed|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function blame($sha)
    {
        $response = $this->request('GET', "/git/commits/$sha");

        return $this->responseContents($response);
    }

    /**
     * @param  string  $path
     * @param  string  $contents
     * @param  string  $commitMessage
     * @param  bool  $override
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function upload(string $path, string $contents, string $commitMessage, $override = false): array
    {
        $path = urlencode($path);

        $method = $override ? 'PUT' : 'POST';
        $payload = [
            'content' => base64_encode($contents),
            'message' => $commitMessage,
            'signoff' => false,
        ];

        if ($override) {
            try {
                $existingFile = $this->read($path);
                if ($existingFile) {
                    $payload['sha'] = $existingFile['sha'];
                }
            } catch (GuzzleException $e) {
            }
        }

        $response = $this->request($method, "contents/$path", $payload);

        return $this->responseContents($response);
    }

    /**
     * @param  string  $path
     * @param $resource
     * @param  string  $commitMessage
     * @param  bool  $override
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function uploadStream(string $path, $resource, string $commitMessage, $override = false): array
    {
        if (!is_resource($resource)) {
            throw new InvalidArgumentException(sprintf(
                'Argument must be a valid resource type. %s given.',
                gettype($resource)
            ));
        }

        return $this->upload($path, stream_get_contents($resource), $commitMessage, $override);
    }

    /**
     * @param  string  $path
     * @param  string  $commitMessage
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function delete(string $path, string $commitMessage)
    {
        $path = urlencode($path);

        $payload = ['message' => $commitMessage];

        $existingFile = $this->read($path);

        if ($existingFile) {
            $payload['sha'] = $existingFile['sha'];
        }

        $this->request('DELETE', "contents/$path", $payload);
    }

    /**
     * @param  string|null  $directory
     * @param  bool  $recursive
     *
     * @return iterable
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function tree(string $directory = null, bool $recursive = false): iterable
    {
        if ($directory === '/' || $directory === '') {
            $directory = null;
        }

        if (!empty($directory)) {
            $recursive = true;
        }

        $page = 1;

        do {
            $response = $this->request('GET', 'git/trees/' . $this->getBranch(), [], [
                'query' => [
                    'recursive' => $recursive,
                    'limit'  => 100,
                    'page'      => $page++
                ]
            ]);

            $treeResponse = $this->responseContents($response)['tree'];

            if (!empty($directory)) {
                foreach ($treeResponse as $i => $item) {
                    $currentFilename = $item['path'];
                    if (!str_starts_with($currentFilename, $directory) || $currentFilename === $directory) {
                        unset($treeResponse[$i]);
                    }
                }
            }

            yield array_values($treeResponse);
        } while ($this->responseHasNextPage($response));
    }

    /**
     * @return string
     */
    public function getPersonalAccessToken(): string
    {
        return $this->personalAccessToken;
    }

    /**
     * @param  string  $personalAccessToken
     */
    public function setPersonalAccessToken(string $personalAccessToken)
    {
        $this->personalAccessToken = $personalAccessToken;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param  string  $username
     */
    public function setUsername(string $username)
    {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getRepository(): string
    {
        return $this->repository;
    }

    /**
     * @param  string  $repository
     */
    public function setRepository(string $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return string
     */
    public function getBranch(): string
    {
        return $this->branch;
    }

    /**
     * @param  string  $branch
     */
    public function setBranch(string $branch)
    {
        $this->branch = $branch;
    }

    /**
     * @param  string  $method
     * @param  string  $uri
     * @param  array  $params
     *
     * @return \GuzzleHttp\Psr7\Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function request(string $method, string $uri, array $body = [], array $params = []): Response
    {
        $queryParams = ['branch' => $this->branch];
        if (array_key_exists('query', $params)) {
            $queryParams = array_merge($queryParams, $params['query']);
        }

        // $uri = !in_array($method, ['POST', 'PUT', 'DELETE']) ? $this->buildUri($uri, $params['query']) : $this->buildUri($uri);
        $uri = $this->buildUri($uri, $queryParams);

        // $params = in_array($method, ['POST', 'PUT', 'DELETE']) ? array_merge(['branch' => $this->branch], $params) : [];
        $params['debug'] = false;
        if (in_array($method, ['POST', 'PUT', 'DELETE']) && !empty($body)) {
            $params[RequestOptions::JSON] = $body;
        }
        // $client = new GiteaClient(null, null, $this->baseUrl);
        // $client->authenticate($this->personalAccessToken, null, GiteaClient::AUTH_ACCESS_TOKEN);

        // return $client->getHttpClient()->send($method, $uri, $params);

        $container = [];
        $history = Middleware::history($container);

        $stack = HandlerStack::create();
        // Add the history middleware to the handler stack.
        $stack->push($history);

        $client = new HttpClient([
            'handler' => $stack,
            'headers' => ['Authorization' => 'Bearer ' . $this->personalAccessToken]
        ]);

        $response = $client->request($method, $uri, $params);

        foreach ($container as $transaction) {
            // echo (string) $transaction['request']->getBody(); // Hello World
        }

        return $response;
    }

    /**
     * @param  string  $uri
     * @param $params
     *
     * @return string
     */
    private function buildUri(string $uri, array $params = []): string
    {
        $params = array_merge(['ref' => $this->branch], $params);

        $params = array_map('urlencode', $params);

        if (isset($params['path'])) {
            $params['path'] = urldecode($params['path']);
        }

        $params = http_build_query($params);

        $params = !empty($params) ? "?$params" : null;

        $baseUrl = rtrim($this->baseUrl, '/') . self::VERSION_URI;

        $endpoint = $params['endpoint'] ?? 'repos';

        return "{$baseUrl}/$endpoint/{$this->username}/{$this->repository}/{$uri}{$params}";
    }

    /**
     * @param  \GuzzleHttp\Psr7\Response  $response
     * @param  bool  $json
     *
     * @return mixed|string
     */
    private function responseContents(Response $response, $json = true)
    {
        $contents = $response->getBody()
            ->getContents();

        return ($json) ? json_decode($contents, true) : $contents;
    }

    /**
     * @param  \GuzzleHttp\Psr7\Response  $response
     *
     * @return bool
     */
    private function responseHasNextPage(Response $response)
    {
        if ($response->hasHeader('X-Hasmore')) {
            return $response->getHeader('X-Hasmore') === 'true';
        }

        return false;
    }
}
