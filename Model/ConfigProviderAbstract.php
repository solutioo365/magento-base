<?php

declare(strict_types=1);

namespace Solutioo\Base\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ScopeInterface;
use Magento\Store\Model\ScopeInterface as StoreScopeInterface;

abstract class ConfigProviderAbstract
{
    /** @var string Section path prefix, e.g. "my_section/" */
    protected $pathPrefix = '/';

    /** @var array<string, array<string, mixed>> */
    protected $data = [];

    public function __construct(
        protected ScopeConfigInterface $scopeConfig
    ) {
        if ($this->pathPrefix === '/') {
            throw new \LogicException('$pathPrefix must be set in the concrete config provider.');
        }
    }

    public function clean(): void
    {
        $this->data = [];
    }

    /**
     * @param int|string|ScopeInterface|null $storeId
     */
    protected function getValue(
        string $path,
        $storeId = null,
        string $scope = StoreScopeInterface::SCOPE_STORE
    ): mixed {
        if ($storeId instanceof ScopeInterface) {
            $storeId = $storeId->getId();
        }

        $scopeKey = ($storeId === null ? 'current_' : (string) $storeId) . $scope;
        if (!isset($this->data[$path][$scopeKey])) {
            $this->data[$path][$scopeKey] = $this->scopeConfig->getValue(
                $this->pathPrefix . $path,
                $scope,
                $storeId
            );
        }

        return $this->data[$path][$scopeKey];
    }

    protected function getGlobalValue(string $path): mixed
    {
        return $this->getValue($path, null, ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
    }

    /**
     * @param int|string|ScopeInterface|null $storeId
     */
    protected function isSetFlag(
        string $path,
        $storeId = null,
        string $scope = StoreScopeInterface::SCOPE_STORE
    ): bool {
        return (bool) $this->getValue($path, $storeId, $scope);
    }

    protected function isSetGlobalFlag(string $path): bool
    {
        return $this->isSetFlag($path, null, ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
    }
}
