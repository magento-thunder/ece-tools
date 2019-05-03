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
 * Cron Service
 */
class CronService implements ServiceInterface
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
     * CronService constructor.
     * @param string $version
     * @param array $extendedConfig
     * @throws ConfigurationMismatchException
     */
    public function __construct(string $version, array $extendedConfig = [])
    {
        if (!in_array($version, $this->getSupportedVersions(), true)) {
            throw new ConfigurationMismatchException(sprintf(
                'Php version $version does not supported',
                $version
            ));
        }
        $this->version = $version;
        $this->extendedConfig = $extendedConfig;
    }

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        return array_replace_recursive(
            ['image' => sprintf('magento/magento-cloud-docker-php:%s-cli', $this->version)],
            $this->extendedConfig
        );
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
    private function getSupportedVersions()
    {
        return ['7.0', '7.1', '7.2'];
    }

}