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
     * @param string $version
     * @throws ConfigurationMismatchException
     */
    public function __construct(string $version)
    {
        if (!in_array($version, $this->getSupportedVersions(), true)) {
            throw new ConfigurationMismatchException(
                "Redis version $version does not supported"
            );
        }
        $this->version = $version;
    }

    public function getComposeConfig(): array
    {
        return [
            'redis' => [
                'image' => sprintf('redis:%s', $this->version),
                'volumes' => [
                    '/data',
                ],
                'ports' => [6379],
            ]
        ];
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