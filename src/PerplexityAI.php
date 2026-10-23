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

use Exception;
use InvalidArgumentException;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use SensitiveParameter;
use SoftCreatR\PerplexityAI\Exception\PerplexityAIException;
use SoftCreatR\PerplexityAI\Http\MultipartBodyBuilder;
use SoftCreatR\PerplexityAI\Http\ServerSentEventDecoder;
use SoftCreatR\PerplexityAI\Http\StreamingClientInterface;
use Throwable;

use const JSON_THROW_ON_ERROR;
use const PHP_QUERY_RFC3986;

/**
 * PSR-17/PSR-18 client for the Perplexity API.
 *
 * Registered endpoints can be called as magic methods or through request().
 * See README.md for the complete method catalog.
 *
 * API METHODS START
 * @method ResponseInterface|null cancelAgentResponse(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null cancelBatch(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createAgentResponse(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createBatch(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createContextualizedEmbedding(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createDecision(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createEmbedding(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createOrchestratorChatCompletion(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createOrchestratorMessage(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createOrchestratorResponse(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null createSkill(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteFile(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null deleteSkill(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null downloadAgentResponseFile(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null downloadFileContent(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null downloadSkill(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null generateAuthToken(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getComputerUsageAnalytics(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null getComputerUsageAnalyticsV2(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listAgentResponseFiles(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listBatches(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listFiles(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listModels(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listOrchestratorModels(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listSkillRevisions(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null listSkills(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveAgentResponse(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveBatch(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveFile(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveOrchestratorModel(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null retrieveSkill(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null revokeAuthToken(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null search(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null updateSkill(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * @method ResponseInterface|null uploadFile(array<string, mixed> $parametersOrBody = [], array<string, mixed>|callable|null $bodyOrCallback = [], ?callable $streamCallback = null)
 * API METHODS END
 */
class PerplexityAI
{
    public function __construct(
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly UriFactoryInterface $uriFactory,
        private readonly ClientInterface $httpClient,
        #[SensitiveParameter]
        private readonly string $apiKey,
        private readonly string $origin = '',
        private readonly string $basePath = '',
    ) {}

    /**
     * Calls a registered endpoint by its SDK method name.
     *
     * @param array<int, mixed> $args
     *
     * @throws PerplexityAIException If the API returns an error.
     * @throws InvalidArgumentException If the endpoint or arguments are invalid.
     * @throws Throwable If multipart construction or response streaming fails.
     */
    public function __call(string $key, array $args): ?ResponseInterface
    {
        $endpoint = PerplexityAIURLBuilder::getEndpoint($key);
        [$parameters, $body, $streamCallback, $customHeaders] = $this->extractCallArguments($args, $endpoint);

        return $this->request($key, $parameters, $body, $streamCallback, $customHeaders);
    }

    /**
     * Sends a request using a registered endpoint name.
     *
     * @param array<string, mixed> $parameters Path and query parameters.
     * @param array<string, mixed> $body JSON request body.
     * @param callable(array<string, mixed>):void|null $streamCallback SSE event callback.
     * @param array<string, string|string[]> $customHeaders Additional request headers.
     *
     * @throws PerplexityAIException If the API returns an error.
     * @throws InvalidArgumentException If the endpoint or parameters are invalid.
     * @throws Throwable If multipart construction or response streaming fails.
     */
    public function request(
        string $key,
        array $parameters = [],
        array $body = [],
        ?callable $streamCallback = null,
        array $customHeaders = [],
    ): ?ResponseInterface {
        $endpoint = PerplexityAIURLBuilder::getEndpoint($key);
        $pathParameters = $this->getPathParameters($endpoint['path']);
        $pathKeys = \array_flip($pathParameters);
        $pathValues = \array_intersect_key($parameters, $pathKeys);
        $query = $endpoint['query'] ?? [];

        foreach (\array_diff_key($parameters, $pathKeys) as $name => $value) {
            $query[$name] = $value;
        }
        $uri = PerplexityAIURLBuilder::createUrl(
            $this->uriFactory,
            $key,
            $pathValues,
            $this->origin,
            $this->basePath,
        );

        if ($query !== []) {
            $uri = $uri->withQuery(\http_build_query($query, '', '&', PHP_QUERY_RFC3986));
        }

        return $this->sendRequest(
            $uri,
            $endpoint['method'],
            $endpoint,
            $body,
            $streamCallback,
            $customHeaders,
        );
    }

