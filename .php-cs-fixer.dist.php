<?php

declare(strict_types=1);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        '@PSR12:risky' => true,
        '@PHP74Migration' => true,
        '@PHP74Migration:risky' => true,
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,
        'yoda_style' => false,
        'concat_space' => ['spacing' => 'one'],
        'heredoc_indentation' => false,
        'method_argument_space' => [
            'on_multiline' => 'ensure_single_line',
        ],
        'phpdoc_annotation_without_dot' => true,
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->files()
            ->name('*.php')
            ->in(__DIR__ . '/tests')
            ->exclude('framework/di/stubs')
    );
