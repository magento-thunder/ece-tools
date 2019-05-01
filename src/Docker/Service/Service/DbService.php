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
 * Db service
 */
class DbService implements ServiceInterface
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
                "MariaDb version $version does not supported"
            );
        }
        $this->version = $version;
    }

    /**
     * @inheritdoc
     */
    public function getComposeConfig(): array
    {
        return [
            'db' => [
                'image' => sprintf('mariadb:%s', $this->version),
                'ports' => [3306],
                'volumes' => [
                    '/var/lib/mysql',
                    './docker/mysql/docker-entrypoint-initdb.d:/docker-entrypoint-initdb.d',
                ],
                'environment' => [
                    'MYSQL_ROOT_PASSWORD=magento2',
                    'MYSQL_DATABASE=magento2',
                    'MYSQL_USER=magento2',
                    'MYSQL_PASSWORD=magento2',
                ],
            ],
        ];
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