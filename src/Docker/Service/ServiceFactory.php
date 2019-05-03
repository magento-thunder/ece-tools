<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Docker\Service;

use Magento\MagentoCloud\Docker\ConfigurationMismatchException;
use Magento\MagentoCloud\Docker\Service\Service\BuildService;
use Magento\MagentoCloud\Docker\Service\Service\CronService;
use Magento\MagentoCloud\Docker\Service\Service\DeployService;
use Magento\MagentoCloud\Docker\Service\Service\ElasticSearchService;
use Magento\MagentoCloud\Docker\Service\Service\AlpineService;
use Magento\MagentoCloud\Docker\Service\Service\PhpFpmService;
use Magento\MagentoCloud\Docker\Service\Service\MariaDbService;
use Magento\MagentoCloud\Docker\Service\Service\RabbitMqService;
use Magento\MagentoCloud\Docker\Service\Service\RedisService;
use Magento\MagentoCloud\Docker\Service\Service\NginxService;
use Magento\MagentoCloud\Docker\Service\Service\TlsService;
use Magento\MagentoCloud\Docker\Service\Service\VarnishService;

/**
 * Create instance of Docker service configuration.
 */
class ServiceFactory
{
    const SERVICE_MARIADB = 'mariadb';
    const SERVICE_REDIS = 'redis';
    const SERVICE_PHP_FPM = 'php-fpm';
    const SERVICE_BUILD = 'build';
    const SERVICE_DEPLOY = 'deploy';
    const SERVICE_NGINX = 'nginx';
    const SERVICE_VARNISH = 'varnish';
    const SERVICE_TLS = 'tls';
    const SERVICE_CRON = 'cron';
    const SERVICE_ELASTICSEARCH = 'elasticsearch';
    const SERVICE_RABBITMQ = 'rabbitmq';
    const SERVICE_GENERIC = 'generic';

    const CONFIG = [
        self::SERVICE_MARIADB => MariaDbService::class,
        self::SERVICE_REDIS => RedisService::class,
        self::SERVICE_PHP_FPM => PhpFpmService::class,
        self::SERVICE_BUILD => BuildService::class,
        self::SERVICE_DEPLOY => DeployService::class,
        self::SERVICE_NGINX => NginxService::class,
        self::SERVICE_VARNISH => VarnishService::class,
        self::SERVICE_TLS => TlsService::class,
        self::SERVICE_CRON => CronService::class,
        self::SERVICE_ELASTICSEARCH => ElasticSearchService::class,
        self::SERVICE_RABBITMQ => RabbitMqService::class,
        self::SERVICE_GENERIC => AlpineService::class,
    ];

    /**
     * @param string $name
     * @param string $version
     * @param array $extendedConfig
     * @return ServiceInterface
     * @throws ConfigurationMismatchException
     */
    public function create(string $name, string $version, array $extendedConfig = []): ServiceInterface
    {
        if (!array_key_exists($name, self::CONFIG)) {
            throw new ConfigurationMismatchException(sprintf(
                'Service "%s" is not supported',
                $name
            ));
        }

        $serviceClassName = self::CONFIG[$name];

        return new $serviceClassName($version, $extendedConfig);
    }
}
