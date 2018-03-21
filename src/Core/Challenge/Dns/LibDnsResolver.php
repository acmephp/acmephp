<?php

/*
 * This file is part of the ACME PHP library.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Challenge\Dns;

use AcmePhp\Cli\Exception\AcmeDnsResolutionException;
use LibDNS\Decoder\Decoder;
use LibDNS\Decoder\DecoderFactory;
use LibDNS\Encoder\Encoder;
use LibDNS\Encoder\EncoderFactory;
use LibDNS\Messages\MessageFactory;
use LibDNS\Messages\MessageTypes;
use LibDNS\Records\QuestionFactory;
use LibDNS\Records\ResourceTypes;

/**
 * Resolves DNS with LibDNS (pass over internal DNS cache and check several nameservers).
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class LibDnsResolver implements DnsResolverInterface
{
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
        QuestionFactory $questionFactory = null,
        MessageFactory $messageFactory = null,
        Encoder $encoder = null,
        Decoder $decoder = null,
        $nameServer = '8.8.8.8'
    ) {
        $this->questionFactory = $questionFactory === null ? new QuestionFactory() : $questionFactory;
        $this->messageFactory = $messageFactory === null ? new MessageFactory() : $messageFactory;
        $this->encoder = $encoder === null ? (new EncoderFactory())->create() : $encoder;
        $this->decoder = $decoder === null ? (new DecoderFactory())->create() : $decoder;
        $this->nameServer = $nameServer;
    }

    /**
     * @{@inheritdoc}
     */
    public static function isSupported()
    {
        return class_exists(ResourceTypes::class);
    }

    /**
     * @{@inheritdoc}
     */
    public function getTxtEntries($domain)
    {
        $nameServers = $this->request(
            implode('.', array_slice(explode('.', rtrim($domain, '.')), -2)),
            ResourceTypes::NS,
            $this->nameServer
        );
        if (empty($nameServers)) {
            throw new AcmeDnsResolutionException(
                sprintf('Unable to find domain %s on nameserver %s', $domain, $this->nameServer)
            );
        }
        $entries = null;
        foreach ($nameServers as $nameServer) {
            $ip = gethostbyname($nameServer);
            $serverEntries = $this->request($domain, ResourceTypes::TXT, $ip);
            if (null === $entries) {
                $entries = $serverEntries;
            } elseif ($entries != $serverEntries) {
                throw new AcmeDnsResolutionException(
                    sprintf('Dns not fully propagated into nameserver %s', $nameServer)
                );
            }
        }

        return $entries;
    }

    private function request($domain, $type, $nameServer)
    {
        $question = $this->questionFactory->create($type);
        $question->setName(rtrim($domain, '.'));

        // Create request message
        $request = $this->messageFactory->create(MessageTypes::QUERY);
        $request->getQuestionRecords()->add($question);
        $request->isRecursionDesired(true);

        // Send request
        $socket = stream_socket_client(sprintf('udp://'.$nameServer.':53'));
        stream_socket_sendto($socket, $this->encoder->encode($request));

        $r = [$socket];
        $w = $e = [];
        if (!stream_select($r, $w, $e, 3)) {
            throw new AcmeDnsResolutionException(sprintf('Timeout reached when requesting ServerName %s', $nameServer));
        }

        // Decode response message
        $response = $this->decoder->decode(fread($socket, 512));

        if ($response->getResponseCode() !== 0) {
            throw new AcmeDnsResolutionException(
                sprintf('ServerName respond with error code "%d"', $response->getResponseCode())
            );
        }

        $entries = [];
        foreach ($response->getAnswerRecords() as $record) {
            foreach ($record->getData() as $field) {
                $entries[] = (string) $field;
            }
        }

        sort($entries);

        return array_unique($entries);
    }
}
