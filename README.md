# Digipolis Symfony Dominaitor Sock Bundle

## Compatibility

This bundle is compatible with all Symfony 3.4.* releases.

## Installation

You can use composer to install the bundle in an existing symfony project.

```
composer require digipolisgent/domainator9k-sock-bundle
```

Then, update your ```app/AppKernel.php``` file.

```php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = [
        // ...
        new DigipolisGent\Domainator9k\SockBundle\DigipolisGentDomainator9kSockBundle()
    ];

    // ...
}
```

In ```app/config/config.yml``` you need to provide 3 values.

```yaml
digipolis_gent_domainator9k_sock:
    host: 'your-host'
    user_token: 'your-user-token'
    client_token: 'your-client-token'
```

