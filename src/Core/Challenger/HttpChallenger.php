<?php


namespace AcmePhp\Core\Challenger;


use AcmePhp\Core\Protocol\AuthorizationChallenge;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * ACME HTTP challenger with manual configuration.
 *
 * @author Jérémy Derussé <jeremy@derusse.com>
 */
class HttpChallenger implements ChallengerInterface
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output = null)
    {
        $this->output = null === $output ? new ConsoleOutput() : $output;
    }

    /**
     * @inheritdoc
     */
    public function supports($type)
    {
        return 'http-01' === $type;
    }

    protected function getWebPath(AuthorizationChallenge $authorizationChallenge)
    {
        return sprintf(
            'http://%s/.well-known/acme-challenge/%s',
            $authorizationChallenge->getDomain(),
            $authorizationChallenge->getToken()
        );
    }

    protected function getWebContent(AuthorizationChallenge $authorizationChallenge)
    {
        return $authorizationChallenge->getPayload();
    }

    /**
     * @inheritdoc
     */
    public function initialize(AuthorizationChallenge $authorizationChallenge)
    {
        $webPath = $this->getWebPath($authorizationChallenge);
        $webContent = $this->getWebContent($authorizationChallenge);
        $this->output->writeln(
            sprintf(
                <<<'EOF'
<info>The authorization token was successfully fetched!</info>

Now, to prove you own the domain %s and request certificates for this domain, follow these steps:

    1. Create a text file accessible on URL %s
       containing the following content:
       
       %s
       
    2. Check in your browser that the URL %s returns
       the authorization token above.
EOF
                ,
                $authorizationChallenge->getDomain(),
                $webPath,
                $webContent,
                $webPath
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function cleanup(AuthorizationChallenge $authorizationChallenge)
    {
    }
}
