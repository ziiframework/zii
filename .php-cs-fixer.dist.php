<?php

declare(strict_types=1);

return (new PhpCsFixer\Config())->setRules([
    '@PSR12' => true,
    '@PHP74Migration' => true,
])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__ . '/src')
    );
