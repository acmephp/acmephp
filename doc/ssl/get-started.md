---
currentMenu: ssl-get-started
---

# Get started

## Installation

You will need the PHP OpenSSL extension to use Acme PHP SSL.

Install this library using Composer:

```
composer require acmephp/ssl
```

## Usage

### SSL entities

This library provides the following SSL entities:

- **PrivateKey**: a private key
- **PublicKey**: a public key
- **ParsedKey**: data resulting of the decoding of a key (public or private)
- **KeyPair**: a couple of public and private key
- **Certificate**: a PEM certificate string (an encoded certificate)
- **ParsedCertificate**: data resulting of the decoding of a parsed certificate
- **DistinguishedName**: required data used to generate a Certificate Request Signing
- **CertificateRequest**: required data used to request a certificate
- **CertificateResponse**: the result of a certificate request

These entities are the objects you will receive from the services provided by this library.

### Generators

Generators are under `AcmePhp\Ssl\Generator` namespace.

- **KeyPairGenerator** generates a **KeyPair** entity (using OpenSSL functions)

### Parsers

Parsers are under `AcmePhp\Ssl\Parser` namespace.

- **CertificateParser** parses certificates (**Certificate** entities) and return **ParsedCertificate** entities
- **KeyParser** parses keys (**PrivateKey or PublicKey** entities) and return **ParsedKey** entities

### Signers

Signers are under `AcmePhp\Ssl\Signer` namespace.

- **CertificateRequestSigner** signs Certificate requests (CSR)
- **DataSigner** signs custom data using a private key

