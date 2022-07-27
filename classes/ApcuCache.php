<?php

declare(strict_types=1);

namespace Bnomei;

use Kirby\Cache\FileCache;
use Kirby\Cache\Value;
use Kirby\Toolkit\A;
use Kirby\Toolkit\F;
use Kirby\Toolkit\Str;

final class ApcuCache extends FileCache
{
    private $shutdownCallbacks = [];

    /** @var array $store */
    private $store;

    /** @var array $index */
    private $index;

    public const INDEX = "apcugc-index";

    public function __construct(array $options = [])
    {
        $this->setOptions($options);

        parent::__construct($this->options);

        $this->store = [];
        $this->index = kirby()->cache('bnomei.apcu-cachedriver')->get(static::INDEX, []);

        if ($this->options['debug']) {
            $this->flush();
        }

        $this->garbagecollect();
    }

    public function __destruct()
    {
        foreach ($this->shutdownCallbacks as $callback) {
            if (!is_string($callback) && is_callable($callback)) {
                $callback();
            }
        }
        kirby()->cache('bnomei.apcu-cachedriver')->set(static::INDEX, $this->index);
    }

    public function register_shutdown_function($callback)
    {
        $this->shutdownCallbacks[] = $callback;
    }

    /**
     * @param string|null $key
     * @return array
     */
    public function option(?string $key = null)
    {
        if ($key) {
            return A::get($this->options, $key);
        }
        return $this->options;
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, $value, int $minutes = 0): bool
    {
        /* SHOULD SET EVEN IN DEBUG
        if ($this->option('debug')) {
            return true;
        }
        */

        $rawKey = $key;

        $key = $this->key($key);
        $value = new Value($value, $minutes);
        $expire = $value->expires() ?? 0;

        $this->index[$rawKey] = $expire;

        $success = apcu_store($key, $value->toJson(), $expire);

        if ($this->option('store') && (empty($this->option('store-ignore')) || str_contains($key, $this->option('store-ignore')) === false)) {
            $this->store[$key] = $value;
        }

        return $success;
    }

    public function exists(string $key): bool
    {
        $key = $this->key($key);
        $value = A::get($this->store, $key);

        return $value !== null || apcu_exists($key);
    }

    /**
     * @inheritDoc
     */
    public function retrieve(string $key): ?Value
    {
        $key = $this->key($key);

        $value = A::get($this->store, $key);
        if ($value === null && $fetch = apcu_fetch($key)) {
            $value = Value::fromJson($fetch);

            if ($this->option('store') && (empty($this->option('store-ignore')) || str_contains($key, $this->option('store-ignore')) === false)) {
                $this->store[$key] = $value;
            }
        }
        return $value;
    }

    public function get(string $key, $default = null)
    {
        if ($this->option('debug')) {
            return $default;
        }

        return parent::get($key, $default);
    }

    /**
     * @inheritDoc
     */
    public function remove(string $key): bool
    {
        $rawKey = $key;
        $key = $this->key($key);

        if (array_key_exists($key, $this->store)) {
            unset($this->store[$key]);
        }
        if (array_key_exists($rawKey, $this->index)) {
            unset($this->index[$rawKey]);
        }

        return apcu_delete($key);
    }

    /**
     * @inheritDoc
     */
    public function flush(): bool
    {
        $this->store = [];
        $this->index = [];

        if (empty($this->options['prefix']) === false) {
            return apcu_delete(new APCUIterator('!^' . preg_quote($this->options['prefix']) . '!'));
        } else {
            return apcu_clear_cache();
        }
    }

    public function garbagecollect(): bool
    {
        $count = 0;
        $indexCopy = $this->index;
        foreach ($indexCopy as $key => $expires) {
            if ($expires && $expires < time()) {
                $this->remove($key); // changes $this->index thus the copy
                $count++;
            }
        }
        return $count > 0;
    }

    private static $singleton;
    public static function singleton(array $options = []): self
    {
        if (self::$singleton) {
            return self::$singleton;
        }
        self::$singleton = new self($options);
        return self::$singleton;
    }

    private function setOptions(array $options)
    {
        $root = null;
        $cache = kirby()->cache('bnomei.apcu-cachedriver');
        if (is_a($cache, FileCache::class)) {
            $root = A::get($cache->options(), 'root');
            if ($prefix =  A::get($cache->options(), 'prefix')) {
                $root .= '/' . $prefix;
            }
        } else {
            $root = kirby()->roots()->cache();
        }

        $this->options = array_merge([
            'root' => $root,
            'debug' => \option('debug'),
            'store' => \option('bnomei.apcu-cachedriver.store', true),
            'store-ignore' => \option('bnomei.apcu-cachedriver.store-ignore'),
        ], $options);
    }

    public function benchmark(int $count = 10)
    {
        $prefix = "apcu-benchmark-";
        $apcu = $this;
        $file = kirby()->cache('bnomei.apcu-cachedriver'); // neat, right? ;-)

        foreach (['apcugc' => $apcu, 'file' => $file] as $label => $driver) {
            $time = microtime(true);
            for ($i = 0; $i < $count; $i++) {
                $key = $prefix . $i;
                if (!$driver->get($key)) {
                    $driver->set($key, Str::random(1000));
                }
            }
            for ($i = $count * 0.6; $i < $count * 0.8; $i++) {
                $key = $prefix . $i;
                $driver->remove($key);
            }
            for ($i = $count * 0.8; $i < $count; $i++) {
                $key = $prefix . $i;
                $driver->set($key, Str::random(1000));
            }
            echo $label . ' : ' . (microtime(true) - $time) . PHP_EOL;

            // cleanup
            for ($i = 0; $i < $count; $i++) {
                $key = $prefix . $i;
                $driver->remove($key);
            }
        }
    }
}
