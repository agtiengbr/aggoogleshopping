<?php

class AgGoogleShoppingCronModuleFrontController extends ModuleFrontController
{
    /** @var bool */
    public $ssl = true;

    /** @var bool */
    public $display_header = false;

    /** @var bool */
    public $display_footer = false;

    public function initContent()
    {
        parent::initContent();

        /** @var aggoogleshopping|false $module */
        $module = $this->module;
        if (!$module instanceof aggoogleshopping) {
            http_response_code(503);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'module unavailable';
            exit;
        }

        $token = (string) Tools::getValue('token');
        if (!$module->validateFeedToken($token)) {
            http_response_code(401);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'unauthorized';
            exit;
        }

        $module->runCronRegeneration();
    }
}
