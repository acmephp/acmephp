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
use Monolog\Logger;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Writes logs to the console output depending on its verbosity setting.
 *
 * Extracted from Symfony Monolog bridge.
 *
 * @see https://github.com/symfony/monolog-bridge/edit/master/Handler/ConsoleHandler.php
 *
 * @author Tobias Schultze <http://tobion.de>
 */
class ConsoleHandler extends AbstractProcessingHandler
{
    /**
     * @var OutputInterface|null
     */
    private $output;

    /**
     * @var array
     */
    private $verbosityLevelMap = [
        OutputInterface::VERBOSITY_QUIET => Logger::ERROR,
        OutputInterface::VERBOSITY_NORMAL => Logger::INFO,
        OutputInterface::VERBOSITY_VERBOSE => Logger::INFO,
        OutputInterface::VERBOSITY_VERY_VERBOSE => Logger::INFO,
        OutputInterface::VERBOSITY_DEBUG => Logger::DEBUG,
    ];

    /**
     * Constructor.
     *
     * @param OutputInterface|null $output            The console output to use (the handler remains disabled when passing null
     *                                                until the output is set, e.g. by using console events)
     * @param bool                 $bubble            Whether the messages that are handled can bubble up the stack
     * @param array                $verbosityLevelMap Array that maps the OutputInterface verbosity to a minimum logging
     *                                                level (leave empty to use the default mapping)
     */
    public function __construct(?OutputInterface $output = null, bool $bubble = true, array $verbosityLevelMap = [])
    {
        parent::__construct(Logger::DEBUG, $bubble);

        $this->output = $output;

        if ($verbosityLevelMap) {
            $this->verbosityLevelMap = $verbosityLevelMap;
        }
    }

    public function isHandling(array $record): bool
    {
        return $this->updateLevel() && parent::isHandling($record);
    }

    public function handle(array $record): bool
    {
        // we have to update the logging level each time because the verbosity of the
        // console output might have changed in the meantime (it is not immutable)
        return $this->updateLevel() && parent::handle($record);
    }

    /**
     * Sets the console output to use for printing logs.
     *
     * @param OutputInterface $output The console output to use
     */
    public function setOutput(OutputInterface $output)
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

    protected function write(array $record): void
    {
        $this->output->write((string) $record['formatted']);
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
    private function updateLevel()
    {
        if (null === $this->output) {
            return false;
        }

        $verbosity = $this->output->getVerbosity();
        if (isset($this->verbosityLevelMap[$verbosity])) {
            $this->setLevel($this->verbosityLevelMap[$verbosity]);
        } else {
            $this->setLevel(Logger::DEBUG);
        }

        return true;
    }
}
