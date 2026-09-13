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

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use SoftCreatR\PerplexityAI\PerplexityAIURLBuilder;

#[CoversNothing]
final class EndpointCatalogTest extends TestCase
{
    public function testEveryRegisteredEndpointHasAMatchingExample(): void
    {
        $exampleRoot = \dirname(__DIR__) . '/examples';

        foreach (PerplexityAIURLBuilder::getEndpoints() as $method => $endpoint) {
            $path = "{$exampleRoot}/{$endpoint['category']}/{$method}.php";
            $route = ($endpoint['basePath'] ?? PerplexityAIURLBuilder::BASE_PATH) . $endpoint['path'];
            $this->assertFileExists($path, "Missing example for {$method}.");

            $contents = \file_get_contents($path);
            $this->assertIsString($contents);
            $this->assertStringContainsString("'{$method}'", $contents);
            $this->assertStringContainsString("{$endpoint['method']} {$route}", $contents);
        }
    }

    public function testReadmeListsEveryRegisteredEndpoint(): void
    {
        $readme = \file_get_contents(\dirname(__DIR__) . '/README.md');
        $this->assertIsString($readme);

        foreach (PerplexityAIURLBuilder::getEndpoints() as $method => $endpoint) {
            $route = ($endpoint['basePath'] ?? PerplexityAIURLBuilder::BASE_PATH) . $endpoint['path'];
            $this->assertStringContainsString("`{$method}`", $readme);
            $this->assertStringContainsString("`{$endpoint['method']} {$route}`", $readme);
        }
    }
}
