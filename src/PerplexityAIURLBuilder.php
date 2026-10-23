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
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

class PerplexityAIURLBuilder
{
    public const ORIGIN = 'api.perplexity.ai';

    public const BASE_PATH = '/v1';

    private const HTTP_METHOD_POST = 'POST';

    private const HTTP_METHOD_GET = 'GET';

    private const HTTP_METHOD_PUT = 'PUT';

    private const HTTP_METHOD_DELETE = 'DELETE';

    /**
     * Configuration of Perplexity API endpoints.
     *
     * @var array<string, array{
     *     method: string,
     *     path: string,
     *     category: string,
     *     body: 'none'|'json'|'multipart',
     *     fileFields?: list<string>,
     *     basePath?: string,
     *     headers?: array<string, string|string[]>,
     *     query?: array<string, bool|float|int|string>,
     *     streaming?: bool,
     *     analytics?: bool
     * }>
     */
    private static array $urlEndpoints = [
        // Agent API
        'createAgentResponse' => ['method' => self::HTTP_METHOD_POST, 'path' => '/agent', 'category' => 'agent', 'body' => 'json'],
        'retrieveAgentResponse' => ['method' => self::HTTP_METHOD_GET, 'path' => '/agent/{id}', 'category' => 'agent', 'body' => 'none'],
        'listAgentResponseFiles' => ['method' => self::HTTP_METHOD_GET, 'path' => '/agent/{id}/files', 'category' => 'agent', 'body' => 'none'],
        'downloadAgentResponseFile' => ['method' => self::HTTP_METHOD_GET, 'path' => '/agent/{id}/files/{file_id}/content', 'category' => 'agent', 'body' => 'none'],
        'cancelAgentResponse' => ['method' => self::HTTP_METHOD_POST, 'path' => '/agent/{id}/cancel', 'category' => 'agent', 'body' => 'none'],

        // Search, embeddings, and models
        'search' => ['method' => self::HTTP_METHOD_POST, 'path' => '/search', 'category' => 'search', 'body' => 'json', 'basePath' => ''],
        'createEmbedding' => ['method' => self::HTTP_METHOD_POST, 'path' => '/embeddings', 'category' => 'embeddings', 'body' => 'json'],
        'createContextualizedEmbedding' => ['method' => self::HTTP_METHOD_POST, 'path' => '/contextualizedembeddings', 'category' => 'embeddings', 'body' => 'json'],
        'listModels' => ['method' => self::HTTP_METHOD_GET, 'path' => '/models', 'category' => 'models', 'body' => 'none'],

        // Orchestrator API
        'createOrchestratorChatCompletion' => ['method' => self::HTTP_METHOD_POST, 'path' => '/orchestrator/v1/chat/completions', 'category' => 'orchestrator/chat', 'body' => 'json', 'basePath' => ''],
        'createOrchestratorMessage' => ['method' => self::HTTP_METHOD_POST, 'path' => '/orchestrator/v1/messages', 'category' => 'orchestrator/messages', 'body' => 'json', 'basePath' => ''],
        'createOrchestratorResponse' => ['method' => self::HTTP_METHOD_POST, 'path' => '/orchestrator/v1/responses', 'category' => 'orchestrator/responses', 'body' => 'json', 'basePath' => ''],
        'listOrchestratorModels' => ['method' => self::HTTP_METHOD_GET, 'path' => '/orchestrator/v1/models', 'category' => 'orchestrator/models', 'body' => 'none', 'basePath' => ''],
        'retrieveOrchestratorModel' => ['method' => self::HTTP_METHOD_GET, 'path' => '/orchestrator/v1/models/{model}', 'category' => 'orchestrator/models', 'body' => 'none', 'basePath' => ''],

        // Decisions, Skills, Files, and Batches
        'createDecision' => ['method' => self::HTTP_METHOD_POST, 'path' => '/decisions', 'category' => 'decisions', 'body' => 'json'],
        'createSkill' => ['method' => self::HTTP_METHOD_POST, 'path' => '/skills', 'category' => 'skills', 'body' => 'multipart', 'fileFields' => ['file']],
        'listSkills' => ['method' => self::HTTP_METHOD_GET, 'path' => '/skills', 'category' => 'skills', 'body' => 'none'],
        'retrieveSkill' => ['method' => self::HTTP_METHOD_GET, 'path' => '/skills/{skill_id}', 'category' => 'skills', 'body' => 'none'],
        'updateSkill' => ['method' => self::HTTP_METHOD_PUT, 'path' => '/skills/{skill_id}', 'category' => 'skills', 'body' => 'multipart', 'fileFields' => ['file']],
        'listSkillRevisions' => ['method' => self::HTTP_METHOD_GET, 'path' => '/skills/{skill_id}/revisions', 'category' => 'skills', 'body' => 'none'],
        'downloadSkill' => ['method' => self::HTTP_METHOD_GET, 'path' => '/skills/{skill_id}/download', 'category' => 'skills', 'body' => 'none'],
        'deleteSkill' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/skills/{skill_id}', 'category' => 'skills', 'body' => 'none'],
        'uploadFile' => ['method' => self::HTTP_METHOD_POST, 'path' => '/files', 'category' => 'files', 'body' => 'multipart', 'fileFields' => ['file']],
        'listFiles' => ['method' => self::HTTP_METHOD_GET, 'path' => '/files', 'category' => 'files', 'body' => 'none'],
        'retrieveFile' => ['method' => self::HTTP_METHOD_GET, 'path' => '/files/{file_id}', 'category' => 'files', 'body' => 'none'],
        'downloadFileContent' => ['method' => self::HTTP_METHOD_GET, 'path' => '/files/{file_id}/content', 'category' => 'files', 'body' => 'none'],
        'deleteFile' => ['method' => self::HTTP_METHOD_DELETE, 'path' => '/files/{file_id}', 'category' => 'files', 'body' => 'none'],
        'createBatch' => ['method' => self::HTTP_METHOD_POST, 'path' => '/batches', 'category' => 'batches', 'body' => 'json'],
        'listBatches' => ['method' => self::HTTP_METHOD_GET, 'path' => '/batches', 'category' => 'batches', 'body' => 'none'],
        'retrieveBatch' => ['method' => self::HTTP_METHOD_GET, 'path' => '/batches/{batch_id}', 'category' => 'batches', 'body' => 'none'],
        'cancelBatch' => ['method' => self::HTTP_METHOD_POST, 'path' => '/batches/{batch_id}/cancel', 'category' => 'batches', 'body' => 'none'],

        // Authentication
        'generateAuthToken' => ['method' => self::HTTP_METHOD_POST, 'path' => '/generate_auth_token', 'category' => 'authentication', 'body' => 'json', 'basePath' => ''],
        'revokeAuthToken' => ['method' => self::HTTP_METHOD_POST, 'path' => '/revoke_auth_token', 'category' => 'authentication', 'body' => 'json', 'basePath' => ''],

        // Analytics
        'getComputerUsageAnalytics' => ['method' => self::HTTP_METHOD_GET, 'path' => '/analytics/computer/usage', 'category' => 'analytics', 'body' => 'none', 'analytics' => true],
        'getComputerUsageAnalyticsV2' => ['method' => self::HTTP_METHOD_GET, 'path' => '/analytics/computer/usage', 'category' => 'analytics', 'body' => 'none', 'basePath' => '/v2', 'analytics' => true],
    ];

