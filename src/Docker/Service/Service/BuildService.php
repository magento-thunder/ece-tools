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
 * Build Service
 */
class BuildService implements ServiceInterface
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
                'PHP version $version does not supported',
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
            'build' => [
                'image' => sprintf('magento/magento-cloud-docker-php:%s-cli', $this->version),
                'hostname' => 'build.magento2.docker',
                'depends_on' => ['db', 'redis'],
                'extends' => 'generic',
                'volumes' => [
                    'magento:/app:rw',
                    'magento-vendor:/app/vendor:rw',
                    'magento-generated:/app/generated:rw',
                    'magento-setup:/app/setup:rw',
                    'magento-var:/app/var:rw',
                    'magento-etc:/app/app/etc:rw',
                    'magento-static:/app/pub/static:rw',
                    'magento-media:/app/pub/media:rw',
                    '~/.composer/cache:/root/.composer/cache',
                    './docker/mnt:/mnt',
                    './docker/tmp:/tmp',
                ]
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
        return ['7.0', '7.1', '7.2'];
    }

}