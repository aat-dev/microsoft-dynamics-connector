Microsoft Dynamics Connector for PHP
====================================

The Microsoft Dynamics Connector is a PHP package to make it easy to integrate your
PHP application with CRM's SOAP service and RESTful endpoint (OData).

## Usage

```php
$connector new new AAT\CRM\SoapConnector(
  'http://msdynamicshost',
  ['user' => 'username', 'password' => 'password'],
  'Execute'
);

$request = 'name-of-request';
$data = array(); // Request data.

$response = $connector->get($request, $data)->doRequest();
```

## Installing Microsoft Dynamics Connector

The recommended way to install the Microsoft Dynamics Connector is through
[Composer](http://getcomposer.org).

```bash
# Install Composer
curl -sS https://getcomposer.org/installer | php
```

Next, run the Composer command to install the latest stable version of the Microsoft Dynamics Connector:

```bash
composer.phar require aat-dev/microsoft-dynamics-connector
```

After installing, you need to require Composer's autoloader:

```php
require 'vendor/autoload.php';
```

You can then later update the Microsoft Dynamics Connector using composer:

 ```bash
composer.phar update
 ```
 
 