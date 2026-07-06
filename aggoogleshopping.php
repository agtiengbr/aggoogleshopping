<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/classes/AgGoogleShoppingAvailabilityResolver.php';
require_once dirname(__FILE__) . '/classes/AgGoogleShoppingFeedBuilder.php';
require_once dirname(__FILE__) . '/classes/AgGoogleShoppingFieldRegistry.php';
require_once dirname(__FILE__) . '/classes/AgGoogleShoppingCategoryMappingRepository.php';
require_once dirname(__FILE__) . '/classes/AgGoogleShoppingCategoryMappingResolver.php';
require_once dirname(__FILE__) . '/classes/AgGoogleShoppingFieldValueResolver.php';

class aggoogleshopping extends Module
{
    public const CONFIG_SKU_FIELD = 'AGGOOGLESHOPPING_SKU_FIELD';
    public const CONFIG_FEED_TOKEN = 'AGGOOGLESHOPPING_FEED_TOKEN';
    public const CONFIG_LAST_GENERATED = 'AGGOOGLESHOPPING_LAST_GENERATED';

    public function __construct()
    {
        $this->name = 'aggoogleshopping';
        $this->tab = 'advertising_marketing';
        $this->version = '1.2.2';
        $this->author = 'AGTI';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '1.6.0.0', 'max' => '9.99.99'];

        parent::__construct();

