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
     * @param string $version
     * @throws ConfigurationMismatchException
     */
    public function __construct(string $version)
    {
        if (!in_array($version, $this->getSupportedVersions(), true)) {
            throw new ConfigurationMismatchException(sprintf(
                'Elastic Search version %s does not supported',
                $version
            ));
        }
        $this->version = $version;
    }

    public function getConfig(): array
    {
        return [
            'image' => sprintf('magento/magento-cloud-docker-elasticsearch:%s', $this->version),
        ];
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
