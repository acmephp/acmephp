<?php
declare(strict_types=1);

use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\ListNotation\ListSyntaxFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths([__DIR__ . '/src', __DIR__ . '/tests'])
    ->withConfiguredRule(
        ArraySyntaxFixer::class,
        ['syntax' => 'long']
    )
    ->withRules([
        ListSyntaxFixer::class,
    ])
    ->withPreparedSets(psr12: true);
