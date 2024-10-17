<?php

/*
 * Copyright (c) 2024, Sascha Greuel and Contributors
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

require_once __DIR__ . '/../PerplexityAIFactory.php';

// Call the createChatCompletion method with specific options for streaming support.
PerplexityAIFactory::request(
    'createChatCompletion',
    [],
    [
        'model' => 'llama-3.1-sonar-small-128k-online',
        'messages' => [
            [
                'role' => 'user',
                'content' => 'Tell me a story about a brave knight.',
            ],
        ],
        'stream' => true,
    ],
    static function ($data) {
        if (isset($data['choices'][0]['delta']['content'])) {
            echo $data['choices'][0]['delta']['content'];
        }
    }
);
