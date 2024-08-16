<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Cli\Exception;

/**
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class CommandFlowException extends AcmeCliException
{
    private $missing;
    private $command;
    private $arguments;

    /**
     * @param string $missing   Missing requirement to fix the flow
     * @param string $command   Name of the command to run in order to fix the flow
     * @param array  $arguments Optional list of missing arguments
     */
    public function __construct(string $missing, string $command, array $arguments = array(), ?\Exception $previous = null)
    {
        $this->missing = $missing;
        $this->command = $command;
        $this->arguments = $arguments;

        $message = trim(sprintf(
            'You have to %s first. Run the command%sphp %s %s %s',
            $missing,
            PHP_EOL . PHP_EOL,
            $_SERVER['PHP_SELF'],
            $command,
            implode(' ', $arguments),
        ));

        parent::__construct($message, $previous);
    }

    public function getMissing(): string
    {
        return $this->missing;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }
}
