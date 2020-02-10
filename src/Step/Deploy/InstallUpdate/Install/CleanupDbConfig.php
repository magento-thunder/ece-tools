<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy\InstallUpdate\Install;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\Database\ResourceConfig;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Magento\MagentoCloud\Config\Database\DbConfig;
use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface as ConfigReader;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface as ConfigWriter;
use Psr\Log\LoggerInterface;

/**
 *
 */
class CleanupDbConfig implements StepInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ConfigWriter
     */
    private $configWriter;

    /**
     * @var ConfigReader
     */
    private $configReader;

    /**
     * @var DbConfig
     */
    private $dbConfig;

    /**
     * @param LoggerInterface $logger
     * @param ConfigWriter $configWriter
     * @param ConfigReader $configReader
     * @param DbConfig $dbConfig
     */
    public function __construct(
        LoggerInterface $logger,
        ConfigWriter $configWriter,
        ConfigReader $configReader,
        DbConfig $dbConfig
    )
    {
        $this->logger = $logger;
        $this->configWriter = $configWriter;
        $this->configReader = $configReader;
        $this->dbConfig = $dbConfig;
    }

    /**
     * @throws StepException
     */
    public function execute()
    {
        try {
            $this->logger->debug('CLEANUP DB CONFIG');
            $envDbConfig = $this->dbConfig->get();
            $mageConfig = $this->configReader->read();
            $mageDbConfig = $mageConfig['db'] ?? [];
            $mageSplitDbConnectionsConfig = array_intersect_key(
                $mageDbConfig['connection'] ?? [],
                array_flip(DbConfig::SPLIT_CONNECTIONS)
            );

            $envDbConnectionDefaultHost = $envDbConfig['connection']['default']['host'] ?? '';
            $mageDbConnectionDefaultHost = $mageDbConfig['connection']['default']['host'] ?? '';

            if (!empty($mageSplitDbConnectionsConfig)
                && ($envDbConnectionDefaultHost !== $mageDbConnectionDefaultHost)) {
                $this->logger->notice(
                    'Previous split DB connection will be lost as new custom main connection was set'
                );

                unset($mageConfig['install'], $mageConfig['db']);

                $this->configWriter->create($mageConfig);
            }
        } catch (GenericException $e) {
            throw new StepException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
