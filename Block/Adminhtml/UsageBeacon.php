<?php

declare(strict_types=1);

namespace Solutioo\Base\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Store\Model\StoreManagerInterface;
use Solutioo\Base\Model\Config;

class UsageBeacon extends Template
{
    private const PING_BASE = 'https://www.solutioo.de/module-ping/';
    private const MODULE_VERSION = '1.0.0';

    /**
     * Local/demo helpers that must not appear in the public installations list.
     *
     * @var list<string>
     */
    private const EXCLUDED_MODULES = [
        'themeswitcher',
    ];

    public function __construct(
        Context $context,
        private readonly Config $config,
        private readonly StoreManagerInterface $storeManager,
        private readonly ProductMetadataInterface $productMetadata,
        private readonly ModuleListInterface $moduleList,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function isEnabled(): bool
    {
        return $this->config->isUsageStatsEnabled();
    }

    public function getBeaconUrl(): string
    {
        try {
            $host = (string) parse_url(
                (string) $this->storeManager->getStore()->getBaseUrl(),
                PHP_URL_HOST
            );
        } catch (\Throwable) {
            $host = '';
        }

        $host = strtolower(preg_replace('/[^a-z0-9.-]/i', '', $host) ?? '');
        $mods = [];
        foreach (array_keys($this->moduleList->getAll()) as $name) {
            if (!str_starts_with((string) $name, 'Solutioo_')) {
                continue;
            }
            $short = strtolower(substr((string) $name, strlen('Solutioo_')));
            if (in_array($short, self::EXCLUDED_MODULES, true)) {
                continue;
            }
            $mods[] = $short;
        }
        sort($mods);

        return self::PING_BASE . '?' . http_build_query([
            'm' => 'base',
            'v' => self::MODULE_VERSION,
            'h' => $host,
            'mv' => $this->productMetadata->getVersion(),
            'e' => $this->productMetadata->getEdition(),
            'mods' => implode(',', $mods),
        ]);
    }
}
