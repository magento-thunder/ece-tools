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
 * Elastic Search service
 */
class ElasticSearchService implements ServiceInterface
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
     * ElasticSearchService constructor.
     * @param string $version
     * @param array $extendedConfig
     * @throws ConfigurationMismatchException
     */
    public function __construct(string $version, array $extendedConfig = [])
    {
        if (!in_array($version, $this->getSupportedVersions(), true)) {
            throw new ConfigurationMismatchException(sprintf(
                'Elastic Search version %s does not supported',
                $version
            ));
        }
        $this->version = $version;
        $this->extendedConfig = $extendedConfig;
    }

    public function getConfig(): array
    {
        return array_replace_recursive(
            ['image' => sprintf('magento/magento-cloud-docker-elasticsearch:%s', $this->version)],
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
        return ['1.7', '2.4', '5.2', '6.5'];
    }

}
