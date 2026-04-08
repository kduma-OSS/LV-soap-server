# Laravel SOAP Server

[![Latest Stable Version](https://poser.pugx.org/kduma/laravel-soap-server/v/stable.svg)](https://packagist.org/packages/kduma/laravel-soap-server)
[![Total Downloads](https://poser.pugx.org/kduma/laravel-soap-server/downloads.svg)](https://packagist.org/packages/kduma/laravel-soap-server)
[![License](https://poser.pugx.org/kduma/laravel-soap-server/license.svg)](https://packagist.org/packages/kduma/laravel-soap-server)

Wrapper for creating SOAP web service servers in Laravel using Laminas/Soap.

## Requirements

- PHP `^8.3`
- Laravel `^12.0 || ^13.0`
- `ext-soap`

## Installation

```bash
composer require kduma/laravel-soap-server
```

## Usage

Create a service class:

```php
class MathService
{
    /** @param float $a */
    /** @param float $b */
    public function add(float $a = 0, float $b = 0): float
    {
        return $a + $b;
    }
}
```

Create a controller:

```php
class MySoapController extends \KDuma\SoapServer\AbstractSoapServerController
{
    protected function getService(): string { return MathService::class; }
    protected function getEndpoint(): string { return route('soap'); }
    protected function getWsdlUri(): string { return route('soap.wsdl'); }
}
```

Register routes:

```php
Route::name('soap.wsdl')->get('/soap.wsdl', [MySoapController::class, 'wsdlProvider']);
Route::name('soap')->post('/soap', [MySoapController::class, 'soapServer']);
```
