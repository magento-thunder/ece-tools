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
 * MariaDb service
 */
class MariaDbService implements ServiceInterface
{
    /**
     * Current version
     *
     * @var string
     */
    private $version;

    /**
     * Extended Config
     *
     * @var array
     */
    private $extendedConfig;

    /**
     * MariaDbService constructor.
     * @param string $version
     * @param array $extendedConfig
     * @throws ConfigurationMismatchException
     */
    public function __construct(string $version, array $extendedConfig = [])
    {
        if (!in_array($version, $this->getSupportedVersions(), true)) {
            throw new ConfigurationMismatchException(
                "MariaDb version $version does not supported"
            );
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
            [
                'image' => sprintf('mariadb:%s', $this->version),
                'ports' => [3306],
                'volumes' => ['/var/lib/mysql'],
            ],
            $this->extendedConfig
        );
    }

    /**
     * @inheritdoc
     */
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
        return ['10.0', '10.1', '10.2'];
    }
}