# Perplexity API SDK for PHP

[![Tests](https://img.shields.io/github/actions/workflow/status/SoftCreatR/php-perplexity-ai-sdk/.github/workflows/validate-pr.yml?branch=main&label=tests)](https://github.com/SoftCreatR/php-perplexity-ai-sdk/actions/workflows/validate-pr.yml)
[![Latest Release](https://img.shields.io/packagist/v/softcreatr/php-perplexity-ai-sdk)](https://packagist.org/packages/softcreatr/php-perplexity-ai-sdk)
[![PHP](https://img.shields.io/packagist/dependency-v/softcreatr/php-perplexity-ai-sdk/php)](composer.json)
[![License](https://img.shields.io/badge/license-ISC-blue.svg)](LICENSE.md)

A lightweight PSR-17/PSR-18 client for the current [Perplexity API](https://docs.perplexity.ai/api-reference), with
examples for every exposed SDK method, SSE streaming, structured errors, compatible endpoint overrides, and separate
organization analytics authentication.

## Requirements

- PHP 8.1 or newer. CI tests PHP 8.1, 8.2, 8.3, 8.4, and 8.5.
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

```php
use const JSON_THROW_ON_ERROR;

$response = $perplexity->createAgentResponse([
    'model' => 'openai/gpt-5-mini',
    'input' => 'Give me a one-sentence summary of PSR-18.',
]);

$result = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
```

Endpoint methods return a PSR-7 `ResponseInterface`, including calls that deliver SSE events to a callback.

## Sonar API

Sonar provides Perplexity's search-grounded chat completions.

```php
$response = $perplexity->createChatCompletion([
    'model' => 'sonar',
    'messages' => [
        ['role' => 'user', 'content' => 'When did Wolfgang Amadeus Mozart die?'],
    ],
]);
```

## Arguments

Body-only endpoints use a single body array:

```php
$perplexity->createEmbedding([
    'model' => 'pplx-embed-v1-0.6b',
    'input' => 'A sentence to embed.',
]);
```

For endpoints with path parameters, a single combined array is the preferred v3 form. Path fields are separated from
query or body fields using the endpoint template:

```php
$perplexity->retrieveAgentResponse([
    'id' => 'resp_abc123',
]);
```

The v2 two-array form remains supported for existing integrations:

```php
$perplexity->createChatCompletion([], [
    'model' => 'sonar',
    'messages' => [['role' => 'user', 'content' => 'Summarize PSR-18.']],
]);
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
$perplexity->createChatCompletion(
    [
        'model' => 'sonar',
        'messages' => [['role' => 'user', 'content' => 'Write a short haiku about PHP.']],
        'stream' => true,
    ],
    static function (array $event): void {
        echo $event['choices'][0]['delta']['content'] ?? '';
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

## Examples

Examples load the ignored project-level `.env` through `examples/PerplexityAIFactory.php`:

```bash
cp .env.example .env
php examples/sonar/createChatCompletion.php
```

Set `PERPLEXITY_ANALYTICS_API_KEY` only when running examples under `examples/analytics`.

## Supported Methods

The catalog follows the current [Perplexity API reference](https://docs.perplexity.ai/api-reference).

### Agent

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `createAgentResponse` | `POST /v1/agent` | `json` | [PHP](examples/agent/createAgentResponse.php) |
| `retrieveAgentResponse` | `GET /v1/agent/{id}` | `none` | [PHP](examples/agent/retrieveAgentResponse.php) |
| `listAgentResponseFiles` | `GET /v1/agent/{id}/files` | `none` | [PHP](examples/agent/listAgentResponseFiles.php) |
| `downloadAgentResponseFile` | `GET /v1/agent/{id}/files/{file_id}/content` | `none` | [PHP](examples/agent/downloadAgentResponseFile.php) |
| `cancelAgentResponse` | `POST /v1/agent/{id}/cancel` | `none` | [PHP](examples/agent/cancelAgentResponse.php) |

### Sonar

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `createChatCompletion` | `POST /v1/sonar` | `json` | [PHP](examples/sonar/createChatCompletion.php) |
| `createAsyncChatCompletion` | `POST /v1/async/sonar` | `json` | [PHP](examples/sonar/async/createAsyncChatCompletion.php) |
| `listAsyncChatCompletions` | `GET /v1/async/sonar` | `none` | [PHP](examples/sonar/async/listAsyncChatCompletions.php) |
| `retrieveAsyncChatCompletion` | `GET /v1/async/sonar/{api_request}` | `none` | [PHP](examples/sonar/async/retrieveAsyncChatCompletion.php) |

### Search

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `search` | `POST /search` | `json` | [PHP](examples/search/search.php) |

### Embeddings

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `createEmbedding` | `POST /v1/embeddings` | `json` | [PHP](examples/embeddings/createEmbedding.php) |
| `createContextualizedEmbedding` | `POST /v1/contextualizedembeddings` | `json` | [PHP](examples/embeddings/createContextualizedEmbedding.php) |

### Models

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `listModels` | `GET /v1/models` | `none` | [PHP](examples/models/listModels.php) |

### Router

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `createRouterChatCompletion` | `POST /router/v1/chat/completions` | `json` | [PHP](examples/router/chat/createRouterChatCompletion.php) |
| `createRouterMessage` | `POST /router/v1/messages` | `json` | [PHP](examples/router/messages/createRouterMessage.php) |
| `createRouterResponse` | `POST /router/v1/responses` | `json` | [PHP](examples/router/responses/createRouterResponse.php) |
| `listRouterModels` | `GET /router/v1/models` | `none` | [PHP](examples/router/models/listRouterModels.php) |
| `retrieveRouterModel` | `GET /router/v1/models/{model}` | `none` | [PHP](examples/router/models/retrieveRouterModel.php) |

### Authentication

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `generateAuthToken` | `POST /generate_auth_token` | `json` | [PHP](examples/authentication/generateAuthToken.php) |
| `revokeAuthToken` | `POST /revoke_auth_token` | `json` | [PHP](examples/authentication/revokeAuthToken.php) |

These operations manage live credentials. Review the examples before running them.

### Analytics

| SDK method | HTTP route | Request body | Example |
| --- | --- | --- | --- |
| `getComputerUsageAnalytics` | `GET /v1/analytics/computer/usage` | `none` | [PHP](examples/analytics/getComputerUsageAnalytics.php) |
| `getComputerUsageAnalyticsV2` | `GET /v2/analytics/computer/usage` | `none` | [PHP](examples/analytics/getComputerUsageAnalyticsV2.php) |

Analytics endpoints require a separate organization analytics API key. The example factory reads it from
`PERPLEXITY_ANALYTICS_API_KEY`.

## Custom API Origin

Pass a hostname or absolute base URL through the constructor's `origin` argument. `basePath` overrides any path already
present in an absolute origin. The examples read `PERPLEXITY_API_ORIGIN` and `PERPLEXITY_API_BASE_PATH` from `.env`.

## Development

```bash
composer install
composer test
composer analyse
vendor/bin/php-cs-fixer fix --dry-run --diff
```

## License

Licensed under the [ISC License](LICENSE.md).
