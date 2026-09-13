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

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use SoftCreatR\PerplexityAI\Exception\PerplexityAIException;
use SoftCreatR\PerplexityAI\PerplexityAI;
use SoftCreatR\PerplexityAI\PerplexityAIURLBuilder;

/**
 * Load .env from project root if present.
 */
$projectRoot = \dirname(__DIR__);

if (\file_exists($projectRoot . '/.env')) {
    Dotenv::createImmutable($projectRoot)->load();
}

/**
 * Example factory class for creating and using the Perplexity client.
 */
final class PerplexityAIFactory
{
    private function __construct() {}

    /**
     * Create a Perplexity client.
     */
    public static function create(
        #[SensitiveParameter]
        string $apiKey = '',
    ): PerplexityAI {
        $psr17Factory = new HttpFactory();
        $httpClient = new Client(['stream' => true]);

        return new PerplexityAI(
            requestFactory: $psr17Factory,
            streamFactory: $psr17Factory,
            uriFactory: $psr17Factory,
            httpClient: $httpClient,
            apiKey: $apiKey,
            origin: self::value('PERPLEXITY_API_ORIGIN'),
            basePath: self::value('PERPLEXITY_API_BASE_PATH'),
        );
    }

    /**
     * Send a generic request to a Perplexity endpoint.
     *
     * @param string        $method          Method name, e.g. 'createChatCompletion'.
     * @param array         $parameters      URL/path parameters.
     * @param array         $options         Body or query parameters.
     * @param callable|null $streamCallback  Stream callback for SSE.
     * @param bool          $returnResponse  If true, returns the raw body.
     * @param bool          $useAnalyticsKey If true, uses $_ENV['PERPLEXITY_ANALYTICS_API_KEY'].
     *
     * @throws RuntimeException If the required API key is not configured.
     */
    public static function request(
        string $method,
        array $parameters = [],
        array $options = [],
        ?callable $streamCallback = null,
        bool $returnResponse = false,
        bool $useAnalyticsKey = false,
    ): ?string {
        try {
            $endpoint = PerplexityAIURLBuilder::getEndpoint($method);
            $useAnalyticsKey = $useAnalyticsKey || ($endpoint['analytics'] ?? false);
            $keyName = $useAnalyticsKey ? 'PERPLEXITY_ANALYTICS_API_KEY' : 'PERPLEXITY_API_KEY';
            $apiKey = self::value($keyName);

            if (!$useAnalyticsKey && $apiKey === '') {
                $apiKey = self::value('PERPLEXITYAI_API_KEY');
            }

            if ($apiKey === '') {
                throw new RuntimeException("Set {$keyName} in the project .env file before running this example.");
            }

            $perplexity = self::create($apiKey);
            $hasPlaceholders = (bool) \preg_match('/\{[A-Za-z_]\w*}/', $endpoint['path']);

            if ($endpoint['body'] === 'none') {
                $urlParams = $parameters + $options;
                $bodyOpts = [];
            } elseif ($hasPlaceholders) {
                $urlParams = $parameters;
                $bodyOpts = $options;
            } else {
                $urlParams = [];
                $bodyOpts = $parameters + $options;
            }

            if ($streamCallback !== null) {
                $perplexity->request($method, $urlParams, $bodyOpts, $streamCallback);

                return null;
            }

            $response = $perplexity->request($method, $urlParams, $bodyOpts);

            if ($response === null) {
                return null;
            }

            if ($returnResponse) {
                return $response->getBody()->getContents();
            }

            $contentType = $response->getHeaderLine('Content-Type');
            $body = $response->getBody()->getContents();

            if (\str_contains($contentType, 'application/json')) {
                $decoded = \json_decode($body, true, 512, \JSON_THROW_ON_ERROR);
                echo "============\n| Response |\n============\n\n"
                    . \json_encode($decoded, \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR)
                    . "\n\n============\n";
            } else {
                echo "Received response with Content-Type: {$contentType}\n";
                echo $body;
            }
        } catch (PerplexityAIException $exception) {
            echo "Perplexity API error: {$exception->getMessage()}\n";

            if ($exception->getRequestId() !== null) {
                echo "Request ID: {$exception->getRequestId()}\n";
            }
        } catch (\Throwable $exception) {
            echo "Error: {$exception->getMessage()}\n";
        }

        return null;
    }

    /**
     * Send an analytics request.
     *
     * @throws RuntimeException If the analytics API key is not configured.
     */
    public static function analyticsRequest(
        string $method,
        array $parameters = [],
        array $options = [],
        ?callable $streamCallback = null,
        bool $returnResponse = false,
    ): ?string {
        return self::request(
            $method,
            $parameters,
            $options,
            $streamCallback,
            $returnResponse,
            true,
        );
    }

    /**
     * Read a required example variable.
     *
     * @throws RuntimeException If an example variable is not configured.
     */
    public static function env(string $name, string $default = ''): string
    {
        $value = self::value($name, $default);

        if ($value === '') {
            throw new RuntimeException("Set {$name} in the project .env file before running this example.");
        }

        return $value;
    }

    private static function value(string $name, string $default = ''): string
    {
        $value = $_ENV[$name] ?? \getenv($name);

        return \is_string($value) && $value !== '' ? $value : $default;
    }
}
