<?php
declare(strict_types=1);

use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\ListNotation\ListSyntaxFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withRootFiles()
    ->withPaths([__DIR__ . '/src', __DIR__ . '/tests'])
    ->withConfiguredRule(ArraySyntaxFixer::class, ['syntax' => 'short'])
    ->withPreparedSets(psr12: true, spaces: true, strict: true)
    ->withPhpCsFixerSets(perCS20: true)
    ->withRules([ListSyntaxFixer::class])
;
