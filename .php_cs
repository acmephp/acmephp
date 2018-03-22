<?php

$header = <<<'EOF'
This file is part of the Acme PHP project.

(c) Titouan Galopin <galopintitouan@gmail.com>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF;

$config = PhpCsFixer\Config::create()
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => true,
        'header_comment' => ['header' => $header],
        'linebreak_after_opening_tag' => true,
        'modernize_types_casting' => true,
        'no_superfluous_elseif' => true,
        'no_useless_else' => true,
        'phpdoc_order' => true,
        'psr4' => true,
        'simplified_null_return' => true,
        'no_useless_return' => true,
        'strict_comparison' => true,
        'yoda_style' => true,
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__.'/')
            ->exclude('vendor')
            ->name('*.php')
    )
;

return $config;