    /**
     * Normalizes body-first calls and explicit parameter/body pairs.
     *
     * @param array<int, mixed> $args
     * @param array{method:string,path:string,body:string,fileFields?:list<string>,basePath?:string,headers?:array<string,string|string[]>,query?:array<string,bool|float|int|string>,streaming?:bool,analytics?:bool} $endpoint
     *
     * @return array{array<string,mixed>,array<string,mixed>,callable|null,array<string,string|string[]>}
     *
     * @throws InvalidArgumentException If endpoint arguments are invalid.
     */
    private function extractCallArguments(array $args, array $endpoint): array
    {
        if (\count($args) > 3) {
            throw new InvalidArgumentException('Endpoint calls accept at most three arguments.');
        }

        if (isset($args[0]) && !\is_array($args[0])) {
            throw new InvalidArgumentException('First argument must be an array of parameters.');
        }

        $first = $args[0] ?? [];
        $second = [];
        $hasSecondArray = isset($args[1]) && \is_array($args[1]);
        $streamCallback = null;

        if ($hasSecondArray) {
            $second = $args[1];
        } elseif (isset($args[1])) {
            if (!\is_callable($args[1])) {
                throw new InvalidArgumentException('Second argument must be an array or callable.');
            }

            $streamCallback = $args[1];
        }

        if (isset($args[2])) {
            if (!$hasSecondArray || !\is_callable($args[2])) {
                throw new InvalidArgumentException('Third argument must be a stream callback.');
            }

            $streamCallback = $args[2];
        }

        $customHeaders = $this->extractCustomHeaders($first, $second);

        $bodyType = $endpoint['body'];

        if ($bodyType === 'none') {
            return [$first + $second, [], $streamCallback, $customHeaders];
        }

        if ($hasSecondArray) {
            return [$first, $second, $streamCallback, $customHeaders];
        }

        $pathParameters = $this->getPathParameters($endpoint['path']);

        if ($pathParameters === []) {
            return [[], $first, $streamCallback, $customHeaders];
        }

        $pathKeys = \array_flip($pathParameters);

        return [
            \array_intersect_key($first, $pathKeys),
            \array_diff_key($first, $pathKeys),
            $streamCallback,
            $customHeaders,
        ];
    }

    /**
     * @param array<string, mixed> $first
     * @param array<string, mixed> $second
     *
     * @return array<string, string|string[]>
     *
     * @throws InvalidArgumentException If customHeaders is not an array.
     */
    private function extractCustomHeaders(array &$first, array &$second): array
    {
        $headers = [];

        foreach ([$first, $second] as $values) {
            if (!isset($values['customHeaders'])) {
                continue;
            }

            if (!\is_array($values['customHeaders'])) {
                throw new InvalidArgumentException('customHeaders must be an array.');
            }

            foreach ($values['customHeaders'] as $name => $value) {
                $headers[$name] = $value;
            }
        }

        unset($first['customHeaders'], $second['customHeaders']);

        return $headers;
    }

    /**
     * @return list<string>
     */
    private function getPathParameters(string $path): array
    {
        \preg_match_all('/\{([A-Za-z_]\w*)}/', $path, $matches);

        return $matches[1];
    }

