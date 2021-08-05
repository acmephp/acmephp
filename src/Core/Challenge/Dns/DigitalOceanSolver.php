<?php

/*
 * This file is a driver for allowing DNS resolving through DigitalOceans API.
 * The API documentation can be found here: https://docs.digitalocean.com/reference/api/api-reference/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Challenge\Dns;

use AcmePhp\Core\Challenge\MultipleChallengesSolverInterface;
use AcmePhp\Core\Exception\Protocol\ChallengeFailedException;
use AcmePhp\Core\Protocol\AuthorizationChallenge;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Webmozart\Assert\Assert;
use \Psr\Http\Message\ResponseInterface;


class DigitalOceanSolver implements MultipleChallengesSolverInterface
{
    use LoggerAwareTrait;

    /**
     * @var DnsDataExtractor
     */
    private $extractor;


    /**
     * @var \GuzzleHttp\Client
     */
    private $m_httpClient;


    /**
     * The OAuth API key that one should use to authenticate requests.
     * @var string
     */
    private $m_apiKey;


    /*
     * Keeps track of all the domain records we created, in order to clean up easily.
     * @var array
     */
    private $m_cachedCreatedRecords;


    public function __construct(DnsDataExtractor $extractor = null, \GuzzleHttp\Client $httpClient, string $apiKey)
    {
        // @TODO - remove this hardcoding after figuring out how to get it injected.
        $apiKey = "NeedToFigureOutHowToGetThisPassedInCorrectly.";

        $this->extractor = $extractor ?: new DnsDataExtractor();

        $this->m_apiKey = $apiKey;
        $this->m_cachedCreatedRecords = array();
        $this->m_httpClient = $httpClient;
        $this->logger = new NullLogger();
    }


    /**
     * {@inheritdoc}
     */
    public function supports(AuthorizationChallenge $authorizationChallenge): bool
    {
        return 'dns-01' === $authorizationChallenge->getType();
    }


    /**
     * {@inheritdoc}
     */
    public function solve(AuthorizationChallenge $authorizationChallenge)
    {
        return $this->solveAll([$authorizationChallenge]);
    }


    /**
     * {@inheritdoc}
     */
    public function solveAll(array $authorizationChallenges)
    {
        Assert::allIsInstanceOf($authorizationChallenges, AuthorizationChallenge::class);

        foreach ($authorizationChallenges as $authorizationChallenge)
        {
            /* @var $authorizationChallenge AuthorizationChallenge */
            $recordFqdn = "_acme-challenge." . $authorizationChallenge->getDomain();
            $recordValue = $this->extractor->getRecordValue($authorizationChallenge);;
            $response = $this->addTxtRecord($recordFqdn, $recordValue);
        }
    }


    /**
     * {@inheritdoc}
     */
    public function cleanup(AuthorizationChallenge $authorizationChallenge)
    {
        return $this->cleanupAll([$authorizationChallenge]);
    }


    /**
     * {@inheritdoc}
     */
    public function cleanupAll(array $authorizationChallenges)
    {
        Assert::allIsInstanceOf($authorizationChallenges, AuthorizationChallenge::class);

        foreach ($authorizationChallenges as $challenge)
        {
            $fqdn = "_acme-challenge." . $challenge->getDomain();

            /* @var $challenge AuthorizationChallenge */
            if (!isset($this->m_cachedCreatedRecords[$fqdn]))
            {
                throw new \Exception("We did not set a record for {$fqdn}");
            }

            $recordId = $this->m_cachedCreatedRecords[$fqdn];
            $domain = $this->getDomainFromFQDN($fqdn);
            $this->removeRecord($domain, $recordId);
        }
    }


    /**
     * Send a request to Digital Ocean to authenticate using the API key.
     */
    private function getExistingDomain(string $domain)
    {
        $domain = $this->getDomainFromFQDN($domain); // ensure no hostname.
        return $this->m_httpClient->request("GET", "/domains/{$domain}");
    }


    /**
     * Helper function that sends a request to the D.O. API, adding the necessary auth token.
     * @param string $method - the method. E.g. "GET", "POST", "DELETE".
     * @param string $endpoint - The API endpoint. E.g. "/domains"
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function sendRequest(string $method, string $endpoint, $options = array()) : ResponseInterface
    {
        if (isset($options['headers']))
        {
            $headersArray = $options['headers'];
            $headersArray['Authorization'] = "Bearer {$this->m_apiKey}";
        }
        else
        {
            $options['headers'] = array(
                'Authorization' => "Bearer {$this->m_apiKey}",
            );
        }

        $url = "https://api.digitalocean.com/v2{$endpoint}";
        return $this->m_httpClient->request($method, $url, $options);
    }


    /**
     * Remove a record by ID.
     * @param string $domain
     * @param int $recordId
     * @return type
     */
    private function removeRecord(string $domain, int $recordId)
    {
        $response = $this->sendRequest("DELETE", "/domains/{$domain}/records/{$recordId}");

        die("delete response: " . print_r($response->getBody()));

        // check that the request was successful
        if ($response->getStatusCode() !== 200)
        {
            throw new \Exception("Request to delete record failed.");
        }
    }


    /**
     * Add a TXT record using Route53
     * @param string $name - the TXT record FQDN. E.g. "test.mydomin.org"
     * @param string $value - the value for the TXT record.
     * @return void - throw exception if anything goes wrong.
     */
    private function addTxtRecord(string $name, string $value)
    {
        $domain = $this->getDomainFromFQDN($name); // ensure no hostname.

        $options = [
            'json' => [
                'type' => "TXT",
                'name' => $this->getSubdomainForFQDN($name),
                'data' => $value,
                'ttl' => 60
            ]
        ];

        $response = $this->sendRequest("POST", "/domains/{$domain}/records", $options);
        $responseBody = $response->getBody();
        $responseObject = json_decode($responseBody, true);

        if ($responseObject === null)
        {
            throw new \Exception("Recieved non-JSON response back from D.O. API.");
        }

        if (isset($responseObject['domain_record']))
        {
            // successful response
            $record = $responseObject['domain_record'];
            $id = $record['id'];
            $this->m_cachedCreatedRecords[$name] = $id;
        }
        else
        {
            // failed repsonse, probably a bad auth token.
            throw new \Exception("Request failed, please check that your API auth token is valid.");
        }
    }


    /**
     * Fetches the DOMAIN part of a fully qualified domain name.
     * E.g. given: my.site.mydomain.com, this would return "mydomain.com"
     * @param string $FQDN - the fully qualified domain name.
     * @return string - the subdomain part of the FQDN.
     */
    private function getDomainFromFQDN($FQDN) : string
    {
        $parts = explode(".", $FQDN);
        $numParts = count($parts);
        $domain = $parts[$numParts - 2] . '.' . $parts[$numParts - 1];
        return $domain;
    }


    /**
     * Fetches the subdomain part of a fully qualified domain name.
     * E.g. given: my.site.mydomain.com, this would return "my.site"
     * @param string $FQDN - the fully qualified domain name.
     * @return string - the subdomain part of the FQDN.
     */
    private function getSubdomainForFQDN(string $FQDN) : string
    {
        $parts = explode(".", $FQDN);

        // remove the last two elements which are the domain.
        array_pop($parts);
        array_pop($parts);

        $subdomain = implode(".", $parts);
        return $subdomain;
    }
}
