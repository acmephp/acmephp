Acme PHP
========

[![Join the chat at https://gitter.im/acmephp/acmephp](https://badges.gitter.im/acmephp/acmephp.svg)](https://gitter.im/acmephp/acmephp?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

[![Build Status](https://img.shields.io/travis/acmephp/acmephp/master.svg?style=flat-square)](https://travis-ci.org/acmephp/acmephp)
[![Quality Score](https://img.shields.io/scrutinizer/g/acmephp/acmephp.svg?style=flat-square)](https://scrutinizer-ci.com/g/acmephp/acmephp)
[![StyleCI](https://styleci.io/repos/59910490/shield)](https://styleci.io/repos/59910490)
[![Packagist Version](https://img.shields.io/packagist/v/acmephp/acmephp.svg?style=flat-square)](https://packagist.org/packages/acmephp/acmephp)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

> **Note:** this project is in active development.

Acme PHP is a simple yet very extensible CLI client for Let's Encrypt that will help
you get and renew free HTTPS certificates.

Acme PHP is also an initiative to bring a robust, stable and powerful implementation
of the ACME protocol in PHP. Using the Acme PHP library and core components, you will be
able to deeply integrate the management of your certificates directly in your application
(for instance, renew your certificates from your web interface).

## Why should I use Acme PHP when I have an official client?

Acme PHP provides several major improvements over the default clients:
-   Acme PHP comes by nature as a single binary file: a single download and you are ready to start working ;
-   Acme PHP is based on a configuration file (`~/.acmephp/acmephp.conf`) instead command line arguments.
    Thus, the configuration is much more expressive and the same setup is used at every renewal ;
-   Acme PHP can monitor your CRONs and can send you alerts in many differents places:
    E-mail, Slack, HipChat, Flowdock, Fleep (thanks to [Monolog](https://github.com/Seldaek/monolog)!)
-   Acme PHP is very extendible it to create the certificate files structure you need for your webserver.
    It brings several default formatters to create classical file structures
    (nginx, nginx-proxy, haproxy, etc.) but you can very easily create our own if you need to ;

## Documentation

Read the official [Acme PHP documentation](https://acmephp.github.io).

## Launch the Test suite

The Acme PHP test suite uses the Docker Boulder image to create an ACME server.

In the Acme PHP root directory:

```
# Get the dev dependencies
composer update

# Start the ACME server Docker container
docker run -d --net host acmephp/testing-ca

# Run the tests
vendor/bin/phpunit
```

**Warning**: as the acmephp/testing-ca Docker image needs to be mapped to the host network,
you may have ports conflicts. See [https://github.com/acmephp/testing-ca](https://github.com/acmephp/testing-ca)
for more informations.