    /**
     * Prevents instantiation of this class.
     */
    protected function __construct()
    {
        // This class should not be instantiated.
    }

    /**
     * Gets all Perplexity API endpoint configurations.
     *
     * @return array<string, array{
     *     method: string,
     *     path: string,
     *     category: string,
     *     body: 'none'|'json'|'multipart',
     *     fileFields?: list<string>,
     *     basePath?: string,
     *     headers?: array<string, string|string[]>,
     *     query?: array<string, bool|float|int|string>,
     *     streaming?: bool,
     *     analytics?: bool
     * }>
     */
    public static function getEndpoints(): array
    {
        return self::$urlEndpoints;
    }

    /**
     * Gets the Perplexity API endpoint configuration.
     *
     * @param string $key The endpoint key.
     *
     * @return array{
     *     method: string,
     *     path: string,
     *     category: string,
     *     body: 'none'|'json'|'multipart',
     *     fileFields?: list<string>,
     *     basePath?: string,
     *     headers?: array<string, string|string[]>,
     *     query?: array<string, bool|float|int|string>,
     *     streaming?: bool,
     *     analytics?: bool
     * } The endpoint configuration.
     *
     * @throws InvalidArgumentException If the provided key is invalid.
     */
    public static function getEndpoint(string $key): array
    {
        if (!isset(self::$urlEndpoints[$key])) {
            throw new InvalidArgumentException(\sprintf('Invalid PerplexityAI URL key "%s".', $key));
        }

        return self::$urlEndpoints[$key];
    }

