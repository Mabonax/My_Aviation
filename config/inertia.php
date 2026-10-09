<?php

return [
    // Match the actual React directory, including case on Linux.
    'page_paths' => [resource_path('js/pages')],
    'page_extensions' => ['tsx', 'ts', 'jsx', 'js'],
    'testing' => [
        'ensure_pages_exist' => true,
        'page_paths' => [resource_path('js/pages')],
        'page_extensions' => ['tsx', 'ts', 'jsx', 'js'],
    ],
];
