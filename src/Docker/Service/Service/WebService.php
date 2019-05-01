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
 * Web Service
 */
class WebService implements ServiceInterface
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
                'Nginx version $version does not supported',
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
            'image' => sprintf('magento/magento-cloud-docker-nginx:%s', $this->version),
            'depends_on' => ['fpm'],
            'extends' => 'generic',
            'volumes' => [
                'magento:/app:ro',
                'magento-vendor:/app/vendor:ro',
                'magento-generated:/app/generated:ro',
                'magento-setup:/app/setup:ro',
                'magento-var:/app/var:rw',
                'magento-etc:/app/app/etc:rw',
                'magento-static:/app/pub/static:rw',
                'magento-media:/app/pub/media:rw',
            ],
        ];
    }

    /**
     * Return supported versions
     *
     * @return array
     */
    private function getSupportedVersions()
    {
        return ['1.9', 'latest'];
    }
}