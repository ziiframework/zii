<?php

declare(strict_types=1);

$header_comment_block = <<<'EOF'
@link https://www.yiiframework.com/
@copyright Copyright (c) 2008 Yii Software LLC
@license https://www.yiiframework.com/license/
EOF;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PHP74Migration' => true,
        '@PHP74Migration:risky' => true,
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'yoda_style' => false,
        'declare_strict_types' => true,
        'is_null' => false,
        'void_return' => true,
        'single_quote' => ['strings_containing_single_quote_chars' => false],
        'concat_space' => ['spacing' => 'one'],
        'header_comment' => ['header' => $header_comment_block, 'comment_type' => 'PHPDoc'], // TODO change to: comment_type => comment
        'heredoc_indentation' => false,
        'method_argument_space' => [
            'on_multiline' => 'ensure_single_line',
        ],
        'phpdoc_annotation_without_dot' => false,
        'single_line_comment_style' => false,
//        'phpdoc_no_alias_tag' => [
//            'replacements' => [
//                'property-read' => 'property',
//                'property-write' => 'property',
//                'type' => 'var',
//                'link' => 'see',
//            ],
//        ],
        'phpdoc_no_alias_tag' => false,
        'phpdoc_align' => false,
        'align_multiline_comment' => false,
        'fully_qualified_strict_types' => true,
        'static_lambda' => true,
        'lambda_not_used_import' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],
        'no_useless_concat_operator' => false,
        'no_leading_import_slash' => true,
        'no_unused_imports' => true,
        'single_import_per_statement' => true,
        'ordered_imports' => [
            'sort_algorithm' => 'length',
            'imports_order' => ['const', 'class', 'function']
        ],
        'phpdoc_order' => [
            'order' => ['param', 'return', 'throws'],
        ],
        'single_line_after_imports' => true,
        'strict_comparison' => false, // TODO remove
        'echo_tag_syntax' => ['format' => 'short'],
        'no_unneeded_curly_braces' => true,
        'blank_line_after_namespace' => true,
        'blank_line_before_statement' => [
            'statements' => [
                'case',
                // 'break',
                'continue',
                'declare',
                'default',
                'do',
                'exit',
                'for',
                'foreach',
                'goto',
                'if',
                'include',
                'include_once',
                'phpdoc',
                'require',
                'require_once',
                'return',
                'switch',
                'throw',
                'try',
                'while',
                'yield',
            ],
        ],
        'php_unit_set_up_tear_down_visibility' => false,
        'php_unit_test_case_static_method_calls' => [
            'call_type' => 'this',
        ],
        // 'php_unit_construct' => true,
        // 'php_unit_dedicate_assert' => true,
        // 'php_unit_dedicate_assert_internal_type' => true,
        'php_unit_no_expectation_annotation' => true,
        'php_unit_expectation' => true,
        'php_unit_mock' => true,
        'php_unit_mock_short_will_return' => true,
        // 'php_unit_namespaced' => true,
        'native_constant_invocation' => false,
        'native_function_casing' => true,
        'native_function_invocation' => false,
        'native_function_type_declaration_casing' => true,
        'fopen_flags' => ['b_mode' => true],
        'get_class_to_class_keyword' => false, // TODO: as of php 8.0
        'no_trailing_comma_in_singleline_function_call' => true,
        'class_reference_name_casing' => true,
        'no_unneeded_import_alias' => false,
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->files()
            ->name('*.php')
            ->notName('Exception.php')
            ->notName('*Exception.php')
            ->in(__DIR__)
            ->exclude('vendor')
            ->exclude('src/views')
            ->exclude('tests/framework/di/stubs')
            ->exclude('tests/data/console/migrate_create')
            ->exclude('tests/data/views')
            ->notName('add_columns_fk.php')
            ->notName('typed_error.php')
            ->notName('DetailViewTest.php')
            ->notName('VarDumperTest.php')
            ->notName('MaskedInput.php')
            ->notName('ModelController.php')
    );
