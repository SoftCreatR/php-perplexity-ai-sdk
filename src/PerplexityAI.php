<?php

/*
 * Copyright (c) 2023-present, Sascha Greuel and Contributors
 *
 * Permission to use, copy, modify, and/or distribute this software for any
 * purpose with or without fee is hereby granted, provided that the above
 * copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
 * WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
 * ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
 * WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
 * ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
 * OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
 */

namespace SoftCreatR\PerplexityAI;

use InvalidArgumentException;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use SensitiveParameter;
use SoftCreatR\PerplexityAI\Exception\PerplexityAIException;

use const JSON_THROW_ON_ERROR;

/**
 * A wrapper for the PerplexityAI API.
 *
 * @method ResponseInterface|null createChatCompletion(array $options, ?\callable $streamCallback = null) Creates a model response for the given chat conversation.
 */
class PerplexityAI
{
    /**
     * Constructs a new instance of the PerplexityAI client.
     *
     * @param RequestFactoryInterface $requestFactory The PSR-17 request factory.
     * @param StreamFactoryInterface  $streamFactory  The PSR-17 stream factory.
     * @param UriFactoryInterface     $uriFactory     The PSR-17 URI factory.
     * @param ClientInterface         $httpClient     The PSR-18 HTTP client.
     * @param string                  $apiKey         Your PerplexityAI API key.
     * @param string                  $origin         Custom API origin (hostname).
     * @param string                  $basePath       Custom base path.
     */
    public function __construct(
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly UriFactoryInterface $uriFactory,
        private readonly ClientInterface $httpClient,
        #[SensitiveParameter]
        private readonly string $apiKey,
        private readonly string $origin = '',
        private readonly string $basePath = ''
    ) {}

    /**
     * Magic method to call the PerplexityAI API endpoints.
     *
     * @param string $key The endpoint method.
     * @param array $args The arguments for the endpoint method.
     *
     * @return ResponseInterface|null The API response or null if streaming.
     *
     * @throws PerplexityAIException       If the API returns an error.
     * @throws InvalidArgumentException If the parameters are invalid.
     */
    public function __call(string $key, array $args): ?ResponseInterface
    {
        $endpoint = PerplexityAIURLBuilder::getEndpoint($key);
        $httpMethod = $endpoint['method'];

        [$parameters, $opts, $streamCallback] = $this->extractCallArguments($args);

        return $this->callAPI($httpMethod, $key, $parameters, $opts, $streamCallback);
    }

    /**
     * Extracts the arguments from the input array.
     *
     * @param array $args The input arguments.
     *
     * @return array An array containing the extracted parameters, options, and stream callback.
     *
     * @throws InvalidArgumentException If the first argument is not an array.
     */
    private function extractCallArguments(array $args): array
    {
        $parameters = [];
        $opts = [];
        $streamCallback = null;

        if (!isset($args[0])) {
            return [$parameters, $opts, $streamCallback];
        }

        if (\is_array($args[0])) {
            $parameters = $args[0];

            if (isset($args[1]) && \is_array($args[1])) {
                $opts = $args[1];

                if (isset($args[2]) && \is_callable($args[2])) {
                    $streamCallback = $args[2];
                }
            } elseif (isset($args[1]) && \is_callable($args[1])) {
                $streamCallback = $args[1];
            }
        } else {
            throw new InvalidArgumentException('First argument must be an array of parameters.');
        }

        return [$parameters, $opts, $streamCallback];
    }

    /**
     * Calls the PerplexityAI API with the provided method, key, parameters, and options.
     *
     * @param string $method The HTTP method for the request.
     * @param string $key The API endpoint key.
     * @param array $parameters Parameters for URL placeholders.
     * @param array $opts The options for the request body or query.
     * @param callable|null $streamCallback Callback function to handle streaming data.
     *
     * @return ResponseInterface|null The API response or null if streaming.
     *
     * @throws PerplexityAIException If the API returns an error.
     */
    private function callAPI(
        string $method,
        string $key,
        array $parameters = [],
        array $opts = [],
        ?callable $streamCallback = null
    ): ?ResponseInterface {
        $uri = PerplexityAIURLBuilder::createUrl(
            $this->uriFactory,
            $key,
            $parameters,
            $this->origin,
            $this->basePath
        );

        // Extract headers from opts
        $customHeaders = $opts['customHeaders'] ?? [];
        unset($opts['customHeaders']); // Remove headers from opts to avoid sending them in the body

        return $this->sendRequest($uri, $method, $opts, $streamCallback, $customHeaders);
    }

