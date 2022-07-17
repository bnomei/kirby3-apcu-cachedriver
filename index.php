<?php

@include_once __DIR__ . '/vendor/autoload.php';

if (!class_exists('Bnomei\ApcuCache')) {
    require_once __DIR__ . '/classes/ApcuCache.php';
}

if (! function_exists('apcugc')) {
    function apcugc(array $options = [])
    {
        return \Bnomei\ApcuCache::singleton($options);
    }
}

Kirby::plugin('bnomei/apcu-cachedriver', [
    'options' => [
        'cache' => true, // create cache folder
        'store' => true, // php memory cache
        'store-ignore' => '', // if contains then ignore
    ],
    'cacheTypes' => [
        'apcugc' => \Bnomei\ApcuCache::class
    ],
]);