    /**
     * Creates a URL for the specified Perplexity API endpoint.
     *
     * @param UriFactoryInterface  $uriFactory The PSR-17 URI factory instance used for creating URIs.
     * @param string               $key        The key representing the API endpoint.
     * @param array<string, mixed> $parameters Optional parameters to replace in the endpoint path.
     * @param string               $origin     Custom origin (hostname or absolute base URL), if needed.
     * @param string               $basePath   Custom base path, if needed.
     *
     * @return UriInterface The fully constructed URL for the API endpoint.
     *
     * @throws InvalidArgumentException If a required path parameter is missing or invalid.
     */
    public static function createUrl(
        UriFactoryInterface $uriFactory,
        string $key,
        array $parameters = [],
        string $origin = '',
        string $basePath = '',
    ): UriInterface {
        $endpoint = self::getEndpoint($key);
        $endpointPath = self::replacePathParameters($endpoint['path'], $parameters);
        $isAbsoluteOrigin = $origin !== '' && \preg_match('#^[a-z][a-z0-9+.-]*://#i', $origin) === 1;

        if ($isAbsoluteOrigin) {
            $uri = $uriFactory->createUri($origin);
            $originBasePath = $uri->getPath();
        } else {
            $uri = $uriFactory
                ->createUri()
                ->withScheme('https')
                ->withHost($origin !== '' ? $origin : self::ORIGIN);
            $originBasePath = '';
        }

        $endpointBasePath = $endpoint['basePath'] ?? self::BASE_PATH;
        $resolvedBasePath = $basePath !== ''
            ? $basePath
            : ($isAbsoluteOrigin ? $originBasePath : $endpointBasePath);
        $path = \rtrim('/' . \trim($resolvedBasePath, '/'), '/') . '/' . \ltrim($endpointPath, '/');

        return $uri->withPath($path);
    }

    /**
     * Replaces path parameters in the given path with provided parameter values.
     *
     * @param string               $path       The path containing parameter placeholders.
     * @param array<string, mixed> $parameters The parameter values to replace placeholders in the path.
     *
     * @return string The path with replaced parameter values.
     *
     * @throws InvalidArgumentException If a required path parameter is missing or invalid.
     */
    private static function replacePathParameters(string $path, array $parameters): string
    {
        return \preg_replace_callback('/\{([A-Za-z_]\w*)}/', static function (array $matches) use ($parameters): string {
            $key = $matches[1];

            if (!\array_key_exists($key, $parameters)) {
                throw new InvalidArgumentException(\sprintf('Missing path parameter "%s".', $key));
            }

            if (!\is_scalar($parameters[$key])) {
                throw new InvalidArgumentException(\sprintf(
                    'Parameter "%s" must be a scalar value, %s given.',
                    $key,
                    \gettype($parameters[$key]),
                ));
            }

            return \rawurlencode((string) $parameters[$key]);
        }, $path);
    }
}
