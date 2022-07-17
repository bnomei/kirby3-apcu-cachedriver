# Kirby3 Extended APCu Cache-Driver

![Release](https://flat.badgen.net/packagist/v/bnomei/kirby3-apcu-cachedriver?color=ae81ff)
![Downloads](https://flat.badgen.net/packagist/dt/bnomei/kirby3-apcu-cachedriver?color=272822)
[![Build Status](https://flat.badgen.net/travis/bnomei/kirby3-apcu-cachedriver)](https://travis-ci.com/bnomei/kirby3-apcu-cachedriver)
[![Coverage Status](https://flat.badgen.net/coveralls/c/github/bnomei/kirby3-apcu-cachedriver)](https://coveralls.io/github/bnomei/kirby3-apcu-cachedriver)
[![Maintainability](https://flat.badgen.net/codeclimate/maintainability/bnomei/kirby3-apcu-cachedriver)](https://codeclimate.com/github/bnomei/kirby3-apcu-cachedriver)
[![Twitter](https://flat.badgen.net/badge/twitter/bnomei?color=66d9ef)](https://twitter.com/bnomei)

Extends the basic APCu Cache-Driver for Kirby 3 with garbage collection and in-memory store

## Commercial Usage

> <br>
> <b>Support open source!</b><br><br>
> This plugin is free but if you use it in a commercial project please consider to sponsor me or make a donation.<br>
> If my work helped you to make some cash it seems fair to me that I might get a little reward as well, right?<br><br>
> Be kind. Share a little. Thanks.<br><br>
> &dash; Bruno<br>
> &nbsp;

| M | O | N | E | Y |
|---|----|---|---|---|
| [Github sponsor](https://github.com/sponsors/bnomei) | [Patreon](https://patreon.com/bnomei) | [Buy Me a Coffee](https://buymeacoff.ee/bnomei) | [Paypal dontation](https://www.paypal.me/bnomei/15) | [Hire me](mailto:b@bnomei.com?subject=Kirby) |

## Installation

- unzip [master.zip](https://github.com/bnomei/kirby3-apcu-cachedriver/archive/master.zip) as folder `site/plugins/kirby3-apcu-cachedriver` or
- `git submodule add https://github.com/bnomei/kirby3-apcu-cachedriver.git site/plugins/kirby3-apcu-cachedriver` or
- `composer require bnomei/kirby3-apcu-cachedriver`

## Why

### Memcached < File < Redis < SQLite < APCu

Kirby ships with built in support for File, Memcached and APCu Cache Drivers. APCu is widely available and performs great. It's only drawback is the limited memory size compared to SQLite or Redis.

### In-memory store

Usually each call to the same cached item, even repeated calls in the same http request, would yield repeated requests to the APCu cache. With the in-memory store of this plugin retrieved items will be stored in a PHP array for the current http request and returned from there without the round trip to APCu. This might increase total memory usage of your PHP script but significantly speeds up repeated calls. You can turn of this behaviour in the settings if you do not need it.

### Garbage Collection

Kirby removes expired cached items only if they are requested again and then deemed to be expired or when the cache is flushed completely. This might result in long expired items taking up precious memory in the cache. 
The default APCu implementation does not know about Kirbys expire timestamps thus when running out of memory it might even keep expired items and overwrite those not expired yet - depending on creation time but not on the expiry timestamp.
With the garbage collection of this plugin all expired items are removed automatically on each http request keeping the cache as small as possible and thus reducing said cache overwrites to as few as possible.

## Usage

### Cache methods

```php
$cache = \Bnomei\ApcuCache::singleton(); // or
$cache = apcugc();

$cache->set('key', 'value', $expireInMinutes);
$value = apcugc()->get('key', $default);

apcugc()->remove('key');
apcugc()->flush();
```

### Benchmark

```php
apcugc()->benchmark(1000);
```

```shell script
apcugc : XXX
file   : XXX
```

> ATTENTION: This will create and remove a lot of cache files and apcu entries

### No cache when debugging

When Kirbys global debug config is set to `true` the complete plugin cache will be flushed and no caches will be read. But entries will be created. This will make you live easier – trust me.

### How to use ApcuGC with Lapse or Boost

You need to set the cache driver for the [lapse plugin](https://github.com/bnomei/kirby3-lapse) to `apcu`.

**site/config/config.php**
```php
<?php
return [
    'bnomei.lapse.cache' => ['type' => 'apcugc'],
    'bnomei.boost.cache' => ['type' => 'apcugc'],
    //... other options
];
```

### Setup Content-File Cache

Use [Kirby 3 Boost](https://github.com/bnomei/kirby3-boost) to setup a cache for content files.

## Settings

| bnomei.apcu-cachedriver.            | Default        | Description               |
|---------------------------|----------------|---------------------------|
| store | `true` | keep accessed cache items stored in PHP memory for faster recurring access  |
| store-ignore | `` | if key contains that string then ignore  |

## Dependencies

- PHP APCu extension

## Disclaimer

This plugin is provided "as is" with no guarantee. Use it at your own risk and always test it yourself before using it in a production environment. If you find any issues, please [create a new issue](https://github.com/bnomei/kirby3-apcu-cachedriver/issues/new).

## License

[MIT](https://opensource.org/licenses/MIT)

It is discouraged to use this plugin in any project that promotes racism, sexism, homophobia, animal abuse, violence or any other form of hate speech.
