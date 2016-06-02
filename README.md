RCHConfigAccessBundle
=====================

Retrieve configuration values from any container-aware context using dot syntax.

Why?
----

In Symfony, when you need to get the value of any configuration (default in `app/config`) the answer is always _parameters_.

But, what about the final configuration? After that the DI container has been compiled? After that compiler passes and bundle extensions changed it or merged it with a default one?

Actuall there is no solution excepted processing the whole configuration of a bundle each time you need it, even partially.
This bundle is intended to solve this problem.

Related issues:
- [_Symfony2 accessing variables defined in config?_](http://stackoverflow.com/questions/8544392/symfony2-accessing-variables-defined-in-config-yml-and-config-yml#answer-22603488) (Stack Overflow)
- [_How do I read configuration settings from symfony2 config?_](http://stackoverflow.com/questions/4821692/how-do-i-read-configuration-settings-from-symfony2-config-yml#answer-22599416) (Stack Overflow)
- [Symfony2 find matching firewall based on route name](http://stackoverflow.com/questions/29285514/symfony-2-find-matching-firewall-based-on-route-name)

Installation
------------

#### Download the bundle

```bash
$ composer require rch/config-access-bundle
```

This command requires you to have [Composer](https://getcomposer.org/doc/00-intro.md) installed globally.

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

// Security
$securityConfig = $accessor->get('security');

// Current firewall
foreach ($accessor->get('security.firewalls') as $name => $mapping) {
    if (preg_match(sprintf('{%s}', $mapping['pattern']), $request->attributes->get('_route'))) {
        printf('Current firewall: %s', $name);
    }
}

// Serializer
$serializerConfig = $accessor->get('framework.serializer');
$isEnabled = $serializerConfig->enabled
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
