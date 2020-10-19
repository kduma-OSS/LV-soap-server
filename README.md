# laravel-soap-server

[![Latest Version on Packagist](https://img.shields.io/packagist/v/kduma/laravel-soap-server.svg?style=flat-square)](https://packagist.org/packages/kduma/laravel-soap-server)
[![Build Status](https://img.shields.io/travis/kduma/laravel-soap-server/master.svg?style=flat-square)](https://travis-ci.org/kduma/laravel-soap-server)
[![Quality Score](https://img.shields.io/scrutinizer/g/kduma/laravel-soap-server.svg?style=flat-square)](https://scrutinizer-ci.com/g/kduma/laravel-soap-server)
[![Total Downloads](https://img.shields.io/packagist/dt/kduma/laravel-soap-server.svg?style=flat-square)](https://packagist.org/packages/kduma/laravel-soap-server)

Laravel SOAP service server

## Installation

You can install the package via composer:

```bash
composer require kduma/laravel-soap-server
```

## Usage

Create server class - remember to provide correct typehints and doc blocks:
```php
class SoapDemoServer
{
    /**
     * Adds two numbers
     *
     * @param float $a
     * @param float $b
     *
     * @return float
     */
    public function sum(float $a = 0, float $b = 0): float
    {
        return $a + $b;
    }

    /**
     * Returns your data
     *
     * @return Person
     */
    public function me(): Person
    {
        return new Person('John', 'Doe');
    }

    /**
     * Says hello to person provided
     *
     * @param Person $person
     *
     * @return string
     */
    public function hello(Person $person): string
    {
        return sprintf("Hello %s!", $person->first_name);
    }
}
```

...and DTO objects:
```php
class Person
{
    /**
     * @var string
     */
    public $first_name;

    /**
     * @var string
     */
    public $last_name;

    /**
     * @param string $first_name
     * @param string $last_name
     */
    public function __construct(string $first_name, string $last_name)
    {
        $this->first_name = $first_name;
        $this->last_name = $last_name;
    }
}
```

Create controller class for your SOAP server:
```php
class MySoapController extends \KDuma\SoapServer\AbstractSoapServerController
{
    protected function getService(): string
    {
        return SoapDemoServer::class;
    }

    protected function getEndpoint(): string
    {
        return route('my_soap_server');
    }

    protected function getWsdlUri(): string
    {
        return route('my_soap_server.wsdl');
    }

    protected function getClassmap(): array
    {
        return [
            'SoapPerson' => Person::class,
        ];
    }
}
```

Register routes in your routes file:
```php
Route::name('my_soap_server.wsdl')->get('/soap.wsdl', [MySoapController::class, 'wsdlProvider']);
Route::name('my_soap_server')->post('/soap', [MySoapController::class, 'soapServer']);
```

### Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email git@krystian.duma.sh instead of using the issue tracker.

## Credits

- [Krystian Duma](https://github.com/kduma)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).
