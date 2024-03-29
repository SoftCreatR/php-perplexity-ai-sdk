<?php

/*
 * Copyright (c) 2023, Sascha Greuel and Contributors
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

/**
 * Creates URLs for PerplexityAI API endpoints.
 */
final class PerplexityAIURLBuilder
{
    public const ORIGIN = 'api.perplexity.ai';

    private const HTTP_METHOD_POST = 'POST';

    /**
     * @var array<string, array<string, string>> PerplexityAI API endpoints configuration.
     */
    private static array $urlEndpoints = [
        // Chat Completions: https://docs.perplexity.ai/reference/post_chat_completions
        'createChatCompletion' => ['method' => self::HTTP_METHOD_POST, 'path' => '/chat/completions'],
    ];

    /**
     * Gets the PerplexityAI API endpoint configuration.
     *
     * @param string $key The endpoint key.
     *
     * @return array<string, string> The endpoint configuration.
     *
     * @throws InvalidArgumentException If the provided key is invalid.
     */
    public static function getEndpoint(string $key): array
    {
        if (!isset(self::$urlEndpoints[$key])) {
            throw new InvalidArgumentException('Invalid Perplexity AI URL key "' . $key . '".');
        }

        return self::$urlEndpoints[$key];
    }

    /**
     * Creates a URL for the specified PerplexityAI API endpoint.
     *
     * @param UriFactoryInterface $uriFactory The PSR-17 URI factory instance used for creating URIs.
     * @param string $key The key representing the API endpoint.
     * @param string|null $parameter Optional parameter to replace in the endpoint path.
     * @param string $origin Custom origin (Hostname), if needed.
     *
     * @return UriInterface The fully constructed URL for the API endpoint.
     *
     * @throws InvalidArgumentException If the provided key is invalid.
     */
    public static function createUrl(
        UriFactoryInterface $uriFactory,
        string $key,
        ?string $parameter = null,
        string $origin = ''
    ): UriInterface {
        $endpoint = self::getEndpoint($key);
        $path = self::replacePathParameters($endpoint['path'], $parameter);

        return $uriFactory
            ->createUri()
            ->withScheme('https')
            ->withHost($origin ?: self::ORIGIN)
            ->withPath($path);
    }

    /**
     * Replaces path parameters in the given path with provided parameter value.
     *
     * @param string $path The path containing the parameter placeholder.
     * @param string|null $parameter The parameter value to replace the placeholder with.
     *
     * @return string The path with replaced parameter value.
     */
    private static function replacePathParameters(string $path, ?string $parameter = null): string
    {
        if ($parameter !== null) {
            return \sprintf($path, $parameter);
        }

        return $path;
    }
}