        $this->displayName = $this->moduleTrans('AG Google Shopping Feed');
        $this->description = $this->moduleTrans('Gera feed XML Google Shopping (RSS 2.0) com token de segurança e URL para atualização agendada.');
        $this->confirmUninstall = $this->moduleTrans('Remover feed e configurações?');
    }

    public function isUsingNewTranslationSystem()
    {
        return version_compare(_PS_VERSION_, '1.7.0.0', '>=');
    }

    /**
     * @param string $string
     *
     * @return string
     */
    private function moduleTrans($string)
    {
        if (version_compare(_PS_VERSION_, '1.7.0.0', '>=')) {
            return $this->trans($string, [], 'Modules.Aggoogleshopping.Admin');
        }

        return $this->l($string);
    }

    public function install(): bool
    {
        return parent::install()
            && Configuration::updateValue(self::CONFIG_SKU_FIELD, 'reference')
            && Configuration::updateValue(self::CONFIG_FEED_TOKEN, $this->generateToken())
            && Configuration::updateValue(self::CONFIG_LAST_GENERATED, '')
            && $this->installDatabase()
            && $this->installAdminTabs()
            && $this->ensureFeedDirectory();
    }

    public function uninstall(): bool
    {
        $feedPath = $this->getFeedFilePath();
        if (is_file($feedPath)) {
            @unlink($feedPath);
        }

        return Configuration::deleteByName(self::CONFIG_SKU_FIELD)
            && Configuration::deleteByName(self::CONFIG_FEED_TOKEN)
            && Configuration::deleteByName(self::CONFIG_LAST_GENERATED)
            && Configuration::deleteByName('AGGOOGLESHOPPING_CRON_HOUR')
            && $this->uninstallDatabase()
            && $this->uninstallAdminTabs()
            && parent::uninstall();
    }

    public function getContent(): string
    {
        $output = '';
        $this->cleanupLegacyCronJobIntegration();

        if (isset($this->context->controller)) {
            $this->context->controller->addCSS($this->_path . 'views/css/module_config.css');
        }

        if (Tools::isSubmit('submitAgGoogleShoppingConfig')) {
            $skuField = trim((string) Tools::getValue(self::CONFIG_SKU_FIELD));

            Configuration::updateValue(self::CONFIG_SKU_FIELD, $skuField !== '' ? $skuField : 'reference');

            $output .= $this->displayConfirmation($this->moduleTrans('Configurações salvas.'));
        }

        if (Tools::isSubmit('submitAgGoogleShoppingRegenerate')) {
            try {
                $count = $this->regenerateFeed();
                $output .= $this->displayConfirmation(sprintf($this->moduleTrans('Feed regenerado com %d produtos.'), $count));
            } catch (Exception $e) {
                $output .= $this->displayError($this->moduleTrans('Erro ao regenerar feed: ') . $e->getMessage());
            }
        }

        if (Tools::isSubmit('submitAgGoogleShoppingRotateToken')) {
            Configuration::updateValue(self::CONFIG_FEED_TOKEN, $this->generateToken());
            $output .= $this->displayConfirmation($this->moduleTrans('Token rotacionado. Atualize a URL do feed no Google Merchant Center e a URL de atualização agendada (CRON).'));
        }

        return $output . $this->renderSetupGuide() . $this->renderConfigForm() . $this->renderCategoryMappingLink() . $this->renderFeedInfo();
    }

    public function validateFeedToken(?string $token): bool
    {
        $expected = (string) Configuration::get(self::CONFIG_FEED_TOKEN);
        if ($expected === '') {
            $this->ensureFeedToken();
            $expected = (string) Configuration::get(self::CONFIG_FEED_TOKEN);
        }
        if ($expected === '' || $token === null || $token === '') {
            return false;
        }

        return hash_equals($expected, $token);
    }

    public function getFeedFilePath(): string
    {
        return $this->getLocalPath() . 'feeds/feed.xml';
    }

    public function getPublicFeedUrl(): string
    {
        $this->ensureFrontContext();
        $this->ensureFeedToken();

        return $this->context->link->getModuleLink(
            $this->name,
            'feed',
            ['token' => (string) Configuration::get(self::CONFIG_FEED_TOKEN)],
            true
        );
    }

    public function getPublicCronUrl(): string
    {
        $this->ensureFrontContext();
        $this->ensureFeedToken();

        return $this->context->link->getModuleLink(
            $this->name,
            'cron',
            ['token' => (string) Configuration::get(self::CONFIG_FEED_TOKEN)],
            true
        );
    }

    /**
     * @throws Exception
     */
    public function regenerateFeed(): int
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        $this->ensureFeedDirectory();
        $this->ensureFrontContext();

        $builder = new AgGoogleShoppingFeedBuilder(
            (string) Configuration::get(self::CONFIG_SKU_FIELD),
            $this->context
        );

        $xml = $builder->build();
        $path = $this->getFeedFilePath();

        if (@file_put_contents($path, $xml) === false) {
            throw new Exception('Cannot write feed file.');
        }

        Configuration::updateValue(self::CONFIG_LAST_GENERATED, date('Y-m-d H:i:s'));

        return substr_count($xml, '<item>');
    }

    public function runCronRegeneration(): void
    {
        if (!headers_sent()) {
            header('Content-Type: text/plain; charset=utf-8');
            header('Cache-Control: no-store');
        }

        try {
            $count = $this->regenerateFeed();
            echo 'OK products=' . $count . ' generated=' . date('Y-m-d H:i:s');
        } catch (Exception $e) {
            http_response_code(500);
            echo 'ERROR ' . $e->getMessage();
        }

        exit;
    }

    public function serveFeed(): void
    {
        $path = $this->getFeedFilePath();
        if (!is_file($path)) {
            try {
                $this->regenerateFeed();
            } catch (Exception $e) {
                http_response_code(503);
                header('Content-Type: text/plain; charset=utf-8');
                echo 'Feed unavailable';
                exit;
            }
        }

        if (!headers_sent()) {
            header('Content-Type: application/xml; charset=utf-8');
            header('Cache-Control: public, max-age=3600');
        }

        readfile($path);
        exit;
    }

    private function ensureFeedDirectory(): bool
    {
        $dir = $this->getLocalPath() . 'feeds';
        if (is_dir($dir)) {
            return true;
        }

        return @mkdir($dir, 0755, true) || is_dir($dir);
    }

    private function cleanupLegacyCronJobIntegration(): void
    {
        if (!Configuration::hasKey('AGGOOGLESHOPPING_CRON_HOUR')) {
            return;
        }

        $this->unregisterHook('actionCronJob');
        Configuration::deleteByName('AGGOOGLESHOPPING_CRON_HOUR');
    }

    private function ensureFeedToken(): void
    {
        if ((string) Configuration::get(self::CONFIG_FEED_TOKEN) !== '') {
            return;
        }

        Configuration::updateValue(self::CONFIG_FEED_TOKEN, $this->generateToken());
    }

    private function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function ensureFrontContext(): void
    {
        if (!isset($this->context->shop) || !(int) $this->context->shop->id) {
            $this->context->shop = new Shop((int) Configuration::get('PS_SHOP_DEFAULT'));
        }

        if (!isset($this->context->language) || !(int) $this->context->language->id) {
            $this->context->language = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        }

        if (!isset($this->context->currency) || !(int) $this->context->currency->id) {
            $this->context->currency = new Currency((int) Configuration::get('PS_CURRENCY_DEFAULT'));
        }

        if (!isset($this->context->cart) || !(int) $this->context->cart->id) {
            $this->context->cart = new Cart();
        }

        $this->context->link = new Link();
    }

    private function renderConfigForm(): string
    {
        $inputs = [
            [
                'type' => 'select',
                'label' => $this->moduleTrans('Campo ID/SKU no feed'),
                'name' => self::CONFIG_SKU_FIELD,
                'desc' => $this->moduleTrans('Deve coincidir com o identificador de produto (ID) configurado no Google Merchant Center.'),
                'options' => [
                    'query' => [
                        ['id' => 'reference', 'name' => $this->moduleTrans('Referência (reference)')],
                        ['id' => 'ean13', 'name' => $this->moduleTrans('EAN-13')],
                        ['id' => 'upc', 'name' => $this->moduleTrans('UPC')],
                        ['id' => 'id', 'name' => $this->moduleTrans('ID interno PrestaShop')],
                    ],
                    'id' => 'id',
                    'name' => 'name',
                ],
            ],
        ];

        if (version_compare(_PS_VERSION_, '1.7.0.0', '>=')) {
            $fieldsForm = [
                'form' => [
                    'legend' => [
                        'title' => $this->moduleTrans('Configuração'),
                        'icon' => 'icon-cogs',
                    ],
                    'input' => $inputs,
                    'submit' => [
                        'title' => $this->moduleTrans('Salvar'),
                        'name' => 'submitAgGoogleShoppingConfig',
                    ],
                ],
            ];
        } else {
            $fieldsForm = [
                'legend' => [
                    'title' => $this->moduleTrans('Configuração'),
                    'icon' => 'icon-cogs',
                ],
                'input' => $inputs,
                'submit' => [
                    'title' => $this->moduleTrans('Salvar'),
                    'name' => 'submitAgGoogleShoppingConfig',
                ],
            ];
        }

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->default_form_language = (int) $this->context->language->id;
        $helper->allow_employee_form_lang = (int) Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitAgGoogleShoppingConfig';
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->fields_value = [
            self::CONFIG_SKU_FIELD => Configuration::get(self::CONFIG_SKU_FIELD) ?: 'reference',
        ];

        return $helper->generateForm([$fieldsForm]);
    }

    public function installDatabase(): bool
    {
        $sqlFile = $this->getLocalPath() . 'sql/install.php';
        if (!is_file($sqlFile)) {
            return false;
        }

        $sqls = include $sqlFile;
        if (!is_array($sqls)) {
            return false;
        }

        foreach ($sqls as $sql) {
            $sql = str_replace('_DB_PREFIX_', _DB_PREFIX_, $sql);
            if (!Db::getInstance()->execute(trim($sql))) {
                return false;
            }
        }

        return true;
    }

    public function uninstallDatabase(): bool
    {
        $sqlFile = $this->getLocalPath() . 'sql/uninstall.php';
        if (!is_file($sqlFile)) {
            return true;
        }

        $sqls = include $sqlFile;
        if (!is_array($sqls)) {
            return true;
        }

        foreach ($sqls as $sql) {
            $sql = str_replace('_DB_PREFIX_', _DB_PREFIX_, $sql);
            if (!Db::getInstance()->execute(trim($sql))) {
                return false;
            }
        }

        return true;
    }

    public function installAdminTabs(): bool
    {
        if (Tab::getIdFromClassName('AdminAgGoogleShoppingCategoryMapping')) {
            return true;
        }

        $parent = Tab::getIdFromClassName('AdminCatalog');
        if (!$parent) {
            return false;
        }

        $tab = new Tab();
        $tab->class_name = 'AdminAgGoogleShoppingCategoryMapping';
        $tab->module = $this->name;
        $tab->id_parent = (int) $parent;
        $tab->active = 1;
        foreach (Language::getLanguages(false) as $lang) {
            $tab->name[(int) $lang['id_lang']] = 'Google Shopping - Categorias';
        }

        return (bool) $tab->add();
    }

    public function uninstallAdminTabs(): bool
    {
        $idTab = (int) Tab::getIdFromClassName('AdminAgGoogleShoppingCategoryMapping');
        if ($idTab <= 0) {
            return true;
        }

        $tab = new Tab($idTab);

        return (bool) $tab->delete();
    }

    private function renderSetupGuide(): string
    {
        if (version_compare(_PS_VERSION_, '1.7.0.0', '>=')) {
            return $this->fetch('module:aggoogleshopping/views/templates/admin/setup_guide.tpl');
        }

        return $this->display(__FILE__, 'views/templates/admin/setup_guide.tpl');
    }

    private function renderCategoryMappingLink(): string
    {
        $url = $this->context->link->getAdminLink('AdminAgGoogleShoppingCategoryMapping');
        $this->context->smarty->assign([
            'aggs_category_mapping_url' => $url,
        ]);

        return $this->display(__FILE__, 'views/templates/admin/category_mapping_link.tpl');
    }

    private function renderFeedInfo(): string
    {
        $feedUrl = $this->getPublicFeedUrl();
        $cronUrl = $this->getPublicCronUrl();
        $lastGenerated = (string) Configuration::get(self::CONFIG_LAST_GENERATED);
        $token = (string) Configuration::get(self::CONFIG_FEED_TOKEN);

        $this->context->smarty->assign([
            'aggs_feed_url' => $feedUrl,
            'aggs_cron_url' => $cronUrl,
            'aggs_last_generated' => $lastGenerated !== '' ? $lastGenerated : $this->moduleTrans('Nunca'),
            'aggs_token_preview' => substr($token, 0, 8) . '…',
        ]);

        if (version_compare(_PS_VERSION_, '1.7.0.0', '>=')) {
            return $this->fetch('module:aggoogleshopping/views/templates/admin/feed_info.tpl');
        }

        return $this->display(__FILE__, 'views/templates/admin/feed_info.tpl');
    }
}
