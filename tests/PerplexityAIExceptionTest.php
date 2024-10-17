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
use JsonException;
use PHPUnit\Framework\TestCase;
use SoftCreatR\PerplexityAI\Exception\PerplexityAIException;

use const JSON_THROW_ON_ERROR;

/**
 * @covers \SoftCreatR\PerplexityAI\Exception\PerplexityAIException
 */
final class PerplexityAIExceptionTest extends TestCase
{
    /**
     * Tests that the exception sets a default message when constructed with an empty message.
     */
    public function testConstructWithEmptyMessage(): void
    {
        $exception = new PerplexityAIException(null);
        $this->assertEquals('An unknown error occurred', $exception->getMessage());

        $exceptionEmptyString = new PerplexityAIException('');
        $this->assertEquals('An unknown error occurred', $exceptionEmptyString->getMessage());
    }

    /**
     * Tests that the exception extracts the error message from a valid JSON string containing 'error.message'.
     *
     * @throws JsonException
     */
    public function testConstructWithValidJsonErrorMessage(): void
    {
        $jsonErrorMessage = \json_encode([
            'error' => [
                'message' => 'Invalid API key provided.',
                'type' => 'authentication_error',
                'param' => null,
                'code' => 'invalid_api_key',
            ],
        ], JSON_THROW_ON_ERROR);

        $exception = new PerplexityAIException($jsonErrorMessage);
        $this->assertEquals('Invalid API key provided.', $exception->getMessage());
    }

    /**
     * Tests that the exception retains the original message when provided with invalid JSON.
     */
    public function testConstructWithInvalidJson(): void
    {
        $invalidJson = 'This is not a JSON string.';

        $exception = new PerplexityAIException($invalidJson);
        $this->assertEquals($invalidJson, $exception->getMessage());
    }

    /**
     * Tests that the exception retains the original message when JSON does not contain 'error.message'.
     *
     * @throws JsonException
     */
    public function testConstructWithJsonWithoutErrorMessage(): void
    {
        $jsonWithoutErrorMessage = \json_encode([
            'error' => [
                'type' => 'invalid_request_error',
                'param' => 'model',
                'code' => 'invalid_model',
            ],
        ], JSON_THROW_ON_ERROR);

        $exception = new PerplexityAIException($jsonWithoutErrorMessage);
        $this->assertEquals($jsonWithoutErrorMessage, $exception->getMessage());
    }

    /**
     * Tests that the exception retains the original message when 'error.message' is not a string.
     *
     * @throws JsonException
     */
    public function testConstructWithNonStringErrorMessage(): void
    {
        $jsonWithNonStringErrorMessage = \json_encode([
            'error' => [
                'message' => ['This', 'is', 'an', 'array'],
                'type' => 'invalid_request_error',
            ],
        ], JSON_THROW_ON_ERROR);

        $exception = new PerplexityAIException($jsonWithNonStringErrorMessage);
        $this->assertEquals($jsonWithNonStringErrorMessage, $exception->getMessage());
    }

    /**
     * Tests that the exception retains the original message when 'error' key is missing.
     *
     * @throws JsonException
     */
    public function testConstructWithMissingErrorKey(): void
    {
        $jsonMissingErrorKey = \json_encode(['message' => 'A general error occurred.'], JSON_THROW_ON_ERROR);

        $exception = new PerplexityAIException($jsonMissingErrorKey);
        $this->assertEquals($jsonMissingErrorKey, $exception->getMessage());
    }

    /**
     * Tests that the exception sets the correct code.
     */
    public function testConstructSetsCode(): void
    {
        $exception = new PerplexityAIException('Error message', 123);
        $this->assertEquals(123, $exception->getCode());
    }

    /**
     * Tests that the exception chains the previous throwable correctly.
     */
    public function testConstructChainsPreviousThrowable(): void
    {
        $previous = new Exception('Previous exception');
        $exception = new PerplexityAIException('Error message', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    /**
     * Tests that the exception handles JSON_THROW_ON_ERROR correctly by providing malformed JSON.
     */
    public function testConstructWithMalformedJsonThrowsNoException(): void
    {
        // Since JSON_THROW_ON_ERROR is used internally, malformed JSON will not throw here,
        // but the original message should be retained.
        $malformedJson = '{"error": {"message": "Invalid JSON",}';

        $exception = new PerplexityAIException($malformedJson);
        $this->assertEquals($malformedJson, $exception->getMessage());
    }

    /**
     * Tests that the exception correctly handles a non-empty, non-JSON message.
     */
    public function testConstructWithNonEmptyNonJsonMessage(): void
    {
        $message = 'A simple error message.';
        $exception = new PerplexityAIException($message);
        $this->assertEquals($message, $exception->getMessage());
    }
}
