<?php

/*
 * [License Information]
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use SoftCreatR\PerplexityAI\PerplexityAI;
use SoftCreatR\PerplexityAI\PerplexityAIURLBuilder;

/**
 * Example factory class for creating and using the PerplexityAI client.
 */
final class PerplexityAIFactory
{
    /**
     * PerplexityAI API Key.
     *
     * @see https://docs.perplexity.ai/guides/getting-started
     * @var string
     */
    private const PERPLEXITYAI_API_KEY = 'your_api_key';

    /**
     * Prevents instantiation of this class.
     */
    private function __construct()
    {
        // This class should not be instantiated.
    }

    /**
     * Creates an instance of the PerplexityAI client.
     *
     * @param string $apiKey The PerplexityAI API key.
     *
     * @return PerplexityAI The PerplexityAI client instance.
     */
    public static function create(
        #[SensitiveParameter]
        string $apiKey = self::PERPLEXITYAI_API_KEY
    ): PerplexityAI {
        $psr17Factory = new HttpFactory();
        $httpClient = new Client(['stream' => true]);

        return new PerplexityAI(
            requestFactory: $psr17Factory,
            streamFactory: $psr17Factory,
            uriFactory: $psr17Factory,
            httpClient: $httpClient,
            apiKey: $apiKey
        );
    }

    /**
     * Sends a request to the specified PerplexityAI API endpoint.
     *
     * @param string         $method         The name of the API method to call.
     * @param array          $parameters     An associative array of parameters (URL parameters).
     * @param array          $options        An associative array of options (body or query parameters).
     * @param callable|null  $streamCallback Optional callback function for streaming responses.
     * @param bool           $returnResponse Whether to return the response or not.
     *
     * @return mixed
     */
    public static function request(
        string $method,
        array $parameters = [],
        array $options = [],
        ?callable $streamCallback = null,
        bool $returnResponse = false,
    ): mixed {
        $perplexityAI = self::create();

        try {
            $endpoint = PerplexityAIURLBuilder::getEndpoint($method);
            $path = $endpoint['path'];

            // Determine if the path contains placeholders
            $hasPlaceholders = \preg_match('/\{(\w+)}/', $path) === 1;

            if ($hasPlaceholders) {
                $urlParameters = $parameters;
                $bodyOptions = $options;
            } else {
                $urlParameters = [];
                $bodyOptions = $parameters + $options; // Merge parameters and options
            }

            if ($streamCallback !== null) {
                $perplexityAI->{$method}($urlParameters, $bodyOptions, $streamCallback);
            } else {
                $response = $perplexityAI->{$method}($urlParameters, $bodyOptions);

                if ($returnResponse) {
                    return $response->getBody()->getContents();
                }

                $contentType = $response->getHeaderLine('Content-Type');

                if (\str_contains($contentType, 'application/json')) {
                    $result = \json_decode(
                        $response->getBody()->getContents(),
                        true,
                        512,
                        \JSON_THROW_ON_ERROR
                    );

                    echo "============\n| Response |\n============\n\n";
                    echo \json_encode($result, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT);
                    echo "\n\n============\n";
                } else {
                    // Handle other content types if necessary
                    echo "Received response with Content-Type: {$contentType}\n";
                    echo $response->getBody()->getContents();
                }

                return null;
            }
        } catch (Exception $e) {
            echo "Error: {$e->getMessage()}\n";
        }

        return null;
    }
}
