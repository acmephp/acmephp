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
    const SIMPLE_FORMAT = "%start_tag%%message% %context% %extra%%end_tag%\n";

    /**
     * {@inheritdoc}
     */
    public function __construct($format = null, $dateFormat = null, $allowInlineLineBreaks = false, $ignoreEmptyContextAndExtra = true)
    {
        parent::__construct($format, $dateFormat, $allowInlineLineBreaks, $ignoreEmptyContextAndExtra);
    }

    /**
     * {@inheritdoc}
     */
    public function format(array $record): string
    {
        if ($record['level'] >= Logger::ERROR) {
            $record['start_tag'] = '<error>';
            $record['end_tag'] = '</error>';
        } elseif ($record['level'] >= Logger::WARNING) {
            $record['start_tag'] = '<comment>';
            $record['end_tag'] = '</comment>';
        } elseif ($record['level'] >= Logger::NOTICE) {
            $record['start_tag'] = '<info>';
            $record['end_tag'] = '</info>';
        } else {
            $record['start_tag'] = '';
            $record['end_tag'] = '';
        }

        return parent::format($record);
    }
}
