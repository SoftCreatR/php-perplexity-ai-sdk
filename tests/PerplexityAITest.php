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
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use ReflectionException;
use SoftCreatR\PerplexityAI\Exception\PerplexityAIException;
use SoftCreatR\PerplexityAI\PerplexityAI;

/**
 * @covers \SoftCreatR\PerplexityAI\Exception\PerplexityAIException
 * @covers \SoftCreatR\PerplexityAI\PerplexityAI
 * @covers \SoftCreatR\PerplexityAI\PerplexityAIURLBuilder
 */
final class PerplexityAITest extends TestCase
{
    /**
     * The PerplexityAI instance used for testing.
     */
    private PerplexityAI $perplexityAI;

    /**
     * The mocked HTTP client used for simulating API responses.
     */
    private ClientInterface $mockedClient;

    /**
     * API key for the PerplexityAI API.
     */
    private string $apiKey = 'pplx-...';

    /**
     * Custom origin for the PerplexityAI API, if needed.
     */
    private string $origin = 'example.com';

    /**
     * Sets up the test environment by creating an PerplexityAI instance and
     * a mocked HTTP client, then assigns the mocked client to the PerplexityAI instance.
     *
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $psr17Factory = new HttpFactory();
        $this->mockedClient = $this->createMock(ClientInterface::class);

        $this->perplexityAI = new PerplexityAI(
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
            $this->mockedClient,
            $this->apiKey,
            $this->origin
        );
    }

    /**
     * Tests that an InvalidArgumentException is thrown when the first argument is not an array.
     */
    public function testInvalidFirstArgumentInCall(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('First argument must be an array of parameters.');

        /** @noinspection PhpParamsInspection */
        $this->perplexityAI->createChatCompletion('invalid_argument');
    }

