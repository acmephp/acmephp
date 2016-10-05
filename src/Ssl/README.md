Acme PHP SSL library
====================

[![Join the chat at https://gitter.im/acmephp/acmephp](https://badges.gitter.im/acmephp/acmephp.svg)](https://gitter.im/acmephp/acmephp?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

[![Build Status](https://img.shields.io/travis/acmephp/acmephp/master.svg?style=flat-square)](https://travis-ci.org/acmephp/acmephp)
[![Quality Score](https://img.shields.io/scrutinizer/g/acmephp/acmephp.svg?style=flat-square)](https://scrutinizer-ci.com/g/acmephp/acmephp)
[![StyleCI](https://styleci.io/repos/59910490/shield)](https://styleci.io/repos/59910490)
[![Packagist Version](https://img.shields.io/packagist/v/acmephp/acmephp.svg?style=flat-square)](https://packagist.org/packages/acmephp/acmephp)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

> **Note:** This project is in beta but follow a strict BC policy, even in beta (see
> [the Backward Compatibility policy of Acme PHP](https://github.com/acmephp/acmephp#backward-compatibility-policy)
> for more informations).
>
> Moreover, this repository is in beta stage only to follow the same versionning as the global project.
> This library's API won't change in the near future (we don't want BC breaks now).

Acme PHP SSL is a PHP wrapper around OpenSSL extension providing SSL encoding,
decoding, parsing and signing features.

It uses the recommended security settings and let you interact in a OOP
manner with SSL entities (public/private keys, certificates, ...).

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
