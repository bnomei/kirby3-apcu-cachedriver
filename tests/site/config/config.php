<?php

return [
    'debug' => false,
    /*
    'whoops' => false,
    'fatal' => function($kirby, $exception) {
        $t = '';
        foreach($exception->getTrace() as $tr) {
            $t .= print_r($tr, true);
        }
        return $exception->getMessage() . $t;
    },
    */

    'bnomei.apcu-cachedriver.store' => false,
];
