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

require_once \dirname(__DIR__) . '/PerplexityAIFactory.php';

use SoftCreatR\PerplexityAI\Response\ModelResponse;

$client = PerplexityAIFactory::create(PerplexityAIFactory::env('PERPLEXITY_API_KEY'));
$response = $client->createAgentResponse([
    'model' => 'openai/gpt-6.1-sol',
    'reasoning' => ['effort' => 'low'],
    'input' => 'Explain PSR-18 in one sentence.',
    'max_output_tokens' => 1024,
]);

if ($response === null) {
    throw new RuntimeException('Perplexity did not return an HTTP response.');
}

$dto = ModelResponse::fromResponse($response);

echo json_encode([
    'id' => $dto->id,
    'model' => $dto->model,
    'status' => $dto->status,
    'request_id' => $dto->getRequestId(),
    'text' => $dto->getOutputText(),
    'refusals' => $dto->getRefusals(),
    'usage' => $dto->usage,
], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . PHP_EOL;
