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

namespace SoftCreatR\PerplexityAI\Tests;

use GuzzleHttp\Psr7\HttpFactory;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use SoftCreatR\PerplexityAI\PerplexityAIURLBuilder;

/** @covers \SoftCreatR\PerplexityAI\PerplexityAIURLBuilder */
final class PerplexityAIURLBuilderTest extends TestCase
{
    /** @throws ReflectionException */
    public function testConstructorCanOnlyBeReachedThroughReflection(): void
    {
        $constructor = TestHelper::getPrivateConstructor(PerplexityAIURLBuilder::class);
        $instance = (new ReflectionClass(PerplexityAIURLBuilder::class))->newInstanceWithoutConstructor();
        $constructor->invoke($instance);

        $this->assertInstanceOf(PerplexityAIURLBuilder::class, $instance);
    }

    public function testEndpointRegistryContainsCanonicalOperations(): void
    {
        $endpoints = PerplexityAIURLBuilder::getEndpoints();

        $this->assertCount(22, $endpoints);
        $this->assertSame(
            ['method' => 'POST', 'path' => '/sonar', 'category' => 'sonar', 'body' => 'json'],
            PerplexityAIURLBuilder::getEndpoint('createChatCompletion'),
        );
        $this->assertSame('/router/v1/responses', $endpoints['createRouterResponse']['path']);
        $this->assertSame('/v2', $endpoints['getComputerUsageAnalyticsV2']['basePath']);
        $this->assertSame('/analytics/computer/usage', $endpoints['getComputerUsageAnalyticsV2']['path']);
        $this->assertTrue($endpoints['getComputerUsageAnalyticsV2']['analytics']);
    }

    public function testCreatesDefaultAndCustomUrlsAndEncodesPathParameters(): void
    {
        $factory = new HttpFactory();

        $default = PerplexityAIURLBuilder::createUrl($factory, 'listModels');
        $root = PerplexityAIURLBuilder::createUrl($factory, 'search');
        $v2 = PerplexityAIURLBuilder::createUrl($factory, 'getComputerUsageAnalyticsV2');
        $custom = PerplexityAIURLBuilder::createUrl(
            $factory,
            'retrieveRouterModel',
            ['model' => 'openai/gpt 5'],
            'example.com',
            '/proxy/',
        );

        $this->assertSame('https://api.perplexity.ai/v1/models', (string) $default);
        $this->assertSame('https://api.perplexity.ai/search', (string) $root);
        $this->assertSame('https://api.perplexity.ai/v2/analytics/computer/usage', (string) $v2);
        $this->assertSame('https://example.com/proxy/router/v1/models/openai%2Fgpt%205', (string) $custom);
    }

    public function testCreatesUrlsFromAbsoluteOrigins(): void
    {
        $factory = new HttpFactory();

        $originPath = PerplexityAIURLBuilder::createUrl(
            $factory,
            'listModels',
            origin: 'http://localhost:8080/perplexity',
        );
        $explicitPath = PerplexityAIURLBuilder::createUrl(
            $factory,
            'listModels',
            origin: 'http://localhost:8080/perplexity',
            basePath: '/proxy',
        );

        $this->assertSame('http://localhost:8080/perplexity/models', (string) $originPath);
        $this->assertSame('http://localhost:8080/proxy/models', (string) $explicitPath);
    }

    public function testRejectsUnknownEndpoint(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid PerplexityAI URL key "unknown".');

        PerplexityAIURLBuilder::getEndpoint('unknown');
    }

    public function testRejectsMissingPathParameter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing path parameter "model".');

        PerplexityAIURLBuilder::createUrl(new HttpFactory(), 'retrieveRouterModel');
    }

    public function testRejectsNonScalarPathParameter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter "model" must be a scalar value, array given.');

        PerplexityAIURLBuilder::createUrl(new HttpFactory(), 'retrieveRouterModel', ['model' => []]);
    }
}
