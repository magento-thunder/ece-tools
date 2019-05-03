<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Docker\Service\Service;

use Magento\MagentoCloud\Docker\ConfigurationMismatchException;

/**
 * RabbitMq Service
 */
class RabbitMqService
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
                'RsbbitMQ version %s does not supported',
                $version
            ));
        }
        $this->version = $version;
    }

    public function getComposeConfig(): array
    {
        return [
            'image' => sprintf('rabbitmq:%s', $this->version),
        ];
    }

    /**
     * Return supported versions
     *
     * @return array
     */
    private function getSupportedVersions()
    {
        return ['3.5', '3.7'];
    }
}
