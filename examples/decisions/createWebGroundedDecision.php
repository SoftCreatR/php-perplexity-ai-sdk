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

// POST /v1/decisions
PerplexityAIFactory::request('createDecision', [], [
    'model' => 'pplx-decider-flash-latest',
    'mode' => 'web_grounding',
    'state' => 'https://www.php-fig.org/psr/psr-18/',
    'max_staleness' => 86400,
    'questions' => [
        'http_client' => ['type' => 'noul', 'instructions' => 'Does PSR-18 define an HTTP client interface?'],
    ],
]);
