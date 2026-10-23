# Perplexity API SDK for PHP

[![Release](https://img.shields.io/github/actions/workflow/status/SoftCreatR/php-perplexity-ai-sdk/.github/workflows/create-release.yml?branch=main&label=release)](https://github.com/SoftCreatR/php-perplexity-ai-sdk/actions/workflows/create-release.yml)
[![Latest Release](https://img.shields.io/packagist/v/softcreatr/php-perplexity-ai-sdk)](https://packagist.org/packages/softcreatr/php-perplexity-ai-sdk)
[![PHP](https://img.shields.io/packagist/dependency-v/softcreatr/php-perplexity-ai-sdk/php)](composer.json)
[![License](https://img.shields.io/badge/license-ISC-blue.svg)](LICENSE.md)

A lightweight PSR-17/PSR-18 client for the current [Perplexity API](https://docs.perplexity.ai/api-reference), with
examples for every exposed SDK method, multipart uploads, SSE streaming, structured errors, optional response
wrappers, compatible endpoint overrides, and separate organization analytics authentication.

## Requirements

- PHP 8.2.35+, 8.3.36+, 8.4.27+, 8.5.12+, or 8.6.x. Earlier patch releases in each branch are not supported.
  CI tests PHP 8.2.35 with lowest dependencies and the latest PHP 8.5 release with current dependencies.
  PHP 8.6 is an experimental, non-blocking job.
- A PSR-17 request, stream, and URI factory.
- A PSR-18 HTTP client.
- The JSON extension.

Guzzle is used below because it provides both the PSR-17 factories and PSR-18 client implementation. The SDK itself
depends only on the PSR interfaces, so other compliant implementations remain supported.

## Installation

```bash
composer require softcreatr/php-perplexity-ai-sdk guzzlehttp/guzzle
```

## Client Setup

```php
<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use SoftCreatR\PerplexityAI\PerplexityAI;

$factory = new HttpFactory();
$perplexity = new PerplexityAI(
    requestFactory: $factory,
    streamFactory: $factory,
    uriFactory: $factory,
    httpClient: new Client(['stream' => true]),
    apiKey: (string) getenv('PERPLEXITY_API_KEY'),
);
```

Keep API keys on the server and out of source control.

## Agent API

The Agent API provides model and tool orchestration through a unified response interface.

Model identifiers include the provider prefix. For example, `openai/gpt-6.1-sol` selects an OpenAI model through
Perplexity; the request still uses your Perplexity API key and endpoint. See the
[Agent model catalog](https://docs.perplexity.ai/docs/agent-api/models) for supported models.

```php
use const JSON_THROW_ON_ERROR;

$response = $perplexity->createAgentResponse([
    'model' => 'openai/gpt-6.1-sol',
    'reasoning' => ['effort' => 'low'],
    'input' => 'Give me a one-sentence summary of PSR-18.',
]);

$result = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
```

Endpoint methods return a PSR-7 `ResponseInterface`, including calls that deliver SSE events to a callback.

## Arguments

Body-only endpoints use a single body array:

```php
$perplexity->createEmbedding([
    'model' => 'pplx-embed-v1-0.6b',
    'input' => 'A sentence to embed.',
]);
```

For endpoints with path parameters, a single combined array is the preferred form. Path fields are separated from
query or body fields using the endpoint template:

```php
$perplexity->retrieveAgentResponse([
    'id' => 'resp_abc123',
]);
```

Use separate parameter and body arrays when an endpoint needs both query parameters and a request body:

```php
$perplexity->updateSkill(
    ['skill_id' => 'skill_abc123', 'expected_revision' => 'revision_abc123'],
    ['file' => __DIR__ . '/examples/skills/fixtures/skills.zip'],
);
```

For `GET` and `DELETE` methods, non-path values become RFC 3986 query parameters. Path parameter values are URL
encoded. The explicit form is available when method names are determined at runtime:

```php
$response = $perplexity->request(
    'retrieveAgentResponse',
    ['id' => 'resp_abc123'],
    customHeaders: ['X-Trace-Id' => 'trace_abc123'],
);
```

## Streaming

Set `stream` to `true` and pass a callback. The decoder supports arbitrarily split chunks, CRLF and LF delimiters,
comments, multiline data fields, final unterminated frames, and `[DONE]`.

```php
$perplexity->createAgentResponse(
    [
        'model' => 'openai/gpt-6.1-sol',
        'reasoning' => ['effort' => 'low'],
        'input' => 'Write a short haiku about PHP.',
        'stream' => true,
    ],
    static function (array $event): void {
        if (($event['type'] ?? null) === 'response.output_text.delta') {
            echo $event['delta'] ?? '';
        }
    },
);
```

An ordinary JSON response is still returned when a callback is supplied without requesting an SSE stream. For a
transport that exposes a response before buffering its complete body, implement `StreamingClientInterface`. Other
PSR-18 clients remain supported and use their normal `sendRequest()` behavior.

## Errors

4xx and 5xx responses throw `PerplexityAIException`. The exception keeps the parsed API error, raw response body,
response headers, status code, and `x-request-id` for diagnostics.

```php
use SoftCreatR\PerplexityAI\Exception\PerplexityAIException;

try {
    $perplexity->listModels();
} catch (PerplexityAIException $exception) {
    error_log(sprintf(
        'Perplexity request %s failed (%d): %s',
        $exception->getRequestId() ?? 'unknown',
        $exception->getCode(),
        $exception->getMessage(),
    ));
}
```

PSR-18 transport failures are wrapped in `PerplexityAIException` and retain the original exception as `getPrevious()`.

## Multipart Uploads

Pass readable local file paths in endpoint-declared file fields. The SDK streams raw file bytes into a temporary
multipart body; it does not base64 encode uploads. For example:

```php
$response = $perplexity->uploadFile([
    'purpose' => 'batch',
    'file' => __DIR__ . '/examples/files/fixtures/batch.jsonl',
]);
```

Skills use a ZIP containing one `SKILL.md`. The examples include a working `basic-math` source directory and
`skills.zip`. Updates and deletion require the current opaque `expected_revision` to prevent concurrent writes:

```php
$response = $perplexity->updateSkill(
    ['skill_id' => 'skill_abc123', 'expected_revision' => $revision],
    ['file' => __DIR__ . '/examples/skills/fixtures/skills.zip'],
);
```

Use the two-array convention when a body-bearing operation also needs query parameters. File uploads are for
batch JSONL input (`purpose=batch`). Batch requests target `/v1/responses` inside JSONL, an Agent API alias;
ordinary Agent requests continue to use `/v1/agent`.

## Response Handling

Endpoint methods return raw PSR-7 responses. Optional wrappers implement `ResponseContract` and expose
`toArray()`, `getRawBody()`, `getResponse()`, and `getRequestId()`, as well as JSON serialization. They follow the SDK's
PHP requirements and add no dependencies.

`ModelResponse` is a DTO for Agent and Orchestrator Responses API model responses. It provides readonly `id`, `model`,
`status`, `output`, and `usage` properties, aggregates text from message outputs, and exposes refusals separately:

```php
use SoftCreatR\PerplexityAI\Response\ModelResponse;

$dto = ModelResponse::fromResponse($response);

if ($dto->isCompleted()) {
    echo $dto->getOutputText();
}

$refusals = $dto->getRefusals();
```

The DTO checks common field types but accepts new status strings, output types, and response properties. The original
payload remains available through `toArray()` and `getRawBody()`. Missing or null status and usage are preserved as
null. See the [live DTO example](examples/response-handling/createResponseDto.php).

`JsonResponse` wraps any JSON object or array response. Its `map()` method passes decoded data to your DTO factory;
`mapJson()` passes the exact original JSON to a validator or mapper. Seekable response streams retain their original
position. Non-seekable streams are consumed: wrap them before reading, then use the captured `getRawBody()`. If you
already captured a body, use `JsonResponse::fromJson($rawBody, $response)` to avoid reading the stream again. Invalid
JSON throws `JsonException`; invalid wrapper or DTO input throws `InvalidArgumentException`.

### Optional Payload Contracts

[JSONPayloadContract](https://github.com/SoftCreatR/JSONPayloadContract) can validate and normalize selected fields
before constructing application DTOs. Install it explicitly on PHP 8.3 or newer:

```bash
composer require softcreatr/json-payload-contract:^1.0
```

```php
use SoftCreatR\JsonPayloadContract\Contract;
use SoftCreatR\JsonPayloadContract\Field;
use SoftCreatR\PerplexityAI\Response\JsonResponse;

$contract = Contract::define([
    'responseId' => Field::required('$.id')->string(),
    'textParts' => Field::many(
        '$.output[?(@.type == "message")].content[?(@.type == "output_text")].text',
    )->string(),
]);

$data = JsonResponse::fromResponse($response)->mapJson($contract->applyJson(...));
```

Using raw JSON lets the contract distinguish objects from arrays and detect duplicate object members. Pass
`$contract->extractJson(...)` instead to receive a diagnostic `Result` without throwing for invalid payloads. The
[fixture-based contract example](examples/response-handling/withJsonPayloadContract.php) also validates generated
JSON and constructs an application DTO without making an API request.

HTTP error handling and SSE JSON decoding remain independent of these wrappers.
Apply application schemas and business rules explicitly. For model-generated JSON, use
[Structured Outputs](https://docs.perplexity.ai/docs/agent-api/building-agents/shape-output) and handle refusals and
incomplete responses before using the generated data. Streaming callbacks remain plain event arrays; contracts can
be applied to selected events with `apply()` or `extract()`.

## Examples

Examples load the ignored project-level `.env` through `examples/PerplexityAIFactory.php`:

```bash
cp .env.example .env
php examples/agent/createAgentResponse.php
```

Set `PERPLEXITY_ANALYTICS_API_KEY` only when running examples under `examples/analytics`. An Enterprise organization
admin can generate this separate key in **Settings > Organization > Computer > Analytics** by enabling **Analytics API**
and selecting **Generate key**. It is not a regular project API key. If the toggle is missing, contact your Perplexity
representative. See the [Analytics API setup guide](https://docs.perplexity.ai/docs/admin/computer-analytics-api#getting-access).
Only one analytics key can be active per organization; regenerating it revokes the previous key.

Resource examples use
`PERPLEXITY_AGENT_RESPONSE_ID`, `PERPLEXITY_AGENT_FILE_ID`, `PERPLEXITY_SKILL_ID`, `PERPLEXITY_SKILL_REVISION`,
`PERPLEXITY_FILE_ID`, and `PERPLEXITY_BATCH_ID`. The optional mapper example uses a local fixture and does not
make an API call. Review upload, cancellation, deletion, and authentication examples before running them.

Use Agent effort levels such as `fast` instead of retired Sonar models, or select a provider-prefixed `model`.
Direct model requests do not enable web search by default; add the desired tools explicitly. Orchestrator access
is currently limited preview and requires approval. Requests use the documented `/orchestrator/v1` routes through
the `*Orchestrator*` methods and the examples under `examples/orchestrator`.

## Supported Methods

The catalog follows the current [Perplexity API reference](https://docs.perplexity.ai/api-reference).
The retired Sonar and asynchronous Sonar endpoints are intentionally absent.

### Agent

| SDK method                  | HTTP route                                   | Request body | Example                                             |
|-----------------------------|----------------------------------------------|--------------|-----------------------------------------------------|
| `createAgentResponse`       | `POST /v1/agent`                             | `json`       | [PHP](examples/agent/createAgentResponse.php)       |
| `retrieveAgentResponse`     | `GET /v1/agent/{id}`                         | `none`       | [PHP](examples/agent/retrieveAgentResponse.php)     |
| `listAgentResponseFiles`    | `GET /v1/agent/{id}/files`                   | `none`       | [PHP](examples/agent/listAgentResponseFiles.php)    |
| `downloadAgentResponseFile` | `GET /v1/agent/{id}/files/{file_id}/content` | `none`       | [PHP](examples/agent/downloadAgentResponseFile.php) |
| `cancelAgentResponse`       | `POST /v1/agent/{id}/cancel`                 | `none`       | [PHP](examples/agent/cancelAgentResponse.php)       |

### Search

| SDK method | HTTP route     | Request body | Example                           |
|------------|----------------|--------------|-----------------------------------|
| `search`   | `POST /search` | `json`       | [PHP](examples/search/search.php) |

### Embeddings

| SDK method                      | HTTP route                          | Request body | Example                                                      |
|---------------------------------|-------------------------------------|--------------|--------------------------------------------------------------|
| `createEmbedding`               | `POST /v1/embeddings`               | `json`       | [PHP](examples/embeddings/createEmbedding.php)               |
| `createContextualizedEmbedding` | `POST /v1/contextualizedembeddings` | `json`       | [PHP](examples/embeddings/createContextualizedEmbedding.php) |

### Models

| SDK method   | HTTP route       | Request body | Example                               |
|--------------|------------------|--------------|---------------------------------------|
| `listModels` | `GET /v1/models` | `none`       | [PHP](examples/models/listModels.php) |

### Orchestrator

| SDK method                         | HTTP route                               | Request body | Example                                                                |
|------------------------------------|------------------------------------------|--------------|------------------------------------------------------------------------|
| `createOrchestratorChatCompletion` | `POST /orchestrator/v1/chat/completions` | `json`       | [PHP](examples/orchestrator/chat/createOrchestratorChatCompletion.php) |
| `createOrchestratorMessage`        | `POST /orchestrator/v1/messages`         | `json`       | [PHP](examples/orchestrator/messages/createOrchestratorMessage.php)    |
| `createOrchestratorResponse`       | `POST /orchestrator/v1/responses`        | `json`       | [PHP](examples/orchestrator/responses/createOrchestratorResponse.php)  |
| `listOrchestratorModels`           | `GET /orchestrator/v1/models`            | `none`       | [PHP](examples/orchestrator/models/listOrchestratorModels.php)         |
| `retrieveOrchestratorModel`        | `GET /orchestrator/v1/models/{model}`    | `none`       | [PHP](examples/orchestrator/models/retrieveOrchestratorModel.php)      |

### Skills

| SDK method           | HTTP route                            | Request body | Example                                       |
|----------------------|---------------------------------------|--------------|-----------------------------------------------|
| `createSkill`        | `POST /v1/skills`                     | `multipart`  | [PHP](examples/skills/createSkill.php)        |
| `listSkills`         | `GET /v1/skills`                      | `none`       | [PHP](examples/skills/listSkills.php)         |
| `retrieveSkill`      | `GET /v1/skills/{skill_id}`           | `none`       | [PHP](examples/skills/retrieveSkill.php)      |
| `updateSkill`        | `PUT /v1/skills/{skill_id}`           | `multipart`  | [PHP](examples/skills/updateSkill.php)        |
| `listSkillRevisions` | `GET /v1/skills/{skill_id}/revisions` | `none`       | [PHP](examples/skills/listSkillRevisions.php) |
| `downloadSkill`      | `GET /v1/skills/{skill_id}/download`  | `none`       | [PHP](examples/skills/downloadSkill.php)      |
| `deleteSkill`        | `DELETE /v1/skills/{skill_id}`        | `none`       | [PHP](examples/skills/deleteSkill.php)        |

### Files

| SDK method            | HTTP route                        | Request body | Example                                       |
|-----------------------|-----------------------------------|--------------|-----------------------------------------------|
| `uploadFile`          | `POST /v1/files`                  | `multipart`  | [PHP](examples/files/uploadFile.php)          |
| `listFiles`           | `GET /v1/files`                   | `none`       | [PHP](examples/files/listFiles.php)           |
| `retrieveFile`        | `GET /v1/files/{file_id}`         | `none`       | [PHP](examples/files/retrieveFile.php)        |
| `downloadFileContent` | `GET /v1/files/{file_id}/content` | `none`       | [PHP](examples/files/downloadFileContent.php) |
| `deleteFile`          | `DELETE /v1/files/{file_id}`      | `none`       | [PHP](examples/files/deleteFile.php)          |

### Batches

| SDK method      | HTTP route                           | Request body | Example                                   |
|-----------------|--------------------------------------|--------------|-------------------------------------------|
| `createBatch`   | `POST /v1/batches`                   | `json`       | [PHP](examples/batches/createBatch.php)   |
| `listBatches`   | `GET /v1/batches`                    | `none`       | [PHP](examples/batches/listBatches.php)   |
| `retrieveBatch` | `GET /v1/batches/{batch_id}`         | `none`       | [PHP](examples/batches/retrieveBatch.php) |
| `cancelBatch`   | `POST /v1/batches/{batch_id}/cancel` | `none`       | [PHP](examples/batches/cancelBatch.php)   |

### Decisions

| SDK method       | HTTP route           | Request body | Example                                      |
|------------------|----------------------|--------------|----------------------------------------------|
| `createDecision` | `POST /v1/decisions` | `json`       | [PHP](examples/decisions/createDecision.php) |

### Authentication

| SDK method          | HTTP route                  | Request body | Example                                              |
|---------------------|-----------------------------|--------------|------------------------------------------------------|
| `generateAuthToken` | `POST /generate_auth_token` | `json`       | [PHP](examples/authentication/generateAuthToken.php) |
| `revokeAuthToken`   | `POST /revoke_auth_token`   | `json`       | [PHP](examples/authentication/revokeAuthToken.php)   |

### Analytics

| SDK method                    | HTTP route                         | Request body | Example                                                   |
|-------------------------------|------------------------------------|--------------|-----------------------------------------------------------|
| `getComputerUsageAnalytics`   | `GET /v1/analytics/computer/usage` | `none`       | [PHP](examples/analytics/getComputerUsageAnalytics.php)   |
| `getComputerUsageAnalyticsV2` | `GET /v2/analytics/computer/usage` | `none`       | [PHP](examples/analytics/getComputerUsageAnalyticsV2.php) |

## Custom API Origin

Pass a hostname or absolute base URL through the constructor's `origin` argument. `basePath` overrides any path already
present in an absolute origin. The examples read `PERPLEXITY_API_ORIGIN` and `PERPLEXITY_API_BASE_PATH` from `.env`.

## Migration Notes

PHP 8.1 is no longer supported. Each supported PHP branch has its own minimum patch version listed above.
Rename `createRouterChatCompletion`, `createRouterMessage`, `createRouterResponse`, `listRouterModels`, and
`retrieveRouterModel` to their `*Orchestrator*` equivalents. Move example references from `examples/router` to
`examples/orchestrator`, rename `PERPLEXITY_ROUTER_MODEL` to `PERPLEXITY_ORCHESTRATOR_MODEL`, and replace the old
`PERPLEXITYAI_API_KEY` environment variable with `PERPLEXITY_API_KEY`. There are no legacy aliases or fallbacks.
Body-first endpoint calls and explicit parameter/body pairs remain supported; the latter separates query parameters
such as `expected_revision` from the request body.

Sonar retired on September 27, 2026. Replace `createChatCompletion()` and asynchronous Sonar calls with
`createAgentResponse()`. Map `messages` to Agent `input`, use `effort` or a supported provider-prefixed `model`, and
consume typed `response.output_text.delta` events for streaming. Background Agent requests replace asynchronous
Sonar jobs. See the [official migration guide](https://docs.perplexity.ai/docs/agent-api/migrate-from-sonar/how-to)
and [CHANGELOG.md](CHANGELOG.md) for breaking changes.

## Development

```bash
composer install
composer test
composer analyse
vendor/bin/php-cs-fixer fix --dry-run --diff
```

## License

Licensed under the [ISC License](LICENSE.md).
