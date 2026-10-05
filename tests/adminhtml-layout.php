<?php

declare(strict_types=1);

// Run the real Magento readers without dispatching requests or changing 2FA configuration.
// Usage: php tests/adminhtml-layout.php MAGENTO_ROOT [BASE_DEFAULT_XML]
$root = rtrim($argv[1] ?? '', '/');
$baseLayout = $argv[2] ?? dirname(__DIR__) . '/view/adminhtml/layout/default.xml';
if (!is_file($root . '/app/bootstrap.php') || !is_file($baseLayout)) {
    fwrite(STDERR, "Usage: php tests/adminhtml-layout.php MAGENTO_ROOT [BASE_DEFAULT_XML]\n");
    exit(2);
}

require $root . '/app/bootstrap.php';
$objectManager = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER)->getObjectManager();
$objectManager->get(\Magento\Framework\App\State::class)->setAreaCode('adminhtml');
$objectManager->configure(
    $objectManager->get(\Magento\Framework\ObjectManager\ConfigLoaderInterface::class)->load('adminhtml')
);

function interpretLayout(object $objectManager, array $files): object
{
    $xml = '<layout xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
    foreach ($files as $file) {
        $document = new DOMDocument();
        if (!$document->load($file)) {
            throw new RuntimeException('Cannot read layout: ' . $file);
        }
        foreach ($document->documentElement->childNodes as $child) {
            $xml .= $document->saveXML($child);
        }
    }
    $xml .= '</layout>';
    $context = $objectManager->create(\Magento\Framework\View\Layout\Reader\Context::class);
    $pool = $objectManager->create('commonRenderPool');
    $pool->interpret($context, new \Magento\Framework\View\Layout\Element($xml));
    return $context->getScheduledStructure();
}

$failures = 0;
function check(bool $condition, string $message): void
{
    global $failures;
    echo ($condition ? 'PASS ' : 'FAIL ') . $message . PHP_EOL;
    if (!$condition) {
        ++$failures;
    }
}

$document = new DOMDocument();
$document->load($baseLayout);
libxml_set_external_entity_loader([
    $objectManager->get(\Magento\Framework\Config\Dom\UrnResolver::class),
    'registerEntityLoader',
]);
check(
    $document->schemaValidate($root . '/vendor/magento/framework/View/Layout/etc/page_configuration.xsd'),
    'Base default.xml validates against Magento page_configuration.xsd'
);
libxml_set_external_entity_loader(null);

$theme = $root . '/vendor/magento/module-theme/view/adminhtml/page_layout/';
$backend = $root . '/vendor/magento/module-backend/view/adminhtml/layout/';
$tfa = $root . '/vendor/magento/module-two-factor-auth/view/adminhtml/layout/';
$cases = [
    'tfa_google_auth' => ['Magento\\TwoFactorAuth\\Block\\Provider\\Google\\Auth', 'tfa/provider/auth.phtml', 'tfa-auth'],
    'tfa_google_configure' => ['Magento\\TwoFactorAuth\\Block\\Provider\\Google\\Configure', 'tfa/provider/configure.phtml', 'tfa-configure'],
    'tfa_authy_auth' => ['Magento\\TwoFactorAuth\\Block\\Provider\\Authy\\Auth', 'tfa/provider/auth.phtml', 'tfa-auth'],
    'tfa_authy_configure' => ['Magento\\TwoFactorAuth\\Block\\Provider\\Authy\\Configure', 'tfa/provider/configure.phtml', 'tfa-configure'],
    'tfa_duo_auth' => ['Magento\\TwoFactorAuth\\Block\\Provider\\Duo\\Auth', 'tfa/provider/auth.phtml', 'tfa-auth'],
    'tfa_u2f_auth' => ['Magento\\TwoFactorAuth\\Block\\Provider\\U2fKey\\Auth', 'tfa/provider/auth.phtml', 'tfa-auth'],
    'tfa_u2f_configure' => ['Magento\\TwoFactorAuth\\Block\\Provider\\U2fKey\\Configure', 'tfa/provider/configure.phtml', 'tfa-configure'],
    'tfa_tfa_configure' => ['Magento\\TwoFactorAuth\\Block\\Configure', 'tfa/configure.phtml', null],
    'tfa_tfa_requestconfig' => ['Magento\\Backend\\Block\\Template', 'tfa/request_config.phtml', null],
];
foreach ($cases as $handle => [$class, $template, $component]) {
    $structure = interpretLayout($objectManager, [
        $theme . 'admin-login.xml',
        $backend . 'default.xml',
        $baseLayout,
        $backend . 'admin_login.xml',
        $tfa . 'tfa_screen.xml',
        $tfa . $handle . '.xml',
    ]);
    $data = $structure->getStructureElementData('content', []);
    check(($data['attributes']['class'] ?? '') === $class, $handle . ' retains its provider block class');
    check(
        ($data['attributes']['template'] ?? '') === 'Magento_TwoFactorAuth::' . $template,
        $handle . ' retains its form template'
    );
    if ($component !== null) {
        $arguments = json_encode($data['arguments']['jsLayout'] ?? []);
        check(str_contains($arguments, $component), $handle . ' retains its UI component arguments');
    }
    check(
        !isset($data['attributes']['htmlTag']),
        $handle . ' content block is not polluted with container attributes'
    );
}

foreach (['admin-1column.xml', 'admin-empty.xml'] as $pageLayout) {
    $structure = interpretLayout($objectManager, [$theme . $pageLayout, $backend . 'default.xml', $baseLayout]);
    $data = $structure->getStructureElementData('content', []);
    check(!isset($data['attributes']['class']), $pageLayout . ' keeps content as a container');
    foreach (['solutioo.base.menu.script', 'solutioo.base.usage.beacon'] as $name) {
        $element = $structure->getStructureElement($name);
        $parentIndex = \Magento\Framework\View\Layout\ScheduledStructure\Helper::SCHEDULED_STRUCTURE_INDEX_PARENT_NAME;
        check(($element[$parentIndex] ?? '') === 'js', $pageLayout . ' attaches ' . $name . ' to js');
        $block = $structure->getStructureElementData($name, []);
        check(!empty($block['attributes']['class']) && !empty($block['attributes']['template']), $name . ' retains its class and template');
    }
}

echo 'Failures: ' . $failures . PHP_EOL;
exit($failures === 0 ? 0 : 1);
