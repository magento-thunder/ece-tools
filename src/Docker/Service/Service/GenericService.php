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
 * Generic Service
 */
class GenericService implements ServiceInterface
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
                'Alpine Docker image version $version does not supported',
                $version
            ));
        }
        $this->version = $version;
    }

    /**
     * @inheritdoc
     */
    public function getComposeConfig(): array
    {
        return [
            'cron' => [
                'image' => sprintf('alpine:%s', $this->version),
            ]
        ];
    }

    /**
     * Return supported versions
     *
     * @return array
     */
    private function getSupportedVersions()
    {
        return ['latest'];
    }
}