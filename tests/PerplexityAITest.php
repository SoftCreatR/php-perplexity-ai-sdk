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

use Exception;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use ReflectionException;
use ReflectionProperty;
use SoftCreatR\PerplexityAI\Exception\PerplexityAIException;
use SoftCreatR\PerplexityAI\Http\StreamingClientInterface;
use SoftCreatR\PerplexityAI\PerplexityAI;
use Throwable;

/**
 * @covers \SoftCreatR\PerplexityAI\Exception\PerplexityAIException
 * @covers \SoftCreatR\PerplexityAI\Http\ServerSentEventDecoder
 * @covers \SoftCreatR\PerplexityAI\PerplexityAI
 * @covers \SoftCreatR\PerplexityAI\PerplexityAIURLBuilder
 */
final class PerplexityAITest extends TestCase
{
    private PerplexityAI $perplexityAI;

    private ClientInterface&Stub $httpClient;

    /** @throws \PHPUnit\Framework\MockObject\Exception */
    protected function setUp(): void
    {
        $factory = new HttpFactory();
        $this->httpClient = $this->createStub(ClientInterface::class);
        $this->perplexityAI = new PerplexityAI(
            $factory,
            $factory,
            $factory,
            $this->httpClient,
            'pplx-test',
            'example.com',
            '/api',
        );
    }

    /** @throws Throwable */
    public function testBodyFirstCallCreatesJsonRequestAndReturnsResponse(): void
    {
        $this->mockRequest(function (RequestInterface $request): Response {
            $this->assertSame('POST', $request->getMethod());
            $this->assertSame('https://example.com/api/sonar', (string) $request->getUri());
            $this->assertSame('Bearer pplx-test', $request->getHeaderLine('Authorization'));
            $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
            $this->assertSame('{"model":"sonar","messages":[]}', (string) $request->getBody());

            return new Response(200, [], '{"ok":true}');
        });

        $response = $this->perplexityAI->createChatCompletion(['model' => 'sonar', 'messages' => []]);

        $this->assertSame('{"ok":true}', (string) $response->getBody());
    }

    /** @throws Throwable */
    public function testLegacyTwoArrayCallKeepsParametersSeparateFromBody(): void
    {
        $this->mockRequest(function (RequestInterface $request): Response {
            $this->assertSame('https://example.com/api/agent?id=unused&include=usage', (string) $request->getUri());
            $this->assertSame('{"extra":"value"}', (string) $request->getBody());

            return new Response();
        });

        $response = $this->perplexityAI->__call('createAgentResponse', [
            ['id' => 'unused', 'include' => 'usage'],
            ['extra' => 'value'],
        ]);

        $this->assertSame(200, $response->getStatusCode());
    }

    /** @throws Throwable */
    public function testPathAndQueryParametersAreSeparatedForBodylessCalls(): void
    {
        $this->mockRequest(function (RequestInterface $request): Response {
            $this->assertSame('GET', $request->getMethod());
            $this->assertSame(
                'https://example.com/api/agent/resp_1/files?limit=10&tags%5B0%5D=a%20b',
                (string) $request->getUri(),
            );
            $this->assertSame('', $request->getHeaderLine('Content-Type'));
            $this->assertSame('', (string) $request->getBody());

            return new Response();
        });

        $this->perplexityAI->__call(
            'listAgentResponseFiles',
            [['id' => 'resp_1', 'limit' => 10], ['tags' => ['a b']]],
        );
    }

    /** @throws Throwable */
    public function testBodyAndPathCanShareTheFirstArray(): void
    {
        $this->mockRequest(function (RequestInterface $request): Response {
            $this->assertSame('https://example.com/api/agent/resp%2F1/cancel', (string) $request->getUri());
            $this->assertSame('', (string) $request->getBody());

            return new Response();
        });

        $this->perplexityAI->cancelAgentResponse(['id' => 'resp/1']);
    }

    /** @throws Throwable */
    public function testRequestMethodSupportsQueryBodyAndCustomHeaders(): void
    {
        $this->mockRequest(function (RequestInterface $request): Response {
            $this->assertSame('https://example.com/api/search?source=web', (string) $request->getUri());
            $this->assertSame('custom', $request->getHeaderLine('Authorization'));
            $this->assertSame('yes', $request->getHeaderLine('X-Test'));
            $this->assertSame('{"query":"PHP"}', (string) $request->getBody());

            return new Response(201);
        });

        $response = $this->perplexityAI->request(
            'search',
            ['source' => 'web'],
            ['query' => 'PHP'],
            null,
            ['Authorization' => 'custom', 'X-Test' => 'yes'],
        );

        $this->assertSame(201, $response->getStatusCode());
    }

