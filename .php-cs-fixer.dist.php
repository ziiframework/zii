<?php

declare(strict_types=1);

return (new PhpCsFixer\Config())->setRules([
    '@PSR12' => true,
    '@PHP74Migration' => true,
    '@Symfony' => true,
    '@Symfony:risky' => true,
    'heredoc_indentation' => false,
    'method_argument_space' => [
        'on_multiline' => 'ensure_single_line',
    ],
])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->files()
            ->name('*.php')
            ->in(__DIR__ . '/tests')
            ->exclude('framework/di/stubs')
    );
