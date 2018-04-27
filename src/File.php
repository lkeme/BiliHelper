<?php

/**
 *  Website: https://mudew.com/
 *  Author: Lkeme
 *  Version: 0.0.2
 *  License: The MIT License
 *  Updated: 20180425 18:47:50
 */

namespace lkeme\BiliHelper;

use lkeme\BiliHelper\Curl;
use lkeme\BiliHelper\Sign;
use lkeme\BiliHelper\Log;
use lkeme\BiliHelper\Index;

class File
{
    // RUN
    public static function run()
    {
    }

    // PUT CONF
    public static function writeNewEnvironmentFileWith($key, $value)
    {
        file_put_contents(__DIR__ . '/../conf/' . Index::$conf_file, preg_replace(
            '/^' . $key . '=' . getenv($key) . '/m',
            $key . '=' . $value,
            file_get_contents(__DIR__ . '/../conf/' . Index::$conf_file)
        ));
    }
}