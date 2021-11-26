<?php

declare(strict_types=1);

return (new PhpCsFixer\Config())->setRules([
    '@PSR12' => true,
    '@PHP74Migration' => true,
    'heredoc_indentation' => false,
    'method_argument_space' => ['on_multiline' => 'ensure_single_line'],
])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__ . '/tests/framework')
    );
