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

namespace SoftCreatR\PerplexityAI\Tests\Response;

use Error;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use SoftCreatR\PerplexityAI\Response\JsonResponse;
use SoftCreatR\PerplexityAI\Response\ModelResponse;

#[CoversClass(JsonResponse::class)]
#[CoversClass(ModelResponse::class)]
final class ModelResponseTest extends TestCase
{
    /** @throws ReflectionException */
    public function testClassIsReadOnly(): void
    {
        $this->assertTrue((new ReflectionClass(ModelResponse::class))->isReadOnly());
    }

    /**
     * @throws JsonException
     */
    public function testExposesCommonFieldsAndKeepsUnrecognizedData(): void
    {
        $data = [
            'id' => 'resp_123',
            'object' => 'response',
            'model' => 'gpt-6-luna',
            'status' => 'completed',
            'usage' => ['input_tokens' => 10, 'future_metric' => 20],
            'future_field' => ['enabled' => true],
            'output' => [
                ['type' => 'reasoning', 'summary' => []],
                ['type' => 'future_output_type', 'content' => [['type' => 'output_text', 'text' => 'Ignore.']]],
                ['type' => 'message', 'content' => [
                    ['type' => 'output_text', 'text' => 'Hello'],
                    ['type' => 'future_content_type', 'data' => true],
                    ['type' => 'refusal', 'refusal' => 'Cannot do that.'],
                    null,
                ]],
                ['type' => 'message', 'content' => [['type' => 'output_text', 'text' => ' world.']]],
                ['type' => 'message'],
                null,
                [],
            ],
        ];
        $raw = \json_encode($data, JSON_THROW_ON_ERROR);
        $httpResponse = new Response(200, ['x-request-id' => 'req_123'], $raw);
        $httpResponse->getBody()->seek(7);
        $dto = ModelResponse::fromResponse($httpResponse);

        $this->assertSame('resp_123', $dto->id);
        $this->assertSame('gpt-6-luna', $dto->model);
        $this->assertSame('completed', $dto->status);
        $this->assertSame($data['output'], $dto->output);
        $this->assertSame($data['usage'], $dto->usage);
        $this->assertTrue($dto->isCompleted());
        $this->assertSame('Hello world.', $dto->getOutputText());
        $this->assertSame(['Cannot do that.'], $dto->getRefusals());
        $this->assertSame($data, $dto->toArray());
        $this->assertSame($data, $dto->jsonSerialize());
        $this->assertSame($raw, \json_encode($dto, JSON_THROW_ON_ERROR));
        $this->assertSame($raw, $dto->getRawBody());
        $this->assertSame($httpResponse, $dto->getResponse());
        $this->assertSame('req_123', $dto->getRequestId());
        $this->assertSame(7, $httpResponse->getBody()->tell());
    }

    /**
     * @throws JsonException
     */
    public function testOptionalFieldsAndFutureStatusesAreAccepted(): void
    {
        $dto = $this->dto([]);
        $this->assertNull($dto->status);
        $this->assertNull($dto->usage);
        $this->assertNull($dto->getRequestId());
        $this->assertSame([], $dto->output);
        $this->assertSame('', $dto->getOutputText());
        $this->assertSame([], $dto->getRefusals());
        $this->assertFalse($dto->isCompleted());

        $future = $this->dto(['status' => 'future_status', 'usage' => null]);
        $this->assertSame('future_status', $future->status);
        $this->assertFalse($future->isCompleted());

        $null = $this->dto(['status' => null]);
        $this->assertNull($null->status);
    }

    /**
     * @throws JsonException
     * @throws ReflectionException
     */
    public function testDtoFieldsAreReadOnly(): void
    {
        $dto = $this->dto([]);
        $this->expectException(Error::class);

        (new ReflectionProperty($dto, 'id'))->setValue($dto, 'changed');
    }

    /**
     * @param array<string, mixed> $changes
     *
     * @throws JsonException
     */
    #[DataProvider('invalidFields')]
    public function testRejectsInvalidCommonFieldTypes(array $changes, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/' . \preg_quote($message, '/') . '/');
        $this->dto($changes);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function invalidFields(): iterable
    {
        yield 'missing id' => [['id' => null], 'Response field "id" must be a string.'];
        yield 'invalid id' => [['id' => 1], 'Response field "id" must be a string.'];
        yield 'invalid model' => [['model' => false], 'Response field "model" must be a string.'];
        yield 'wrong object' => [['object' => 'chat.completion'], 'Expected a Responses API model response.'];
        yield 'missing output' => [['output' => null], 'Response field "output" must be a list.'];
        yield 'scalar output' => [['output' => 'text'], 'Response field "output" must be a list.'];
        yield 'object output' => [['output' => ['item' => []]], 'Response field "output" must be a list.'];
        yield 'invalid status' => [['status' => 1], 'Response field "status" must be a string or null.'];
        yield 'invalid usage' => [['usage' => '10'], 'Response field "usage" must be an object or null.'];
    }

    /**
     * @throws JsonException
     */
    public function testRejectsMalformedMessageContent(): void
    {
        $dto = $this->dto(['output' => [['type' => 'message', 'content' => 'invalid']]]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/' . \preg_quote('Response message content must be an array.', '/') . '/');

        $dto->getOutputText();
    }

    /**
     * @throws JsonException
     */
    public function testRejectsMalformedRecognizedTextContent(): void
    {
        $dto = $this->dto(['output' => [[
            'type' => 'message',
            'content' => [['type' => 'output_text', 'text' => 42]],
        ]]]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/' . \preg_quote('Response content field "text" must be a string.', '/') . '/');

        $dto->getOutputText();
    }

    /**
     * @throws JsonException
     */
    public function testRejectsMissingRefusalContent(): void
    {
        $dto = $this->dto(['output' => [[
            'type' => 'message',
            'content' => [['type' => 'refusal']],
        ]]]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/' . \preg_quote('Response content field "refusal" must be a string.', '/') . '/');

        $dto->getRefusals();
    }

    /**
     * @param array<string, mixed> $changes
     *
     * @throws JsonException
     */
    private function dto(array $changes): ModelResponse
    {
        $data = \array_replace([
            'id' => 'resp_123',
            'object' => 'response',
            'model' => 'gpt-6-luna',
            'output' => [],
        ], $changes);

        return ModelResponse::fromJsonResponse(JsonResponse::fromJson(
            \json_encode($data, JSON_THROW_ON_ERROR),
            new Response(),
        ));
    }
}