    /**
     * Sends an HTTP request to the PerplexityAI API and returns the response.
     *
     * @param UriInterface $uri The URI to send the request to.
     * @param string $method The HTTP method to use.
     * @param array $params Parameters to include in the request body.
     * @param callable|null $streamCallback Callback function to handle streaming data.
     *
     * @return ResponseInterface|null The response from the PerplexityAI API or null if streaming.
     *
     * @throws PerplexityAIException If the API returns an error.
     */
    private function sendRequest(
        UriInterface $uri,
        string $method,
        array $params = [],
        ?callable $streamCallback = null,
        array $customHeaders = []
    ): ?ResponseInterface {
        $request = $this->requestFactory->createRequest($method, $uri);
        $headers = $this->createHeaders($customHeaders);
        $request = $this->applyHeaders($request, $headers);

        $body = $this->createJsonBody($params);

        if ($body !== '') {
            $request = $request->withBody($this->streamFactory->createStream($body));
        }

        try {
            if ($streamCallback !== null && ($params['stream'] ?? false) === true) {
                $this->handleStreamingResponse($request, $streamCallback);

                return null;
            }

            $response = $this->httpClient->sendRequest($request);

            if ($response->getStatusCode() >= 400) {
                throw new PerplexityAIException($response->getBody()->getContents(), $response->getStatusCode());
            }

            return $response;
        } catch (ClientExceptionInterface $e) {
            throw new PerplexityAIException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Handles a streaming response from the API.
     *
     * @param RequestInterface $request        The request to send.
     * @param callable         $streamCallback The callback function to handle streaming data.
     *
     * @return void
     *
     * @throws PerplexityAIException If an error occurs during streaming.
     */
    private function handleStreamingResponse(RequestInterface $request, callable $streamCallback): void
    {
        try {
            $response = $this->httpClient->sendRequest($request);
            $statusCode = $response->getStatusCode();

            if ($statusCode >= 400) {
                throw new PerplexityAIException($response->getBody()->getContents(), $statusCode);
            }

            $body = $response->getBody();
            $buffer = '';

            while (!$body->eof()) {
                $chunk = $body->read(8192);
                $buffer .= $chunk;

                while (($newlinePos = \strpos($buffer, "\n")) !== false) {
                    $line = \substr($buffer, 0, $newlinePos);
                    $buffer = \substr($buffer, $newlinePos + 1);

                    $data = \trim($line);

                    if ($data === '') {
                        continue;
                    }

                    if ($data === 'data: [DONE]') {
                        return;
                    }

                    if (\str_starts_with($data, 'data: ')) {
                        $json = \substr($data, 6);

                        try {
                            $decoded = \json_decode($json, true, 512, JSON_THROW_ON_ERROR);
                            $streamCallback($decoded);
                        } catch (JsonException $e) {
                            throw new PerplexityAIException('JSON decode error: ' . $e->getMessage(), 0, $e);
                        }
                    }
                }
            }
        } catch (ClientExceptionInterface $e) {
            throw new PerplexityAIException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Creates the headers for an API request.
     *
     * @return array An associative array of headers.
     */
    private function createHeaders(array $customHeaders = []): array
    {
        $defaultHeaders = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ];

        // Merge custom headers, overriding defaults if necessary
        return \array_merge($defaultHeaders, $customHeaders);
    }

    /**
     * Applies the headers to the given request.
     *
     * @param RequestInterface $request The request to apply headers to.
     * @param array            $headers An associative array of headers to apply.
     *
     * @return RequestInterface The request with headers applied.
     */
    private function applyHeaders(RequestInterface $request, array $headers): RequestInterface
    {
        foreach ($headers as $key => $value) {
            $request = $request->withHeader($key, $value);
        }

        return $request;
    }

    /**
     * Creates a JSON-encoded body string from the given parameters.
     *
     * @param array $params An associative array of parameters to encode as JSON.
     *
     * @return string The JSON-encoded body string.
     *
     * @throws PerplexityAIException If JSON encoding fails.
     */
    private function createJsonBody(array $params): string
    {
        if (empty($params)) {
            return '';
        }

        try {
            return \json_encode($params, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new PerplexityAIException('JSON encode error: ' . $e->getMessage(), 0, $e);
        }
    }
}
