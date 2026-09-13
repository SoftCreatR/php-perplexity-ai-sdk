# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
