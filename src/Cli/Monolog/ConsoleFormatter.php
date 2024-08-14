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
use Monolog\Logger;

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
    public const SIMPLE_FORMAT = "%extra.start_tag%%message% %context% %extra%%extra.end_tag%\n";

    public function __construct($format = null, $dateFormat = null, $allowInlineLineBreaks = false, $ignoreEmptyContextAndExtra = true)
    {
        parent::__construct($format, $dateFormat, $allowInlineLineBreaks, $ignoreEmptyContextAndExtra);
    }

    /**
     * @param LogRecord|array $record
     */
    public function format($record): string
    {
        if ($record['level'] >= Logger::ERROR) {
            $extra['start_tag'] = '<error>';
            $extra['end_tag'] = '</error>';
        } elseif ($record['level'] >= Logger::WARNING) {
            $extra['start_tag'] = '<comment>';
            $extra['end_tag'] = '</comment>';
        } elseif ($record['level'] >= Logger::NOTICE) {
            $extra['start_tag'] = '<info>';
            $extra['end_tag'] = '</info>';
        } else {
            $extra['start_tag'] = '';
            $extra['end_tag'] = '';
        }

        $record['extra'] = [
            ...$record['extra'],
            ...$extra,
        ];

        return dump(parent::format($record));
    }
}
