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

namespace SoftCreatR\PerplexityAI\Response;

use InvalidArgumentException;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use SoftCreatR\PerplexityAI\Contracts\ResponseContract;

/**
 * Optional DTO for Responses API model responses, preserving unknown fields and output types.
 */
final readonly class ModelResponse implements ResponseContract
{
    public string $id;

    public string $model;

    public ?string $status;

    /**
     * @var list<mixed>
     */
    public array $output;

    /**
     * @var array<array-key, mixed>|null
     */
    public ?array $usage;

    /**
     * @throws InvalidArgumentException If a common response field has an invalid type.
     */
    private function __construct(private ResponseContract $response)
    {
        $data = $response->toArray();

        foreach (['id', 'model'] as $field) {
            if (!isset($data[$field]) || !\is_string($data[$field])) {
                throw new InvalidArgumentException("Response field \"{$field}\" must be a string.");
            }
        }

        if (($data['object'] ?? null) !== 'response') {
            throw new InvalidArgumentException('Expected a Responses API model response.');
        }

        if (!isset($data['output']) || !\is_array($data['output']) || !\array_is_list($data['output'])) {
            throw new InvalidArgumentException('Response field "output" must be a list.');
        }

        $status = $data['status'] ?? null;
        $usage = $data['usage'] ?? null;

        if ($status !== null && !\is_string($status)) {
            throw new InvalidArgumentException('Response field "status" must be a string or null.');
        }

        if ($usage !== null && !\is_array($usage)) {
            throw new InvalidArgumentException('Response field "usage" must be an object or null.');
        }

        $this->id = $data['id'];
        $this->model = $data['model'];
        $this->status = $status;
        $this->output = $data['output'];
        $this->usage = $usage;
    }

    /**
     * @throws JsonException If the body is invalid JSON.
     * @throws InvalidArgumentException If common response fields have invalid types.
     * @throws RuntimeException If the response stream cannot be read or repositioned.
     */
    public static function fromResponse(ResponseInterface $response): self
    {
        return self::fromJsonResponse(JsonResponse::fromResponse($response));
    }

    /**
     * @throws InvalidArgumentException If common response fields have invalid types.
     */
    public static function fromJsonResponse(ResponseContract $response): self
    {
        return new self($response);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Aggregates text from all message outputs, ignoring reasoning and unknown output types.
     *
     * @throws InvalidArgumentException If a recognized content value is not a string.
     */
    public function getOutputText(): string
    {
        return \implode('', $this->getContentValues('output_text', 'text'));
    }

    /**
     * @return list<string>
     *
     * @throws InvalidArgumentException If a refusal value is not a string.
     */
    public function getRefusals(): array
    {
        return $this->getContentValues('refusal', 'refusal');
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response->getResponse();
    }

    public function getRawBody(): string
    {
        return $this->response->getRawBody();
    }

    public function getRequestId(): ?string
    {
        return $this->response->getRequestId();
    }

    /**
     * @return array<array-key, mixed>
     */
    public function toArray(): array
    {
        return $this->response->toArray();
    }

    /**
     * @return array<array-key, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @return list<string>
     *
     * @throws InvalidArgumentException If a recognized content value is not a string.
     */
    private function getContentValues(string $type, string $field): array
    {
        $values = [];

        foreach ($this->output as $item) {
            if (!\is_array($item) || ($item['type'] ?? null) !== 'message') {
                continue;
            }

            $content = $item['content'] ?? [];

            if (!\is_array($content)) {
                throw new InvalidArgumentException('Response message content must be an array.');
            }

            foreach ($content as $part) {
                if (!\is_array($part) || ($part['type'] ?? null) !== $type) {
                    continue;
                }

                if (!isset($part[$field]) || !\is_string($part[$field])) {
                    throw new InvalidArgumentException("Response content field \"{$field}\" must be a string.");
                }

                $values[] = $part[$field];
            }
        }

        return $values;
    }
}
