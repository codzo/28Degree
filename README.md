# 28Degree Gadget

PHP library to interact with 28Degree Credit Card. 
28Degree Credit Card website is https://28degrees-online.latitudefinancial.com.au

This package will download and cache the webpage. To speed up the performance
you may setup a cronjob to automate the downloading. See script in `bin`
directory.

## Install
Recommanded to install by composer.
```
composer require codzo/platinum28degree
```


## Configuration
Specify the username and password for 28Degree website in `config/app.php` file.
See `config/app.php.dist` for reference.

## Usage
```php
$pd = new \Codzo\Platinum28Degree\Platinum28Degree();

// update cache, omit if you have a cronjob setup
$pd->updateCache();

$account_summary = $pd->getAccountSummary();
$transactions    = $pd->getLatestTransactions();
$cache_mtime     = $pd->getCacheMTime();
```
