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

use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Writes logs to the console output depending on its verbosity setting.
 *
 * Extracted from Symfony Monolog bridge.
 *
 * @see https://github.com/symfony/monolog-bridge/blob/7.1/Handler/ConsoleHandler.php
 *
 * @author Tobias Schultze <http://tobion.de>
 */
class ConsoleHandler extends AbstractProcessingHandler
{
    /**
     * @var array<int, Level>
     */
    private array $verbosityLevelMap = [
        OutputInterface::VERBOSITY_QUIET => Level::Error,
        OutputInterface::VERBOSITY_NORMAL => Level::Info,
        OutputInterface::VERBOSITY_VERBOSE => Level::Info,
        OutputInterface::VERBOSITY_VERY_VERBOSE => Level::Info,
        OutputInterface::VERBOSITY_DEBUG => Level::Info,
    ];

    /**
     * @param OutputInterface|null $output            The console output to use (the handler remains disabled when passing null
     *                                                until the output is set, e.g. by using console events)
     * @param bool                 $bubble            Whether the messages that are handled can bubble up the stack
     * @param array                $verbosityLevelMap Array that maps the OutputInterface verbosity to a minimum logging
     *                                                level (leave empty to use the default mapping)
     */
    public function __construct(private ?OutputInterface $output = null, bool $bubble = true, array $verbosityLevelMap = [])
    {
        parent::__construct(Level::Debug, $bubble);

        if ([] !== $verbosityLevelMap) {
            $this->verbosityLevelMap = $verbosityLevelMap;
        }
    }

    public function isHandling(LogRecord $record): bool
    {
        return $this->updateLevel() && parent::isHandling($record);
    }

    public function handle(LogRecord $record): bool
    {
        // we have to update the logging level each time because the verbosity of the
        // console output might have changed in the meantime (it is not immutable)
        return $this->updateLevel() && parent::handle($record);
    }

    /**
     * Sets the console output to use for printing logs.
     */
    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    /**
     * Disables the output.
     */
    public function close(): void
    {
        $this->output = null;

        parent::close();
    }

    protected function write(LogRecord $record): void
    {
        $this->output->write((string) $record->formatted);
    }

    protected function getDefaultFormatter(): FormatterInterface
    {
        $formatter = new ConsoleFormatter();
        $formatter->allowInlineLineBreaks();

        return $formatter;
    }

    /**
     * Updates the logging level based on the verbosity setting of the console output.
     *
     * @return bool Whether the handler is enabled and verbosity is not set to quiet
     */
    private function updateLevel(): bool
    {
        if (null === $this->output) {
            return false;
        }

        $verbosity = $this->output->getVerbosity();
        if (isset($this->verbosityLevelMap[$verbosity])) {
            $this->setLevel($this->verbosityLevelMap[$verbosity]);
        } else {
            $this->setLevel(Level::Debug);
        }

        return true;
    }
}
