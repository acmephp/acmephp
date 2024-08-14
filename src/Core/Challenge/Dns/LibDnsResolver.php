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

    private Encoder $encoder;
    private Decoder $decoder;

    /**
     * @param string $nameServer
     */
    public function __construct(
        private QuestionFactory $questionFactory = new QuestionFactory(),
        private MessageFactory $messageFactory = new MessageFactory(),
        ?Encoder $encoder = null,
        ?Decoder $decoder = null,
        private $nameServer = '8.8.8.8'
    ) {
        $this->encoder = $encoder ?? (new EncoderFactory())->create();
        $this->decoder = $decoder ?? (new DecoderFactory())->create();
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
        $domain = rtrim((string) $domain, '.');
        $nameServers = $this->getNameServers($domain);
        $this->logger->debug('Fetched TXT records for domain', ['nsDomain' => $domain, 'servers' => $nameServers]);
        $identicalEntries = [];
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
            $entries = [];
            foreach ($response->getAnswerRecords() as $record) {
                foreach ($record->getData() as $recordData) {
                    $entries[] = (string) $recordData;
                }
            }

            $identicalEntries[json_encode($entries)][] = $nameServer;
        }

        $this->logger->info('DNS records fetched', ['mapping' => $identicalEntries]);
        if (1 !== \count($identicalEntries)) {
            throw new AcmeDnsResolutionException('Dns not fully propagated');
        }

        return json_decode(key($identicalEntries));
    }

    private function getNameServers(string $domain)
    {
        if ('' === $domain) {
            return [$this->nameServer];
        }

        $parentNameServers = $this->getNameServers(implode('.', \array_slice(explode('.', (string) $domain), 1)));
        $itemNameServers = [];
        $this->logger->debug('Fetched NS in charge of domain', ['nsDomain' => $domain, 'servers' => $parentNameServers]);
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
            } catch (\Exception) {
                // ignore errors
                continue;
            }

            foreach ($response->getAnswerRecords() as $record) {
                try {
                    $field = $record->getData()->getFieldByName('nsdname');
                    $itemNameServers[] = $field->getValue();
                } catch (\OutOfBoundsException) {
                }
            }
            foreach ($response->getAuthorityRecords() as $record) {
                try {
                    $field = $record->getData()->getFieldByName('nsdname');
                    $itemNameServers[] = $field->getValue();
                } catch (\OutOfBoundsException) {
                }
            }
        }
        $itemNameServers = array_unique($itemNameServers);
        if (empty($itemNameServers)) {
            return $parentNameServers;
        }

        return $itemNameServers;
    }

    private function request(string $domain, $type, string $nameServer)
    {
        $question = $this->questionFactory->create($type);
        $question->setName($domain);

        // Create request message
        $request = $this->messageFactory->create(MessageTypes::QUERY);
        $request->getQuestionRecords()->add($question);
        $request->isRecursionDesired(true);

        // Send request
        $socket = stream_socket_client(sprintf('udp://'.$nameServer.':53'));
        stream_socket_sendto($socket, (string) $this->encoder->encode($request));

        $r = [$socket];
        $w = $e = [];
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
