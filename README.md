RCHConfigAccessBundle
=====================

[![Build Status](https://travis-ci.org/chalasr/RCHConfigAccessBundle.svg?branch=master)](https://travis-ci.org/chalasr/RCHConfigAccessBundle)
[![StyleCI](https://styleci.io/repos/58928191/shield)](https://styleci.io/repos/58928191)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/6a39d33a-f93a-4016-95fb-cafd03ec2f3a/mini.png)](https://insight.sensiolabs.com/projects/6a39d33a-f93a-4016-95fb-cafd03ec2f3a)

Retrieve final configuration values from any container-aware context.

Why?
----

In Symfony, when you need to get the value of any configuration (default in `app/config`) the answer is always _parameters_.

But, what about the final configuration? After that the DI container has been compiled? After that compiler passes and bundle extensions changed it or merged it with a default one?

Actuall there is no solution excepted processing the whole configuration of a bundle each time you need it, even partially.
This bundle is intended to solve this problem.

Related issues:
- [_Symfony2 accessing variables defined in config?_](http://stackoverflow.com/questions/8544392/symfony2-accessing-variables-defined-in-config-yml-and-config-yml#answer-22603488) (Stack Overflow)
- [_How do I read configuration settings from symfony2 config?_](http://stackoverflow.com/questions/4821692/how-do-i-read-configuration-settings-from-symfony2-config-yml#answer-22599416) (Stack Overflow)

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

Usage
-----

#### Get configuration values

```php
<?php

$accessor = $this->container->get('rch.config_access.accessor');

$accessor->get('security');
// array('encoders' => array(...), 'providers' => array(...), ...)

$accessor->get('framework.serializer');
// array('enabled' => true, ...)

$accessor->get('framework.serializer.enabled');
// true

$accessor->get('stof_doctrine_extensions.uploadable');
// array('orm' => array(...), 'uploadable' => array(...), ...)

$accessor->get('lexik_jwt_authentication.encoder.service'); 
// 'lexik_jwt_authentication.encoder.default'

$accessor->get('frameorf.default_locale'); 
$accessor->get('framework.default_loal');

// Did you mean "framework.default_locale"?
```

#### Inject them into your services

```yaml
services:
    foo_manager:
        arguments: 
            - '@=service("rch_config_access.accessor").get("security")'
```

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

Contributing
------------

[Guidelines](CONTRIBUTING.md)

License
-------

The code is released under the MIT license.

For the whole copyright, see the distributed [LICENSE](LICENSE) file.
