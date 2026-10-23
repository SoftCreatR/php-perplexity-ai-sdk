# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 4.0.0 - 2026-10-23

### Added

- Added Skills, Files, Batch, and Decisions endpoints, with grouped runnable examples and valid ZIP/JSONL fixtures.
- Added streaming multipart uploads using endpoint-declared file fields.
- Added optional `ResponseContract`, `JsonResponse`, and `ModelResponse` helpers for raw JSON snapshots, HTTP
  metadata, typed common response fields, text aggregation, refusals, and application-owned mapper callbacks.
- Added an optional JSONPayloadContract example and fixture. It remains a suggested dependency requiring PHP 8.3;
  it is not needed for the SDK's response wrappers.

### Changed

- Renamed Router methods and example directories to Orchestrator, targeting the current `/orchestrator/v1` routes.
  Replace `*Router*` method names with `*Orchestrator*` and `PERPLEXITY_ROUTER_MODEL` with `PERPLEXITY_ORCHESTRATOR_MODEL`.
- Updated Agent examples and fixtures to `openai/gpt-6.1-sol` with low reasoning effort and adequate output budgets.
  Streaming examples consume typed Agent SSE events.
- Updated development dependencies, the method catalog, and configuration documentation.
- Required PHP 8.2.35+, 8.3.36+, 8.4.27+, 8.5.12+, or 8.6.x, enforcing the minimum patch version for each branch.
- Aligned validation and release workflows: PHP 8.2.35 lowest dependencies, PHP 8.5 latest dependencies, experimental
  PHP 8.6, dependency auditing, static analysis, formatting, and enforced 100% test coverage.
- Releases derive their version from `[Release] x.y.z` commits or a validated manual dispatch instead of a static
  Composer version field. Existing push-based publishing remains supported.
- Replaced deprecated PHPUnit exception-message assertions with escaped regex assertions.
- Used PHP 8.2 readonly classes for response wrappers and the multipart builder, and literal return types in test helpers.
- Documented where Enterprise organization admins can generate the separate analytics API key.

### Removed

- Dropped PHP 8.1 support and support for older patch releases in the PHP 8.2 through 8.5 branches.
- Removed the `PERPLEXITYAI_API_KEY` example fallback; use `PERPLEXITY_API_KEY` instead.
- Removed private-helper compatibility fallbacks for inferred body types, omitted endpoint metadata, and boolean
  content types. Explicit parameter/body pairs remain supported for query/body separation.
- Removed `createChatCompletion`, `createAsyncChatCompletion`, `listAsyncChatCompletions`, and
  `retrieveAsyncChatCompletion`, and their Sonar examples, following the September 27, 2026 retirement.
  This is a breaking change. Use `createAgentResponse`, Agent background requests, and typed SSE events instead.
  See the official Sonar migration guide linked in the README.

## [3.0.0] - 2026-09-13

### Added

- Added the current Agent API, including response retrieval, cancellation, and generated-file access.
- Added Search, Embeddings, Contextualized Embeddings, asynchronous Sonar, model discovery, Router, authentication, and computer analytics operations.
- Added grouped, runnable examples for every registered operation and separate SSE examples.
- Added `request()` for dynamic calls and `PerplexityAIURLBuilder::getEndpoints()` for endpoint discovery.
- Added structured API error metadata through `getError()`, `getRequestId()`, `getResponseBody()`, and `getResponseHeaders()`.
- Added robust SSE decoding for comments, multiline events, mixed line endings, arbitrary chunks, and `[DONE]` termination.
- Added an optional `StreamingClientInterface` for transports that can expose unbuffered response bodies.
- Added endpoint-declared default query parameters, headers, authentication selection, and streaming behavior.
- Added full absolute base-URL support while retaining hostname and `basePath` configuration.
- Added PHPStan, dependency auditing, Composer archive filtering, and CI coverage for PHP 8.1 through PHP 8.5.

### Changed

- `createChatCompletion()` now targets the canonical `POST /v1/sonar` route instead of the compatibility alias `POST /chat/completions`.
- Endpoint calls now support a body-first form while retaining the existing parameters-first, body-second convention.
- Streaming calls now return their `ResponseInterface` after invoking the callback instead of returning `null`.
- Path parameters are URL encoded and remaining parameters are serialized as RFC 3986 query parameters.
- Dependency constraints are bounded to supported PSR, Guzzle, PHPUnit, PHPStan, and PHP-CS-Fixer versions.
- Standardized the shared transport, URL-builder, example-factory, quality-tooling, and documentation conventions
  used across the companion SDKs.
- Composer distribution archives now contain only `src/` and `composer.json`.

### Removed

- Removed outdated model names, documentation links, and references to retired API behavior.

## [2.0.0] - 2024-10-17

### Changed

- Dropped PHP 7.4 support; PHP 8.1 or newer is required.
- Replaced the URL builder's `apiVersion` parameter with `basePath`.

### Added

- Added callback-based streaming support for chat completions.
- Added synchronous and streaming chat completion examples.

## [1.1.0] - 2024-01-10

### Changed

- Removed the legacy completions endpoint and examples.
- Marked API keys as sensitive parameters.

## [1.0.0] - 2023-10-19

### Added

- Initial release with chat completion support.
