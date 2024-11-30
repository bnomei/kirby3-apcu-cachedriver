# Kirby Extended APCu Cache-Driver

![Release](https://flat.badgen.net/packagist/v/bnomei/kirby3-apcu-cachedriver?color=ae81ff&icon=github&label)
[![Discord](https://flat.badgen.net/badge/discord/bnomei?color=7289da&icon=discord&label)](https://discordapp.com/users/bnomei)
[![Buymecoffee](https://flat.badgen.net/badge/icon/donate?icon=buymeacoffee&color=FF813F&label)](https://www.buymeacoffee.com/bnomei)

Extends the basic APCu Cache-Driver for Kirby with garbage collection and in-memory store

## Installation

- unzip [master.zip](https://github.com/bnomei/kirby3-apcu-cachedriver/archive/master.zip) as folder `site/plugins/kirby3-apcu-cachedriver` or
- `git submodule add https://github.com/bnomei/kirby3-apcu-cachedriver.git site/plugins/kirby3-apcu-cachedriver` or
- `composer require bnomei/kirby3-apcu-cachedriver`

## Why

### Memcached < File < Redis < SQLite < APCu

Kirby ships with built-in support for File, Memcached, and APCu Cache Drivers. APCu is widely available and performs great. Its only drawback is the limited memory size compared to SQLite or Redis.

### In-memory store

Usually, each call to the same cached item even repeated calls in the same HTTP request, would yield repeated requests to the APCu cache. With this plugin's in-memory store, retrieved items will be stored in a PHP array for the current http request and returned from there without the round trip to APCu. This might increase the total memory usage of your PHP script but significantly speeds up repeated calls. You can turn of this behaviour in the settings if you do not need it.

### Garbage Collection

Kirby removes expired cached items only if they are requested again and then deemed expired or when the cache is flushed completely. This might result in long-expired items taking up memory in the cache. 

The default APCu implementation does not actively collect garbage but only removes expired cache items when running out of memory. That is a perfectly fine strategy. This plugin minimizes this behaviour by actively purging expired items. 

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
apcugc : 0.026898145675659 
file   : 0.13169479370117
```

> ATTENTION: This will create and remove a lot of cache files and apcu entries

### No cache when debugging

When Kirby's global debug config is set to `true,` the complete plugin cache will be flushed, and no caches will be read. However, entries will be created. This will make your life easier—trust me.

### How to use ApcuGC with Lapse or Boost

You must set the cache driver for the [lapse plugin](https://github.com/bnomei/kirby3-lapse) to `apcugc`.

**site/config/config.php**
```php
<?php
return [
    'bnomei.lapse.cache' => ['type' => 'apcugc'],
    'bnomei.boost.cache' => ['type' => 'apcugc'],
    //... other options
];
```

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
