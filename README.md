# PerplexityAI API Wrapper for PHP

[![Build](https://img.shields.io/github/actions/workflow/status/SoftCreatR/php-perplexity-ai-sdk/.github/workflows/create-release.yml?branch=main)](https://github.com/SoftCreatR/php-perplexity-ai-sdk/actions/workflows/create-release.yml) [![Latest Release](https://img.shields.io/packagist/v/SoftCreatR/php-perplexity-ai-sdk?color=blue&label=Latest%20Release)](https://packagist.org/packages/softcreatr/php-perplexity-ai-sdk) [![ISC licensed](https://img.shields.io/badge/license-ISC-blue.svg)](./LICENSE.md) [![Plant Tree](https://img.shields.io/badge/dynamic/json?color=brightgreen&label=Plant%20Tree&query=%24.total&url=https%3A%2F%2Fpublic.offset.earth%2Fusers%2Fsoftcreatr%2Ftrees)](https://ecologi.com/softcreatr?r=61212ab3fc69b8eb8a2014f4) [![Codecov branch](https://img.shields.io/codecov/c/github/SoftCreatR/php-perplexity-ai-sdk)](https://codecov.io/gh/SoftCreatR/php-perplexity-ai-sdk) [![Code Climate maintainability](https://img.shields.io/codeclimate/maintainability-percentage/SoftCreatR/php-perplexity-ai-sdk)](https://codeclimate.com/github/SoftCreatR/php-perplexity-ai-sdk)

This PHP library provides a simple wrapper for the PerplexityAI API, allowing you to easily integrate the PerplexityAI API into your PHP projects.


## Features

-   Easy integration with PerplexityAI API
-   Supports all PerplexityAI API endpoints
-   Utilizes PSR-17 and PSR-18 compliant HTTP clients, and factories for making API requests

## Requirements

-   PHP 7.4 or higher
-   A PSR-17 HTTP Factory implementation (e.g., [guzzle/psr7](https://github.com/guzzle/psr7) or [nyholm/psr7](https://github.com/Nyholm/psr7))
-   A PSR-18 HTTP Client implementation (e.g., [guzzlehttp/guzzle](https://github.com/guzzle/guzzle) or [symfony/http-client](https://github.com/symfony/http-client))

## Installation

You can install the library via [Composer](https://getcomposer.org/):

```bash
composer require softcreatr/php-perplexity-ai-sdk
```

## Usage

First, include the library in your project:

```php
<?php

require_once 'vendor/autoload.php';
```

Then, create an instance of the `PerplexityAI` class with your API key, organization (optional), an HTTP client, an HTTP request factory, and an HTTP stream factory:

```php
use SoftCreatR\PerplexityAI\PerplexityAI;

$apiKey = 'your_api_key';

// Replace these lines with your chosen PSR-17 and PSR-18 compatible HTTP client and factories
$httpClient = new YourChosenHttpClient();
$requestFactory = new YourChosenRequestFactory();
$streamFactory = new YourChosenStreamFactory();
$uriFactory = new YourChosenUriFactory();

$pplx = new PerplexityAI($requestFactory, $streamFactory, $uriFactory, $httpClient, $apiKey);
```

Now you can call any supported PerplexityAI API endpoint using the magic method `__call`:

```php
$response = $pplx->createChatCompletion([
    'model' => 'mistral-7b-instruct',
    'messages' => [
        [
            'role' => 'system',
            'content' => 'Be precise and concise.'
        ],
        [
            'role' => 'user',
            'content' => 'How many stars are there in our galaxy?'
        ]
    ],
]);

// Process the API response
if ($response->getStatusCode() === 200) {
    $responseObj = json_decode($response->getBody()->getContents(), true);
    
    print_r($responseObj);
} else {
    echo "Error: " . $response->getStatusCode();
}
```

For more details on how to use each endpoint, refer to the [PerplexityAI API documentation](https://docs.perplexity.ai/reference), and the [examples](https://github.com/SoftCreatR/php-perplexity-ai-sdk/tree/main/examples) provided in the repository.

## Supported Methods

### Chat Completions
-   [Create Chat Completion](https://docs.perplexity.ai/reference/post_chat_completions) - [Example](https://github.com/SoftCreatR/php-perplexity-ai-sdk/blob/main/examples/chat/createChatCompletion.php)
    -   `createChatCompletion(array $options = [])`

## Changelog

For a detailed list of changes and updates, please refer to the [CHANGELOG.md](https://github.com/SoftCreatR/php-perplexity-ai-sdk/blob/main/CHANGELOG.md) file. We adhere to [Semantic Versioning](https://semver.org/spec/v2.0.0.html) and document notable changes for each release.

## Known Problems and limitations

### Streaming Support
Currently, streaming is not supported. It's planned to address this limitation asap. For now, please be aware that these methods cannot be used for streaming purposes.

If you require streaming functionality, consider using an alternative implementation or keep an eye out for future updates to this library.

## License

This library is licensed under the ISC License. See the [LICENSE](https://github.com/SoftCreatR/php-perplexity-ai-sdk/blob/main/LICENSE.md) file for more information.

## Maintainers 🛠️

<table>
<tr>
    <td style="text-align:center;word-wrap:break-word;width:150px;height: 150px">
        <a href=https://github.com/SoftCreatR>
            <img src=https://avatars.githubusercontent.com/u/81188?v=4 width="100;" alt="Sascha Greuel"/>
            <br />
            <sub style="font-size:14px"><b>Sascha Greuel</b></sub>
        </a>
    </td>
</tr>
</table>

## Contributors ✨

<table>
<tr>
</tr>
</table>