    /**
     * @param array{path:string,body:string,fileFields?:list<string>,basePath?:string,headers?:array<string,string|string[]>,streaming?:bool,analytics?:bool} $endpoint
     * @param array<string, mixed> $body
     * @param callable(array<string, mixed>):void|null $streamCallback
     * @param array<string, string|string[]> $customHeaders
     *
     * @throws PerplexityAIException If the API returns an error.
     * @throws Throwable If multipart construction or response streaming fails.
     */
    private function sendRequest(
        UriInterface $uri,
        string $method,
        array $endpoint,
        array $body,
        ?callable $streamCallback,
        array $customHeaders,
    ): ResponseInterface {
        $bodyType = $endpoint['body'];
        $boundary = $body !== [] && $bodyType === 'multipart' ? $this->generateMultipartBoundary() : null;
        $requestBody = $this->createRequestBody($bodyType, $body, $boundary, $endpoint['fileFields'] ?? []);
        $contentType = null;

        if ($requestBody !== null) {
            $contentType = $bodyType === 'multipart'
                ? "multipart/form-data; boundary={$boundary}"
                : 'application/json';
        }
        $headers = $endpoint['headers'] ?? [];

        foreach ($customHeaders as $name => $value) {
            $headers[$name] = $value;
        }

        $request = $this->requestFactory->createRequest($method, $uri);
        $request = $this->applyHeaders($request, $this->createHeaders($contentType, $headers));

        if ($requestBody !== null) {
            $request = $request->withBody($requestBody);
        }

        $isStreamingRequest = ($endpoint['streaming'] ?? false)
            || ($body['stream'] ?? false) === true
            || ($body['stream_format'] ?? null) === 'sse';

        try {
            $response = $isStreamingRequest && $this->httpClient instanceof StreamingClientInterface
                ? $this->httpClient->sendStreamingRequest($request)
                : $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $exception) {
            throw new PerplexityAIException($exception->getMessage(), (int) $exception->getCode(), $exception);
        }

        $this->throwForErrorResponse($response);

        $isEventStream = \str_contains(\strtolower($response->getHeaderLine('Content-Type')), 'text/event-stream');
        $streamRequested = $isStreamingRequest;

        if ($streamCallback !== null && ($streamRequested || $isEventStream)) {
            (new ServerSentEventDecoder())->decode($response->getBody(), $streamCallback);
        }

        return $response;
    }

    /**
     * @param array<string, mixed> $body
     * @param list<string> $fileFields
     *
     * @throws PerplexityAIException If JSON encoding fails.
     * @throws Throwable If multipart body construction fails.
     */
    private function createRequestBody(
        string $bodyType,
        array $body,
        ?string $boundary = null,
        array $fileFields = [],
    ): ?StreamInterface {
        if ($body === [] || $bodyType === 'none') {
            return null;
        }

        if ($bodyType === 'multipart') {
            return (new MultipartBodyBuilder($this->streamFactory))->build($body, (string) $boundary, $fileFields);
        }

        return $this->streamFactory->createStream($this->createJsonBody($body));
    }

    /**
     * @throws Exception If the operating system cannot provide random bytes.
     */
    private function generateMultipartBoundary(): string
    {
        return '----PerplexityAI' . \bin2hex(\random_bytes(16));
    }

    /**
     * @throws PerplexityAIException If the API returns an error response.
     */
    private function throwForErrorResponse(ResponseInterface $response): void
    {
        if ($response->getStatusCode() < 400) {
            return;
        }

        throw new PerplexityAIException(
            $response->getBody()->getContents(),
            $response->getStatusCode(),
            null,
            $response->getHeaderLine('x-request-id'),
            $response->getHeaders(),
        );
    }

    /**
     * @param array<string, string|string[]> $customHeaders
     * @return array<string, string|string[]>
     */
    private function createHeaders(
        ?string $contentType,
        array $customHeaders = [],
    ): array {
        $headers = ['Authorization' => 'Bearer ' . $this->apiKey];

        if ($contentType !== null) {
            $headers['Content-Type'] = $contentType;
        }

        return \array_replace($headers, $customHeaders);
    }

    /**
     * @param array<string, string|string[]> $headers
     */
    private function applyHeaders(RequestInterface $request, array $headers): RequestInterface
    {
        foreach ($headers as $key => $value) {
            $request = $request->withHeader($key, $value);
        }

        return $request;
    }

    /**
     * @param array<string, mixed> $params
     *
     * @throws PerplexityAIException If JSON encoding fails.
     */
    private function createJsonBody(array $params): string
    {
        try {
            return \json_encode($params, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new PerplexityAIException('JSON encode error: ' . $exception->getMessage(), 0, $exception);
        }
    }
}
