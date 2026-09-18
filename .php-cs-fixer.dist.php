<?php

/**
 * PHP-CS-Fixer configuration.
 *
 * Run a check:  composer cs
 * Apply fixes:  composer cs-fix
 */

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('resources/vendor')
    ->exclude('resources')
    ->name('*.php')
    ->notName('*_compiled.php');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(false)
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'trailing_comma_in_multiline' => true,
        'single_quote' => true,
    ])
    ->setFinder($finder);
