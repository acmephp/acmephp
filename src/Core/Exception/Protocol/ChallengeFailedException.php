<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Exception\Protocol;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class ChallengeFailedException extends ProtocolException
{
    private $response;

    public function __construct($response, ?\Exception $previous = null)
    {
        parent::__construct(
            sprintf('Challenge failed (response: %s).', json_encode($response)),
            $previous
        );

        $this->response = $response;
    }

    /**
     * @return array
     */
    public function getResponse()
    {
        return $this->response;
    }
}
