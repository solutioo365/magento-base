<?php

declare(strict_types=1);

namespace Solutioo\Base\Model;

class Config extends ConfigProviderAbstract
{
    protected $pathPrefix = 'solutioo_base/';

    public function isUsageStatsEnabled(): bool
    {
        return $this->isSetGlobalFlag('general/usage_stats');
    }

    public function isAppsInfoEnabled(): bool
    {
        return $this->isSetGlobalFlag('apps_info/enabled');
    }
}
