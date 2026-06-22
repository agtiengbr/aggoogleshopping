<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param aggoogleshopping $module
 */
function upgrade_module_1_1_0($module)
{
    return $module->installDatabase()
        && $module->installAdminTabs();
}
