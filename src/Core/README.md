Acme PHP Core
=============

[![Build Status](https://img.shields.io/travis/acmephp/core/master.svg?style=flat-square)](https://travis-ci.org/acmephp/core)
[![Quality Score](https://img.shields.io/scrutinizer/g/acmephp/core.svg?style=flat-square)](https://scrutinizer-ci.com/g/acmephp/core)
[![StyleCI](https://styleci.io/repos/51226077/shield)](https://styleci.io/repos/51226077)
[![Packagist Version](https://img.shields.io/packagist/v/acmephp/core.svg?style=flat-square)](https://packagist.org/packages/acmephp/core)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

> Note : this repository is in alpha stage.

> This library is a part of the [Acme PHP initiative](https://github.com/acmephp),
> aiming to intregrate [Let's Encrypt](https://github.com/acmephp)
> in the PHP world at the application level.

Acme PHP Core is the core of the Acme PHP project : it is a basis for the others more
high-level repositories.

## When use Acme PHP Core?

You usually will want to use either [the Acme PHP CLI client](https://github.com/acmephp/cli)
or [an implementation for your application framework](https://github.com/acmephp).

However, in some cases, you may want to manage SSL certificates directly from your application.
In these cases, this library will be useful to you.

Acme PHP Core does nothing more than implementing the
[Let's Encrypt/ACME protocol](https://github.com/letsencrypt/acme-spec) : the generated SSL keys
and certificates are stored in memory and then given to your script. You are the one in charge
of storing them somewhere. You can use
[the Acme PHP Persistence](https://github.com/acmephp/persistence) library to help you do so.

## Documentation

Read the official [Acme PHP Core documentation](https://acmephp.github.io/core/).

## Launch the Test suite

The Acme PHP Core test suite uses the Docker Boulder image to create an ACME server.

In the Acme PHP Core root directory:

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
