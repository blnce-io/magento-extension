<?php
// @codingStandardsIgnoreFile


const MAGENTO_ROOT = __DIR__ . '/../../../../../..';
require_once realpath(MAGENTO_ROOT . '/vendor/autoload.php');

const XDEBUG_CC_UNUSED = 1;
const XDEBUG_CC_DEAD_CODE = 2;

/**
 * @SuppressWarnings(PHPMD.ShortMethodName)
 */
if (!function_exists('__')) {
    function __()
    {
        $argc = func_get_args();

        $text = array_shift($argc);
        if (!empty($argc) && is_array($argc[0])) {
            $argc = $argc[0];
        }

        return new \Magento\Framework\Phrase($text, $argc);
    }
}
