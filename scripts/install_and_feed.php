<?php

require '/var/www/html/public_html/config/config.inc.php';

$context = Context::getContext();
if (!isset($context->shop) || !(int) $context->shop->id) {
    $context->shop = new Shop((int) Configuration::get('PS_SHOP_DEFAULT'));
}
if (!isset($context->language) || !(int) $context->language->id) {
    $context->language = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
}
if (!isset($context->currency) || !(int) $context->currency->id) {
    $context->currency = new Currency((int) Configuration::get('PS_CURRENCY_DEFAULT'));
}
$context->cart = new Cart();
$context->link = new Link();

$m = Module::getInstanceByName('aggoogleshopping');
if (!$m) {
    fwrite(STDERR, "module not found\n");
    exit(1);
}

if (!Module::isInstalled('aggoogleshopping')) {
    $ok = $m->install();
    echo $ok ? "installed\n" : 'install failed: ' . json_encode($m->getErrors()) . "\n";
    if (!$ok) {
        exit(1);
    }
} else {
    echo "already installed\n";
}

try {
    $count = $m->regenerateFeed();
    echo "products: {$count}\n";
} catch (Exception $e) {
    fwrite(STDERR, 'feed error: ' . $e->getMessage() . "\n");
    exit(1);
}

echo "FEED_URL=" . $m->getPublicFeedUrl() . "\n";
