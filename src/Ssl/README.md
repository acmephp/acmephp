Acme PHP SSL library
====================

[![Build Status](https://img.shields.io/travis/acmephp/acmephp/master.svg?style=flat-square)](https://travis-ci.org/acmephp/acmephp)
[![Quality Score](https://img.shields.io/scrutinizer/g/acmephp/acmephp.svg?style=flat-square)](https://scrutinizer-ci.com/g/acmephp/acmephp)
[![StyleCI](https://styleci.io/repos/59910490/shield)](https://styleci.io/repos/59910490)
[![Packagist Version](https://img.shields.io/packagist/v/acmephp/acmephp.svg?style=flat-square)](https://packagist.org/packages/acmephp/acmephp)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

Acme PHP SSL is a PHP wrapper around OpenSSL extension providing SSL encoding,
decoding, parsing and signing features.

It uses the recommended security settings and let you interact in a OOP
manner with SSL entities (public/private keys, certificates, ...).

> If you want to chat with us or have questions, ping
> @tgalopin or @jderusse on the [Symfony Slack](https://symfony.com/support)!

## Why use Acme PHP SSL?

Acme PHP SSL provides various useful tools solving different use-cases:
- generate public and private keys (see the `Generator\KeyPairGenerator`) ;
- sign data using a private key (see `Signer\DataSigner`) ;
- parse certificates to extract informations about them (see `Parser\CertificateParser`) ;

There are many more possible use-cases, don't hesitate to dig a bit deeper in the
documentation to find out if this library can solve your problem!

## Documentation

Read the official [Acme PHP SSL documentation](https://acmephp.github.io/acmephp/ssl/introduction.html).

## Launch the Test suite

The Acme PHP test suite is located in the main repository:
[https://github.com/acmephp/acmephp#launch-the-test-suite](https://github.com/acmephp/acmephp#launch-the-test-suite).
