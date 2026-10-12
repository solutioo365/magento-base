<?php

declare(strict_types=1);

// Usage: php tests/adminhtml-csp.php MAGENTO_ROOT [BASE_MODULE_ROOT]
// Reads Magento configuration and renders the beacon without dispatching an HTTP request.
$root = rtrim($argv[1] ?? '', '/');
$moduleRoot = rtrim($argv[2] ?? dirname(__DIR__), '/');
$whitelistFile = $moduleRoot . '/etc/csp_whitelist.xml';
$templateFile = $moduleRoot . '/view/adminhtml/templates/usage-beacon.phtml';
if (!is_file($root . '/app/bootstrap.php') || !is_file($whitelistFile) || !is_file($templateFile)) {
    fwrite(STDERR, "Usage: php tests/adminhtml-csp.php MAGENTO_ROOT [BASE_MODULE_ROOT]\n");
    exit(2);
}

require $root . '/app/bootstrap.php';
$objectManager = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER)->getObjectManager();
$objectManager->get(\Magento\Framework\App\State::class)->setAreaCode('adminhtml');
$objectManager->configure(
    $objectManager->get(\Magento\Framework\ObjectManager\ConfigLoaderInterface::class)->load('adminhtml')
);
$objectManager->get(\Magento\Framework\App\Request\Http::class)
    ->setRouteName('sales')->setControllerName('order_create')->setActionName('index');

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
$document->load($whitelistFile);
check(
    $document->schemaValidate($root . '/vendor/magento/module-csp/etc/csp_whitelist.xsd'),
    'Base CSP whitelist validates against the Magento schema'
);

$fileResolver = new class($whitelistFile) implements \Magento\Framework\Config\FileResolverInterface {
    public function __construct(private string $file) {}
    public function get($filename, $scope)
    {
        return [$this->file => file_get_contents($this->file)];
    }
};
$reader = $objectManager->create(\Magento\Csp\Model\Collector\CspWhitelistXml\Reader::class, [
    'fileResolver' => $fileResolver,
]);
$configData = new \Magento\Framework\DataObject($reader->read('global'));
$configReader = new class($configData) implements \Magento\Framework\Config\DataInterface {
    public function __construct(private \Magento\Framework\DataObject $data) {}
    public function merge(array $config) { $this->data->addData($config); }
    public function get($key, $default = null)
    {
        return $key === null ? $this->data->getData() : ($this->data->getData($key) ?? $default);
    }
};
$modulePolicies = $objectManager->create(\Magento\Csp\Model\Collector\CspWhitelistXmlCollector::class, [
    'configReader' => $configReader,
])->collect();
check(
    count($modulePolicies) === 1 && $modulePolicies[0]->getId() === 'img-src',
    'Base only adds an image source and does not change script or style policies'
);
$imagePolicy = null;
foreach ($objectManager->get(\Magento\Csp\Api\PolicyCollectorInterface::class)->collect() as $policy) {
    if ($policy->getId() === 'img-src') {
        $imagePolicy = $policy;
        break;
    }
}
if ($imagePolicy === null) {
    throw new RuntimeException('Magento did not collect an img-src policy.');
}
$registeredModule = (new \Magento\Framework\Component\ComponentRegistrar())->getPath(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'Solutioo_Base'
);
if ($registeredModule !== null && realpath($moduleRoot) === realpath($registeredModule)) {
    check(
        in_array('https://www.solutioo.de', $imagePolicy->getHostSources(), true),
        'Installed Base whitelist is collected by Magento without a test-only merge'
    );
}
foreach ($modulePolicies as $policy) {
    if ($policy->getId() === 'img-src') {
        $imagePolicy = $objectManager->get(\Magento\Csp\Model\Collector\FetchPolicyMerger::class)
            ->merge($imagePolicy, $policy);
    }
}
check(
    in_array('https://www.solutioo.de', $imagePolicy->getHostSources(), true),
    'Magento merges the HTTPS image source into img-src'
);

$engine = $objectManager->get(\Magento\Framework\View\TemplateEngine\Php::class);
foreach ([true, false] as $enabled) {
    $config = new class($enabled) extends \Solutioo\Base\Model\Config {
        public function __construct(private bool $enabled) {}
        public function isUsageStatsEnabled(): bool { return $this->enabled; }
    };
    $block = $objectManager->create(\Solutioo\Base\Block\Adminhtml\UsageBeacon::class, ['config' => $config]);
    $html = $engine->render($block, $templateFile);
    if (!$enabled) {
        check(trim($html) === '', 'Disabled setting renders no image or request URL');
        continue;
    }

    $document = new DOMDocument();
    $document->loadHTML($html);
    $images = $document->getElementsByTagName('img');
    check($images->length === 1, 'Enabled setting renders exactly one image');
    $image = $images->item(0);
    if (!$image instanceof DOMElement) {
        throw new RuntimeException('The enabled beacon did not render an image.');
    }
    check($image->getAttribute('referrerpolicy') === 'no-referrer', 'Image suppresses the Admin Referer');
    check(!$image->hasAttribute('style'), 'Image does not need an inline style');
    check($document->getElementsByTagName('script')->length === 0, 'Image does not need an inline script');
    check($image->getAttribute('src') === $block->getBeaconUrl(), 'Template preserves and escapes the image URL');
    $url = parse_url($image->getAttribute('src'));
    check(
        ($url['scheme'] ?? '') === 'https' && ($url['host'] ?? '') === 'www.solutioo.de'
            && ($url['path'] ?? '') === '/module-ping/',
        'Image keeps the original HTTPS endpoint'
    );
    parse_str($url['query'] ?? '', $parameters);
    $keys = array_keys($parameters);
    sort($keys);
    check(
        $keys === ['e', 'h', 'm', 'mods', 'mv', 'v'] && $parameters['m'] === 'base',
        'Image URL retains its existing parameters'
    );
}

echo 'Failures: ' . $failures . PHP_EOL;
exit($failures === 0 ? 0 : 1);
