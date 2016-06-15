<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Cli\Command;

use AcmePhp\Ssl\CertificateRequest;
use AcmePhp\Ssl\DistinguishedName;
use AcmePhp\Ssl\KeyPair;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Webmozart\PathUtil\Path;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class RequestCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('request')
            ->setDefinition([
                new InputArgument('domain', InputArgument::REQUIRED, 'The domain to get a certificate for'),
            ])
            ->setDescription('Request a SSL certificate for a domain')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command requests to the ACME server a SSL certificate for a
given domain.

This certificate will be stored in the Acme PHP storage directory.

You need to be the proved owner of the domain you ask a certificate for. To prove your ownership
of the domain, please use commands <info>authorize</info> and <info>check</info> before this one.
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $repository = $this->getRepository();
        $client = $this->getClient();
        $domain = $input->getArgument('domain');

        $output->write("\n");

        /*
         * Generate domain key pair if needed
         */
        if (!$repository->hasDomainKeyPair($domain)) {
            $output->writeln('<info>No domain key pair was found, generating one...</info>');

            /** @var KeyPair $domainKeyPair */
            $domainKeyPair = $this->getContainer()->get('ssl.key_pair_generator')->generateKeyPair();

            $repository->storeDomainKeyPair($domain, $domainKeyPair);
        }

        $this->output->writeln('<info>Loading domain key pair...</info>');
        $domainKeyPair = $repository->loadDomainKeyPair($domain);

        $output->write("\n");

        /*
         * Generate domain distinguished name if needed
         */
        if (!$repository->hasDomainDistinguishedName($domain)) {
            $output->writeln("<info>No domain distinguished name was found, creating one...</info>\n");

            $helper = $this->getHelper('question');

            $countryName = $helper->ask($input, $output, new Question(
                'What is the two-letters code of your country (field "C" of the distinguished name)? : ',
                'FR'
            ));

            $stateOrProvinceName = $helper->ask($input, $output, new Question(
                'What is the full name of your country province (field "ST" of the distinguished name)? : '
            ));

            $localityName = $helper->ask($input, $output, new Question(
                'What is the full name of your locality (field "L" of the distinguished name)? : '
            ));

            $organizationName = $helper->ask($input, $output, new Question(
                'What is the full name of your organization/company (field "O" of the distinguished name)? : '
            ));

            $organizationalUnitName = $helper->ask($input, $output, new Question(
                'What is the full name of your organization/company unit or department (field "OU" of the distinguished name)? : '
            ));

            $emailAddress = $helper->ask($input, $output, new Question(
                'What is your e-mail address (field "E" of the distinguished name)? : '
            ));

            $repository->storeDomainDistinguishedName($domain, new DistinguishedName(
                $domain,
                $countryName,
                $stateOrProvinceName,
                $localityName,
                $organizationName,
                $organizationalUnitName,
                $emailAddress
            ));

            $output->writeln('<info>The distinguished name of this domain has been stored locally, it won\'t be asked on renewal.</info>');
        }

        $distinguishedName = $repository->loadDomainDistinguishedName($domain);

        /*
         * Request certificate
         */
        $output->writeln('<info>Creating Certificate Signing Request ...</info>');
        $csr = new CertificateRequest($distinguishedName, $domainKeyPair);

        $output->writeln(sprintf('<info>Requesting certificate for domain %s ...</info>', $domain));
        $response = $client->requestCertificate($domain, $csr);

        $repository->storeDomainCertificate($domain, $response->getCertificate());

        $help = <<<'EOF'

<info>The SSL certificate was fetched successfully!</info>

5 files were created in the Acme PHP storage directory:

    * <info>%private%</info> contains your domain private key (required in many cases). 

    * <info>%cert%</info> contains only your certificate, without the issuer certificate.
      It may be useful in certains cases but you will probably not need it (use fullchain.pem instead).

    * <info>%chain%</info> contains the issuer certificate chain (its certificate, the
      certificate of its issuer, the certificate of the issuer of its issuer, etc.). Your certificate is
      not present in this file.

    * <info>%fullchain%</info> contains your certificate AND the issuer certificate chain.
      You most likely will use this file in your webserver.

    * <info>%combined%</info> contains the fullchain AND your domain private key (some
      webservers expect this format, such as haproxy).
      
You probably want to configure your webserver right now. Here are some instructions for standard ones:

    * <info>Apache</info>
    
      First, you need to enable mod_ssl for Apache. Run the following command:
      
        <info>sudo a2enmod ssl</info>
        
      Then, in the virtual host configuring the domain %domain%, put the following lines:
        
        SSLCertificateFile %cert%
        SSLCertificateKeyFile %private%
        SSLCertificateChainFile %chain%
        
      Finally, restart Apache to take your configuration into account:
      
        <info>sudo service apache2 restart</info>

    * <info>nginx</info>
    
      In the server block configuring the domain %domain%, put the following lines:
        
      server {
        ...
        
        server_name %domain%;

        listen 443 ssl;
        
        ssl_certificate %fullchain%;
        ssl_certificate_key %private%;
        
        ...
      }
        
      Then, reload nginx to take your configuration into account:
      
        <info>sudo service nginx reload</info>

    * <info>haproxy</info>
    
      In your frontend configuration, add the SSL combined certificate. For instance:
      
      frontend www-https
          bind haproxy_www_public_IP:443 ssl crt %combined%
          reqadd X-Forwarded-Proto:\ https
          default_backend www-backend

    * <info>Others</info>
    
      Other configuration possibilities are described in the documentation (https://acmephp.github.io/acmephp/), such as
      <info>platformsh</info>, <info>heroku</info>, etc.
      
<yellow>You also probably want to configure the automatic renewal of the certificate you just got.
Setting this up is easy and described in the documentation: https://acmephp.github.io/acmephp./</yellow>

EOF;

        $replacements = [
            '%private%'   => Path::canonicalize('~/.acmephp/master/private/%s/private.pem'),
            '%cert%'      => Path::canonicalize('~/.acmephp/master/certs/%s/cert.pem'),
            '%chain%'     => Path::canonicalize('~/.acmephp/master/certs/%s/chain.pem'),
            '%fullchain%' => Path::canonicalize('~/.acmephp/master/certs/%s/fullchain.pem'),
            '%combined%'  => Path::canonicalize('~/.acmephp/master/certs/%s/combined.pem'),
        ];

        $this->output->writeln(str_replace(array_keys($replacements), array_values($replacements), $help));
    }
}
