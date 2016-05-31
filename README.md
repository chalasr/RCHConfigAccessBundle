RCHConfigAccessBundle
=====================

Get configuration values from any container-aware context using dot syntax.

Installation
------------

#### Download the bundle

```bash
$ composer require rch/config-access-bundle
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

#### Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
// app/AppKernel.php

$bundles = array(
    // ...
    new RCH\ConfigAccessBundle\RCHConfigAccessBundle(),
);
```

Configuration
--------------

```yaml
rch_config_access:
    default_path: "%kernel.root_dir/config/"
    custom_path:
        - "@AppBundle/Resources/custom/"
```

Usage
-----

#### Retrieve configuration value by dot path

```php
<?php

$accessor = $this->container->get('rch.config_access.accessor');

$fullSecurity = $accessor->get('security');
$routerHttpPort = $accessor->get('framework.router.http_port');
// ...
```

#### Inject them into your services through expressions.

```php
<?php

namespace AppBundle\Services;

class FooManager 
{
    public function __construct(array $security)
    {
        $this->security = $security;
    }
    
    // ...
}
```

```yaml
services:
    foo_manager:
        arguments: 
            - '@=service("rch_config_access.accessor").get("security")'
```

Contributing
------------

This project needs features.

Please follow [the contribution guidelines](CONTRIBUTING.md).

License
-------

The code is released under the MIT license.

For the whole copyright, see the [LICENSE](LICENSE) file.
