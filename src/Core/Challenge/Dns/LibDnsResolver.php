<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Challenge\Dns;

use AcmePhp\Core\Exception\AcmeDnsResolutionException;
use LibDNS\Decoder\Decoder;
use LibDNS\Decoder\DecoderFactory;
use LibDNS\Encoder\Encoder;
use LibDNS\Encoder\EncoderFactory;
use LibDNS\Messages\MessageFactory;
use LibDNS\Messages\MessageTypes;
use LibDNS\Records\QuestionFactory;
use LibDNS\Records\ResourceTypes;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Resolves DNS with LibDNS (pass over internal DNS cache and check several nameservers).
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class LibDnsResolver implements DnsResolverInterface
{
    use LoggerAwareTrait;

    /**
     * @var QuestionFactory
     */
    private $questionFactory;

    /**
     * @var MessageFactory
     */
    private $messageFactory;

    /**
     * @var Encoder
     */
    private $encoder;

    /**
     * @var Decoder
     */
    private $decoder;

    /**
     * @var string
     */
    private $nameServer;

    public function __construct(
        ?QuestionFactory $questionFactory = null,
        ?MessageFactory $messageFactory = null,
        ?Encoder $encoder = null,
        ?Decoder $decoder = null,
        $nameServer = '8.8.8.8'
    ) {
        $this->questionFactory = $questionFactory ?: new QuestionFactory();
        $this->messageFactory = $messageFactory ?: new MessageFactory();
        $this->encoder = $encoder ?: (new EncoderFactory())->create();
        $this->decoder = $decoder ?: (new DecoderFactory())->create();
        $this->nameServer = $nameServer;
        $this->logger = new NullLogger();
    }

    /**
     * @{@inheritdoc}
     */
    public static function isSupported(): bool
    {
        return class_exists(ResourceTypes::class);
    }

    /**
     * @{@inheritdoc}
     */
    public function getTxtEntries($domain): array
    {
        $domain = rtrim($domain, '.');
        $nameServers = $this->getNameServers($domain);
        $this->logger->debug('Fetched TXT records for domain', array('nsDomain' => $domain, 'servers' => $nameServers));
        $identicalEntries = array();
        foreach ($nameServers as $nameServer) {
            $ipNameServer = gethostbynamel($nameServer);
            if (empty($ipNameServer)) {
                throw new AcmeDnsResolutionException(sprintf('Unable to find domain %s on nameserver %s', $domain, $nameServer));
            }
            try {
                $response = $this->request($domain, ResourceTypes::TXT, $ipNameServer[0]);
            } catch (\Exception $e) {
                throw new AcmeDnsResolutionException(sprintf('Unable to find domain %s on nameserver %s', $domain, $nameServer), $e);
            }
            $entries = array();
            foreach ($response->getAnswerRecords() as $record) {
                foreach ($record->getData() as $recordData) {
                    $entries[] = (string) $recordData;
                }
            }

            $identicalEntries[json_encode($entries)][] = $nameServer;
        }

        $this->logger->info('DNS records fetched', array('mapping' => $identicalEntries));
        if (1 !== \count($identicalEntries)) {
            throw new AcmeDnsResolutionException('Dns not fully propagated');
        }

        return json_decode(key($identicalEntries));
    }

    private function getNameServers($domain)
    {
        if ('' === $domain) {
            return array($this->nameServer);
        }

        $parentNameServers = $this->getNameServers(implode('.', \array_slice(explode('.', $domain), 1)));
        $itemNameServers = array();
        $this->logger->debug('Fetched NS in charge of domain', array('nsDomain' => $domain, 'servers' => $parentNameServers));
        foreach ($parentNameServers as $parentNameServer) {
            $ipNameServer = gethostbynamel($parentNameServer);
            if (empty($ipNameServer)) {
                continue;
            }
            try {
                $response = $this->request(
                    $domain,
                    ResourceTypes::NS,
                    $ipNameServer[0]
                );
            } catch (\Exception $e) {
                // ignore errors
                continue;
            }

            foreach ($response->getAnswerRecords() as $record) {
                try {
                    $field = $record->getData()->getFieldByName('nsdname');
                    $itemNameServers[] = $field->getValue();
                } catch (\OutOfBoundsException $e) {
                }
            }
            foreach ($response->getAuthorityRecords() as $record) {
                try {
                    $field = $record->getData()->getFieldByName('nsdname');
                    $itemNameServers[] = $field->getValue();
                } catch (\OutOfBoundsException $e) {
                }
            }
        }
        $itemNameServers = array_unique($itemNameServers);
        if (empty($itemNameServers)) {
            return $parentNameServers;
        }

        return $itemNameServers;
    }

    private function request($domain, $type, $nameServer)
    {
        $question = $this->questionFactory->create($type);
        $question->setName($domain);

        // Create request message
        $request = $this->messageFactory->create(MessageTypes::QUERY);
        $request->getQuestionRecords()->add($question);
        $request->isRecursionDesired(true);

        // Send request
        $socket = stream_socket_client(sprintf('udp://' . $nameServer . ':53'));
        stream_socket_sendto($socket, $this->encoder->encode($request));

        $r = array($socket);
        $w = $e = array();
        if (!stream_select($r, $w, $e, 3)) {
            throw new AcmeDnsResolutionException(sprintf('Timeout reached when requesting ServerName %s', $nameServer));
        }

        // Decode response message
        try {
            $response = $this->decoder->decode(fread($socket, 1 << 20));
        } catch (\Exception $e) {
            throw new AcmeDnsResolutionException('Failed to decode server response', $e);
        }
        if (0 !== $response->getResponseCode()) {
            throw new AcmeDnsResolutionException(sprintf('ServerName respond with error code "%d"', $response->getResponseCode()));
        }

        return $response;
    }
}
