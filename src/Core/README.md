Acme PHP Core library
=====================

[![Join the chat at https://gitter.im/acmephp/acmephp](https://badges.gitter.im/acmephp/acmephp.svg)](https://gitter.im/acmephp/acmephp?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

[![Build Status](https://img.shields.io/travis/acmephp/acmephp/master.svg?style=flat-square)](https://travis-ci.org/acmephp/acmephp)
[![Quality Score](https://img.shields.io/scrutinizer/g/acmephp/acmephp.svg?style=flat-square)](https://scrutinizer-ci.com/g/acmephp/acmephp)
[![StyleCI](https://styleci.io/repos/59910490/shield)](https://styleci.io/repos/59910490)
[![Packagist Version](https://img.shields.io/packagist/v/acmephp/acmephp.svg?style=flat-square)](https://packagist.org/packages/acmephp/acmephp)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

> **Note:** This project is in beta but follow a strict BC policy, even in beta (see
> [the Backward Compatibility policy of Acme PHP](https://github.com/acmephp/acmephp#backward-compatibility-policy)
> for more informations).

Acme PHP Core is the core of the Acme PHP project : it is a basis for the others more
high-level repositories. It consists of a raw implementation of the Let's Encrypt ACME protocol.

## When use Acme PHP Core?

You usually will want to use either [the Acme PHP CLI client](https://github.com/acmephp/cli)
or [an implementation for your application framework](https://github.com/acmephp).

However, in some cases, you may want to manage SSL certificates directly from your application.
In these cases, this library will be useful to you.

Acme PHP Core does nothing more than implementing the [Let's Encrypt/ACME protocol](https://github.com/letsencrypt/acme-spec) :
the generated SSL keys and certificates are stored in memory and returned to your script. You are the one in charge
of storing them somewhere persistent.

## Documentation

Read the official [Acme PHP documentation](https://acmephp.github.io).

## Launch the Test suite

The Acme PHP test suite is located in the main repository:
[https://github.com/acmephp/acmephp#launch-the-test-suite](https://github.com/acmephp/acmephp#launch-the-test-suite).
