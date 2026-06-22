<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param aggoogleshopping $module
 */
function upgrade_module_1_1_1($module)
{
    $module->unregisterHook('actionCronJob');
    Configuration::deleteByName('AGGOOGLESHOPPING_CRON_HOUR');

    return true;
}
