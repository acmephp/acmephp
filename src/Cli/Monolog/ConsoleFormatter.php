<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Cli\Monolog;

use Monolog\Formatter\LineFormatter;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;

/**
 * Formats incoming records for console output by coloring them depending on log level.
 *
 * Extracted from Symfony Monolog bridge.
 *
 * @see https://github.com/symfony/monolog-bridge/edit/master/Formatter/ConsoleFormatter.php
 *
 * @author Tobias Schultze <http://tobion.de>
 */
class ConsoleFormatter extends LineFormatter
{
    public const string SIMPLE_FORMAT = "%extra.start_tag%%message% %context% %extra%%extra.end_tag%\n";

    public function __construct($format = null, $dateFormat = null, $allowInlineLineBreaks = false, $ignoreEmptyContextAndExtra = true)
    {
        parent::__construct($format, $dateFormat, $allowInlineLineBreaks, $ignoreEmptyContextAndExtra);
    }

    public function format(LogRecord $record): string
    {
        if (Level::Error->includes($record->level)) {
            $start = '<error>';
            $end = '</error>';
        } elseif (Level::Warning->includes($record->level)) {
            $start = '<comment>';
            $end = '</comment>';
        } elseif (Level::Notice->includes($record->level)) {
            $start = '<info>';
            $end = '</info>';
        } else {
            $start = '';
            $end = '';
        }

        $record->extra = [
            ...$record->extra,
            'start_tag' => $start,
            'end_tag' => $end,
        ];

        return parent::format($record);
    }
}
