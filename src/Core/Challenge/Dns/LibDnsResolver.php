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

use AcmePhp\Cli\Exception\AcmeDnsResolutionException;
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
        QuestionFactory $questionFactory = null,
        MessageFactory $messageFactory = null,
        Encoder $encoder = null,
        Decoder $decoder = null,
        $nameServer = '8.8.8.8'
    ) {
        $this->questionFactory = null === $questionFactory ? new QuestionFactory() : $questionFactory;
        $this->messageFactory = null === $messageFactory ? new MessageFactory() : $messageFactory;
        $this->encoder = null === $encoder ? (new EncoderFactory())->create() : $encoder;
        $this->decoder = null === $decoder ? (new DecoderFactory())->create() : $decoder;
        $this->nameServer = $nameServer;

        $this->logger = new NullLogger();
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
        $nsDomain = implode('.', array_slice(explode('.', rtrim($domain, '.')), -2));
        try {
            $nameServers = $this->request(
                $nsDomain,
                ResourceTypes::NS,
                $this->nameServer
            );
        } catch (\Exception $e) {
            throw new AcmeDnsResolutionException(sprintf('Unable to find domain %s on nameserver %s', $domain, $this->nameServer), $e);
        }

        $this->logger->debug('Fetched NS in charge of domain', ['nsDomain' => $nsDomain, 'servers' => $nameServers]);
        if (empty($nameServers)) {
            throw new AcmeDnsResolutionException(sprintf('Unable to find domain %s on nameserver %s', $domain, $this->nameServer));
        }

        $identicalEntries = [];
        foreach ($nameServers as $nameServer) {
            $ip = gethostbyname($nameServer);
            $serverEntries = $this->request($domain, ResourceTypes::TXT, $ip);
            $identicalEntries[json_encode($serverEntries)][] = $nameServer;
        }

        $this->logger->info('DNS records fetched', ['mapping' => $identicalEntries]);
        if (1 !== count($identicalEntries)) {
            throw new AcmeDnsResolutionException('Dns not fully propagated');
        }

        return json_decode(key($identicalEntries));
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

        if (0 !== $response->getResponseCode()) {
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
