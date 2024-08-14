<?php

$header = <<<'EOF'
This file is part of the Acme PHP project.

(c) Titouan Galopin <galopintitouan@gmail.com>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF;

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__.'/bin')
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/tests')
;

return (new PhpCsFixer\Config())
    ->setFinder($finder)
    ->setRules([
        '@PHP81Migration' => true,
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
        'phpdoc_annotation_without_dot' => false,
        'header_comment' => ['header' => $header],
    ])
;