    /**
     * Tests that an PerplexityAIException is thrown when the API returns an error response.
     */
    public function testCallAPIHandlesErrorResponse(): void
    {
        $this->sendRequestMock(static function () {
            return new Response(400, [], 'Bad Request');
        });

        $this->expectException(PerplexityAIException::class);
        $this->expectExceptionMessage('Bad Request');

        // Pass options as the second argument
        $this->perplexityAI->createChatCompletion([], [
            'model' => 'llama-3.1-sonar-small-128k-online',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => 'Test message',
                ],
            ],
        ]);
    }

    /**
     * Tests that an PerplexityAIException is thrown when the HTTP client throws a ClientExceptionInterface.
     */
    public function testCallAPICatchesClientException(): void
    {
        $this->sendRequestMock(
            static fn() => throw new class ('Client error', 0) extends Exception implements ClientExceptionInterface {}
        );

        $this->expectException(PerplexityAIException::class);
        $this->expectExceptionMessage('Client error');

        // Pass options as the second argument
        $this->perplexityAI->createChatCompletion([], [
            'model' => 'llama-3.1-sonar-small-128k-online',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => 'Test message',
                ],
            ],
        ]);
    }

    /**
     * Tests that handleStreamingResponse throws an PerplexityAIException when the response status code is >= 400.
     */
    public function testHandleStreamingResponseHandlesErrorResponse(): void
    {
        $this->sendRequestMock(static function () {
            return new Response(400, [], 'Bad Request');
        });

        $this->expectException(PerplexityAIException::class);
        $this->expectExceptionMessage('Bad Request');

        $this->perplexityAI->createChatCompletion(
            [],
            [
                'model' => 'llama-3.1-sonar-small-128k-online',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Test message',
                    ],
                ],
                'stream' => true,
            ],
            static function () {
                // Streaming callback
            }
        );
    }

    /**
     * Tests that handleStreamingResponse continues when data is an empty string.
     */
    public function testHandleStreamingResponseContinuesOnEmptyData(): void
    {
        $fakeResponseContent = "\n"; // Empty data
        $stream = \fopen('php://temp', 'rb+');
        \fwrite($stream, $fakeResponseContent);
        \rewind($stream);

        $fakeResponse = new Response(200, [], $stream);

        $this->sendRequestMock(static function () use ($fakeResponse) {
            return $fakeResponse;
        });

        $this->perplexityAI->createChatCompletion(
            [],
            [
                'model' => 'llama-3.1-sonar-small-128k-online',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Test message',
                    ],
                ],
                'stream' => true,
            ],
            fn() => $this->fail('Streaming callback should not be called on empty data.')
        );

        $this->assertTrue(true); // If no exception is thrown, test passes
    }

    /**
     * Tests that handleStreamingResponse throws an PerplexityAIException when JSON decoding fails.
     */
    public function testHandleStreamingResponseJsonException(): void
    {
        $fakeResponseContent = "data: invalid_json\n";
        $stream = \fopen('php://temp', 'rb+');
        \fwrite($stream, $fakeResponseContent);
        \rewind($stream);

        $fakeResponse = new Response(200, [], $stream);

        $this->sendRequestMock(static function () use ($fakeResponse) {
            return $fakeResponse;
        });

        $this->expectException(PerplexityAIException::class);
        $this->expectExceptionMessageMatches('/JSON decode error:/');

        $this->perplexityAI->createChatCompletion(
            [],
            [
                'model' => 'llama-3.1-sonar-small-128k-online',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Test message',
                    ],
                ],
                'stream' => true,
            ],
            static function ($data) {
                // Streaming callback
            }
        );
    }

    /**
     * Tests that handleStreamingResponse catches ClientExceptionInterface exceptions.
     */
    public function testHandleStreamingResponseCatchesClientException(): void
    {
        $this->sendRequestMock(
            static fn() => throw new class ('Client error in streaming', 0) extends Exception implements ClientExceptionInterface {}
        );

        $this->expectException(PerplexityAIException::class);
        $this->expectExceptionMessage('Client error in streaming');

        $this->perplexityAI->createChatCompletion(
            [],
            [
                'model' => 'llama-3.1-sonar-small-128k-online',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Test message',
                    ],
                ],
                'stream' => true,
            ],
            static function () {
                // Streaming callback
            }
        );
    }

    /**
     * Tests that createJsonBody throws an PerplexityAIException when JSON encoding fails.
     *
     * @throws ReflectionException
     */
    public function testCreateJsonBodyJsonException(): void
    {
        $reflectionMethod = TestHelper::getPrivateMethod($this->perplexityAI, 'createJsonBody');

        $this->expectException(PerplexityAIException::class);
        $this->expectExceptionMessageMatches('/^JSON encode error:/');

        $invalidValue = \tmpfile(); // Cannot be JSON encoded
        $params = ['invalid' => $invalidValue];

        $reflectionMethod->invoke($this->perplexityAI, $params);
    }

    /**
     * Tests that the createChatCompletion method handles API calls correctly.
     *
     * @throws Exception
     */
    public function testCreateChatCompletion(): void
    {
        $this->testApiCall(
            fn() => $this->perplexityAI->createChatCompletion([], [
                'model' => 'llama-3.1-sonar-small-128k-online',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Be precise and concise.',
                    ],
                    [
                        'role' => 'user',
                        'content' => 'How many stars are there in our galaxy?',
                    ],
                ],
            ])
        );
    }

    /**
     * Tests that the createChatCompletion method handles streaming API calls correctly.
     *
     * @throws Exception
     */
    public function testCreateChatCompletionWithStreaming(): void
    {
        $output = '';

        $streamCallback = static function ($data) use (&$output) {
            if (isset($data['choices'][0]['delta']['content'])) {
                $output .= $data['choices'][0]['delta']['content'];
            }
        };

        $this->testApiCallWithStreaming(
            fn($streamCallback) => $this->perplexityAI->createChatCompletion(
                [],
                [
                    'model' => 'llama-3.1-sonar-small-128k-online',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => 'Tell me a story about a brave knight.',
                        ],
                    ],
                    'stream' => true,
                ],
                $streamCallback
            ),
            $streamCallback
        );

        $expectedOutput = 'Hello';
        $this->assertEquals($expectedOutput, $output);
    }

    /**
     * Mocks an API call using a callable and a response file.
     *
     * Mocks the HTTP client to return a predefined response loaded from a file,
     * and checks if the status code and response body match the expected values.
     *
     * @param callable $apiCall      The API call to test.
     *
     * @throws Exception
     */
    private function testApiCall(callable $apiCall): void
    {
        $fakeResponseBody = TestHelper::loadResponseFromFile('chatCompletion.json');
        $fakeResponse = new Response(200, [], $fakeResponseBody);

        $this->sendRequestMock(static function () use ($fakeResponse) {
            return $fakeResponse;
        });

        try {
            $response = $apiCall();
        } catch (Exception $e) {
            $this->fail('Exception occurred during API call: ' . $e->getMessage());
        }

        self::assertNotNull($response, 'Response should not be null.');
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($fakeResponseBody, (string)$response->getBody());
    }

    /**
     * Mocks an API call with streaming support using a callable and a response file.
     *
     * Mocks the HTTP client to return a predefined streaming response loaded from a file,
     * and utilizes the provided stream callback to process the response.
     *
     * @param callable $apiCall       The API call to test.
     * @param callable $streamCallback The callback function to handle streaming data.
     *
     * @throws Exception
     */
    private function testApiCallWithStreaming(callable $apiCall, callable $streamCallback): void
    {
        $fakeResponseContent = TestHelper::loadResponseFromFile('chatCompletionStreaming.txt');
        $fakeChunks = \explode("\n", \trim($fakeResponseContent));
        $stream = \fopen('php://temp', 'rb+');

        foreach ($fakeChunks as $chunk) {
            \fwrite($stream, $chunk . "\n");
        }
        \rewind($stream);

        $fakeResponse = new Response(200, [], $stream);

        $this->sendRequestMock(static function () use ($fakeResponse) {
            return $fakeResponse;
        });

        try {
            $apiCall($streamCallback);
        } catch (Exception $e) {
            $this->fail('Exception occurred during streaming: ' . $e->getMessage());
        }
    }

    /**
     * Sets up a mock for the sendRequest method of the mocked client.
     *
     * @param callable $responseCallback A callable that returns a response or throws an exception.
     */
    private function sendRequestMock(callable $responseCallback): void
    {
        $this->mockedClient
            ->expects(self::once())
            ->method('sendRequest')
            ->willReturnCallback($responseCallback);
    }
}
