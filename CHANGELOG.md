# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2024-10-17

### Removed

- Dropped support for PHP 7.4. **PHP 8.1 or higher is now required**.
- Parameter `apiVersion` has been removed in favor of `basePath` in `OpenAIUrlBuilder::create()`.

### Added

- **Streaming Support**:
    - Added support for streaming responses in the `PerplexityAI` class, allowing real-time token generation for the `createChatCompletion` method and other applicable endpoints.
    - Implemented a callback mechanism for handling streamed data in real time.

- **New Examples**:
    - Created new example files to showcase API usage and functionality:
        - **Chat Completion with Streaming**: `examples/assistants/chat/createChatCompletion.php`
            - Demonstrates how to create a chat completion with the `gpt-4` model, featuring real-time response streaming.

- **Factory Updates**:
    - Added real-time processing of streamed content in the `PerplexityAIFactory::request` method.

## [1.1.0] - 2024-01-10

### Added

- Removed completions endpoint, and examples.
- Sensitive Parameter value redaction (API Key)


## [1.0.0] - 2023-10-19

### Added

- Initial release of the Perplexity AI PHP library.
- Basic implementation for making API calls to the pplx API.
- Unit tests for the initial implementation.