    /** @throws Throwable */
    public function testCustomHeadersCanBePassedInEitherLegacyArray(): void
    {
        $requests = [];
        $this->mockRequest(static function (RequestInterface $request) use (&$requests): Response {
            $requests[] = $request;

            return new Response();
        });

        $this->perplexityAI->createEmbedding([
            'customHeaders' => ['X-First' => 'first'],
            'model' => 'pplx-embed-v1-0.6b',
            'input' => 'test',
        ]);
        $this->perplexityAI->createEmbedding([], [
            'customHeaders' => ['X-Second' => 'second'],
            'model' => 'pplx-embed-v1-0.6b',
            'input' => 'test',
        ]);

        $this->assertSame('first', $requests[0]->getHeaderLine('X-First'));
        $this->assertStringNotContainsString('customHeaders', (string) $requests[0]->getBody());
        $this->assertSame('second', $requests[1]->getHeaderLine('X-Second'));
    }

    /** @throws Throwable */
    public function testStreamingDecodesEventsAndReturnsResponse(): void
    {
        $response = new Response(200, ['Content-Type' => 'text/event-stream'], "data: {\"delta\":\"a\"}\n\ndata: [DONE]\n\n");
        $this->mockRequest(static fn(): Response => $response);
        $events = [];

        $actual = $this->perplexityAI->createChatCompletion(
            ['model' => 'sonar', 'messages' => [], 'stream' => true],
            static function (mixed $event) use (&$events): void {
                $events[] = $event;
            },
        );

        $this->assertSame($response, $actual);
        $this->assertSame([['delta' => 'a']], $events);
    }

    /** @throws Throwable */
    public function testEventStreamContentTypeTriggersDecoderWithoutStreamFlag(): void
    {
        $this->mockRequest(static fn(): Response => new Response(
            200,
            ['Content-Type' => 'text/event-stream; charset=utf-8'],
            "data: {\"event\":true}\n\n",
        ));
        $events = [];

        $this->perplexityAI->createAgentResponse(
            ['model' => 'openai/gpt-5-mini', 'input' => 'test'],
            static function (mixed $event) use (&$events): void {
                $events[] = $event;
            },
        );

        $this->assertSame([['event' => true]], $events);
    }

    /** @throws Throwable */
    public function testNormalResponseDoesNotInvokeCallback(): void
    {
        $this->mockRequest(static fn(): Response => new Response(200, ['Content-Type' => 'application/json'], '{}'));

        $this->perplexityAI->createAgentResponse(
            ['input' => 'test'],
            fn() => $this->fail('Callback must only receive SSE responses.'),
        );

        $this->addToAssertionCount(1);
    }

    /** @throws Throwable */
    public function testStreamingClientExtensionIsUsedForStreamingRequests(): void
    {
        $factory = new HttpFactory();
        $client = $this->createMock(StreamingClientInterface::class);
        $client->expects($this->never())->method('sendRequest');
        $client->expects($this->once())->method('sendStreamingRequest')->willReturn(new Response(
            200,
            ['Content-Type' => 'text/event-stream'],
            "data: [DONE]\n\n",
        ));
        $sdk = new PerplexityAI($factory, $factory, $factory, $client, 'key');

        $sdk->createRouterResponse(
            ['model' => 'openai/gpt-5-mini', 'input' => 'test', 'stream' => true],
            static function (): void {},
        );
    }

    /**
     * @throws ReflectionException
     * @throws Throwable
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testEndpointMetadataControlsQueryHeadersAndStreaming(): void
    {
        $property = new ReflectionProperty(\SoftCreatR\PerplexityAI\PerplexityAIURLBuilder::class, 'urlEndpoints');
        $endpoints = $property->getValue();
        $property->setValue(null, $endpoints + [
            'metadataTest' => [
                'method' => 'POST',
                'path' => '/metadata',
                'category' => 'test',
                'body' => 'json',
                'query' => ['default' => 'value'],
                'headers' => ['X-Endpoint' => 'enabled'],
                'streaming' => true,
            ],
        ]);

        try {
            $factory = new HttpFactory();
            $client = $this->createMock(StreamingClientInterface::class);
            $client->expects($this->never())->method('sendRequest');
            $client->expects($this->once())->method('sendStreamingRequest')->willReturnCallback(
                function (RequestInterface $request): Response {
                    $this->assertSame('https://example.com/api/metadata?default=override&extra=yes', (string) $request->getUri());
                    $this->assertSame('enabled', $request->getHeaderLine('X-Endpoint'));

                    return new Response(200, ['Content-Type' => 'text/event-stream'], "data: [DONE]\n\n");
                },
            );
            $sdk = new PerplexityAI($factory, $factory, $factory, $client, 'key', 'example.com', '/api');

            $sdk->request('metadataTest', ['default' => 'override', 'extra' => 'yes'], ['input' => 'test']);
        } finally {
            $property->setValue(null, $endpoints);
        }
    }

    /** @throws Throwable */
    public function testClientExceptionsAreWrapped(): void
    {
        $previous = new class ('Transport failed', 17) extends Exception implements ClientExceptionInterface {};
        $this->mockRequest(static fn() => throw $previous);

        try {
            $this->perplexityAI->listModels();
            $this->fail('Expected transport exception.');
        } catch (PerplexityAIException $exception) {
            $this->assertSame('Transport failed', $exception->getMessage());
            $this->assertSame(17, $exception->getCode());
            $this->assertSame($previous, $exception->getPrevious());
        }
    }

