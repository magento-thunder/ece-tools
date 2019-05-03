<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Docker\Service\Service;

use Magento\MagentoCloud\Docker\ConfigurationMismatchException;
use Magento\MagentoCloud\Docker\Service\ServiceInterface;

/**
 * Redis Service
 */
class RedisService implements ServiceInterface
{
    /**
     * Current version
     * @var string $version
     */
    private $version;

    /**
     * Extended Config
     *
     * @var array
     */
    private $extendedConfig;

    /**
     * RedisService constructor.
     * @param string $version
     * @param array $extendedConfig
     * @throws ConfigurationMismatchException
     */
    public function __construct(string $version, array $extendedConfig = [])
    {
        if (!in_array($version, $this->getSupportedVersions(), true)) {
            throw new ConfigurationMismatchException(
                "Redis version $version does not supported"
            );
        }
        $this->version = $version;
        $this->extendedConfig = $extendedConfig;
    }

    public function getConfig(): array
    {
        return [
            'image' => sprintf('redis:%s', $this->version),
            'volumes' => ['/data'],
            'ports' => [6379],
        ];
    }

    public function getDepends(): array
    {
        return [];
    }

    /**
     * Return supported versions
     *
     * @return array
     */
    private function getSupportedVersions(): array
    {
        return ['3.0', '3.2', '4.0', '5.0'];
    }
}