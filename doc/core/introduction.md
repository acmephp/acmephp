---
currentMenu: core-introduction
---

# Acme PHP Core

> This library is a part of the [Acme PHP initiative](https://github.com/acmephp),
> aiming to intregrate [Let's Encrypt](https://github.com/acmephp)
> in the PHP world at the application level.

Acme PHP Core is the core of the Acme PHP project : it is a basis for the others more
high-level repositories.

## When use Acme PHP Core?

You usually will want to use either [the Acme PHP CLI client](https://github.com/acmephp/cli)
or [an implementation for your application framework](https://github.com/acmephp).

However, in some cases, you may want to manage from the application itself your SSL certificates.
In these cases, this library will be useful to you.

Acme PHP Core does nothing more than implementing the
[Let's Encrypt/ACME protocol](https://github.com/letsencrypt/acme-spec) : the generated SSL keys
and certificates are stored in memory and then given to your script. You are the one in charge
of storing them somewhere. You can use
[the Acme PHP Persistence](https://github.com/acmephp/persistence) library to help you do so.