    /** @throws Throwable */
    public function testApiErrorsContainStructuredResponseDetails(): void
    {
        $this->mockRequest(static fn(): Response => new Response(
            429,
            ['x-request-id' => 'req_123', 'retry-after' => '10'],
            '{"error":{"message":"Rate limited","type":"rate_limit"}}',
        ));

        try {
            $this->perplexityAI->listModels();
            $this->fail('Expected API exception.');
        } catch (PerplexityAIException $exception) {
            $this->assertSame('Rate limited', $exception->getMessage());
            $this->assertSame(429, $exception->getCode());
            $this->assertSame('req_123', $exception->getRequestId());
            $this->assertSame('10', $exception->getResponseHeaders()['retry-after'][0]);
            $this->assertSame('rate_limit', $exception->getError()['type']);
        }
    }

    /** @throws ReflectionException */
    public function testJsonEncodingErrorsAreWrapped(): void
    {
        $method = TestHelper::getPrivateMethod($this->perplexityAI, 'createJsonBody');
        $resource = \tmpfile();

        $this->expectException(PerplexityAIException::class);
        $this->expectExceptionMessageMatches('/^JSON encode error:/');

        $method->invoke($this->perplexityAI, ['resource' => $resource]);
    }

    /** @throws ReflectionException */
    public function testPrivateArgumentNormalizerCompatibilityBranch(): void
    {
        $method = TestHelper::getPrivateMethod($this->perplexityAI, 'extractCallArguments');
        $callback = static function (): void {};

        $this->assertSame(
            [['a' => 1], ['b' => 2], $callback, ['X-Test' => 'second']],
            $method->invoke(
                $this->perplexityAI,
                [['a' => 1, 'customHeaders' => ['X-Test' => 'first']], ['b' => 2, 'customHeaders' => ['X-Test' => 'second']], $callback],
            ),
        );
        $this->assertSame(
            [['id' => 'response/1'], ['input' => 'test'], null, []],
            $method->invoke(
                $this->perplexityAI,
                [['id' => 'response/1', 'input' => 'test']],
                ['method' => 'POST', 'path' => '/responses/{id}', 'body' => 'json'],
            ),
        );
    }

    /** @throws ReflectionException */
    public function testPrivateBodyAndHeaderCompatibilityBranches(): void
    {
        $inferBodyType = TestHelper::getPrivateMethod($this->perplexityAI, 'inferBodyType');
        $createHeaders = TestHelper::getPrivateMethod($this->perplexityAI, 'createHeaders');

        $this->assertSame('none', $inferBodyType->invoke($this->perplexityAI, 'GET', '/resource'));
        $this->assertSame('none', $inferBodyType->invoke($this->perplexityAI, 'DELETE', '/resource'));
        $this->assertSame('json', $inferBodyType->invoke($this->perplexityAI, 'POST', '/resource'));
        $this->assertSame('application/json', $createHeaders->invoke($this->perplexityAI, true)['Content-Type']);
        $this->assertArrayNotHasKey('Content-Type', $createHeaders->invoke($this->perplexityAI, false));
    }

    /**
     * @param array<int, mixed> $arguments
     *
     * @throws Throwable
     */
    #[DataProvider('invalidArguments')]
    public function testRejectsInvalidMagicCallArguments(array $arguments, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        $this->perplexityAI->__call('createChatCompletion', $arguments);
    }

    /** @return iterable<string, array{array<int,mixed>,string}> */
    public static function invalidArguments(): iterable
    {
        yield 'too many' => [[[], [], static function (): void {}, 'extra'], 'Endpoint calls accept at most three arguments.'];
        yield 'first type' => [['invalid'], 'First argument must be an array of parameters.'];
        yield 'second type' => [[[], 'invalid'], 'Second argument must be an array or callable.'];
        yield 'third without body' => [[[], static function (): void {}, static function (): void {}], 'Third argument must be a stream callback.'];
        yield 'third type' => [[[], [], 'invalid'], 'Third argument must be a stream callback.'];
        yield 'headers first' => [[['customHeaders' => 'invalid']], 'customHeaders must be an array.'];
        yield 'headers second' => [[[], ['customHeaders' => 'invalid']], 'customHeaders must be an array.'];
    }

    /** @param callable(RequestInterface):Response $callback */
    private function mockRequest(callable $callback): void
    {
        $this->httpClient->method('sendRequest')->willReturnCallback($callback);
    }
}
