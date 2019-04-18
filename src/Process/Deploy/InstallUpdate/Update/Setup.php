<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\Update;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Process\ProcessException;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Filesystem\FileList;
use Psr\Log\LoggerInterface;

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
class Setup implements ProcessInterface
{
    /**
     * Exit code when application upgrade is required.
     */
    const EXIT_CODE_UPGRADE_REQUIRED = 2;
    const EXIT_CODE_UPGRADE_NOT_REQUIRED = 0;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var FlagManager
     */

    private $flagManager;

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @param LoggerInterface $logger
     * @param Environment $environment
     * @param ShellInterface $shell
     * @param DirectoryList $directoryList
     * @param FileList $fileList
     * @param FlagManager $flagManager
     * @param DeployInterface $stageConfig
     */
    public function __construct(
        LoggerInterface $logger,
        Environment $environment,
        ShellInterface $shell,
        DirectoryList $directoryList,
        FileList $fileList,
        FlagManager $flagManager,
        DeployInterface $stageConfig
    ) {
        $this->logger = $logger;
        $this->environment = $environment;
        $this->shell = $shell;
        $this->directoryList = $directoryList;
        $this->fileList = $fileList;
        $this->flagManager = $flagManager;
        $this->stageConfig = $stageConfig;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        try {
            $upgradeDependingOnDbStatus = $this->stageConfig->get(
                DeployInterface::VAR_RUN_UPGRADE_DEPENDING_ON_DB_STATUS
            );
            if (!$upgradeDependingOnDbStatus) {
                $this->doUpgrade();
                return;
            }
            $this->logger->notice('Run upgrade based on the database state');
            $this->logger->info('Checks the database state');
            $cmd = 'php bin/magento setup:db:status --no-ansi --no-interaction;echo $?';
            $output = $this->shell->execute($cmd);
            $exitCode = (int)array_pop($output);
            switch ($exitCode) {
                case self::EXIT_CODE_UPGRADE_NOT_REQUIRED:
                    $this->logger->notice(implode(PHP_EOL, $output));
                    $this->logger->notice('Skip run the upgrade process');
                    break;
                case self::EXIT_CODE_UPGRADE_REQUIRED:
                    $this->logger->notice(implode(PHP_EOL, $output));
                    $this->doUpgrade();
                    break;
                default:
                    $this->logger->critical(implode(PHP_EOL, $output));
                    throw new \RuntimeException("Command $cmd returned code $exitCode", $exitCode);
            }
        } catch (\RuntimeException $exception) {
            //Rollback required by database
            throw new ProcessException($exception->getMessage(), 6, $exception);
        }
    }

    /**
     * Runs Magento 2 upgrade
     */
    private function doUpgrade()
    {
        $this->flagManager->delete(FlagManager::FLAG_REGENERATE);
        $verbosityLevel = $this->stageConfig->get(DeployInterface::VAR_VERBOSE_COMMANDS);
        $installUpgradeLog = $this->fileList->getInstallUpgradeLog();
        $this->logger->info('Running setup upgrade.');
        $this->shell->execute('echo \'Updating time: \'$(date) | tee -a ' . $installUpgradeLog);
        $this->shell->execute(sprintf(
            '/bin/bash -c "set -o pipefail; %s | tee -a %s"',
            'php ./bin/magento setup:upgrade --keep-generated --ansi --no-interaction ' . $verbosityLevel,
            $installUpgradeLog
        ));
        $this->flagManager->delete(FlagManager::FLAG_REGENERATE);
    }
}
