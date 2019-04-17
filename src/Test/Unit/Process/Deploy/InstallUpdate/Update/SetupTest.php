<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\Update;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\Update\Setup;
use Magento\MagentoCloud\Process\ProcessException;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Filesystem\FileList;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class SetupTest extends TestCase
{
    /**
     * @var Setup
     */
    private $process;

    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ShellInterface|MockObject
     */
    private $shellMock;

    /**
     * @var FlagManager|MockObject
     */
    private $flagManagerMock;

    /**
     * @var FileList|MockObject
     */
    private $fileListMock;

    /**
     * @var DirectoryList|MockObject
     */
    private $directoryListMock;

    /**
     * @var DeployInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->shellMock = $this->getMockForAbstractClass(ShellInterface::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->fileListMock = $this->createMock(FileList::class);
        $this->flagManagerMock = $this->createMock(FlagManager::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);

        $this->process = new Setup(
            $this->loggerMock,
            $this->environmentMock,
            $this->shellMock,
            $this->directoryListMock,
            $this->fileListMock,
            $this->flagManagerMock,
            $this->stageConfigMock
        );
    }

    /**
     * @throws ProcessException
     */
    public function testExecuteByDefault()
    {
        $installUpgradeLog = '/tmp/log.log';

        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileListMock->expects($this->once())
            ->method('getInstallUpgradeLog')
            ->willReturn($installUpgradeLog);
        $this->stageConfigMock->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [DeployInterface::VAR_RUN_UPGRADE_DEPENDING_ON_DB_STATUS],
                [DeployInterface::VAR_VERBOSE_COMMANDS]
            )
            ->willReturnOnConsecutiveCalls(false, '-v');
        $this->flagManagerMock->expects($this->exactly(2))
            ->method('delete')
            ->with(FlagManager::FLAG_REGENERATE);
        $this->shellMock->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                ['echo \'Updating time: \'$(date) | tee -a ' . $installUpgradeLog],
                ['/bin/bash -c "set -o pipefail; php ./bin/magento setup:upgrade '
                    . '--keep-generated --ansi --no-interaction -v | tee -a '
                    . $installUpgradeLog . '"']
            );
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Running setup upgrade.');

        $this->process->execute();
    }

    public function testExecuteRunUpgradeDependingOnStatus()
    {
        $installUpgradeLog = '/tmp/log.log';

        $this->directoryListMock->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->fileListMock->expects($this->once())
            ->method('getInstallUpgradeLog')
            ->willReturn($installUpgradeLog);
        $this->stageConfigMock->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [DeployInterface::VAR_RUN_UPGRADE_DEPENDING_ON_DB_STATUS],
                [DeployInterface::VAR_VERBOSE_COMMANDS]
            )
            ->willReturnOnConsecutiveCalls(true, '-v');
        $this->loggerMock->expects($this->exactly(2))
            ->method('notice')
            ->withConsecutive(
                ['Run upgrade based on the database state'],
                [<<<EOD
The module code base doesn't match the DB schema and data.
Magento_SomeModule     schema:       2.0.0  ->  2.0.1
Magento_SomeModule       data:       2.0.0  ->  2.0.1
Run 'setup:upgrade' to update your DB schema and data.
EOD
                ]
            );
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Checks the database state'],
                ['Running setup upgrade.']
            );
        $this->flagManagerMock->expects($this->exactly(2))
            ->method('delete')
            ->with(FlagManager::FLAG_REGENERATE);
        $this->shellMock->expects($this->exactly(3))
            ->method('execute')
            ->withConsecutive(
                ['php bin/magento setup:db:status --ansi --no-interaction;echo $?'],
                ['echo \'Updating time: \'$(date) | tee -a ' . $installUpgradeLog],
                ['/bin/bash -c "set -o pipefail; php ./bin/magento setup:upgrade '
                    . '--keep-generated --ansi --no-interaction -v | tee -a '
                    . $installUpgradeLog . '"']
            )
            ->willReturnOnConsecutiveCalls(
                [
                    'The module code base doesn\'t match the DB schema and data.',
                    'Magento_SomeModule     schema:       2.0.0  ->  2.0.1',
                    'Magento_SomeModule       data:       2.0.0  ->  2.0.1',
                    'Run \'setup:upgrade\' to update your DB schema and data.',
                    2,
                ],
                [],
                []
            );

        $this->process->execute();
    }

    public function testExecuteNotRunUpgradeDependingOnStatus()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_RUN_UPGRADE_DEPENDING_ON_DB_STATUS)
            ->willReturn(true);
        $this->loggerMock->expects($this->exactly(3))
            ->method('notice')
            ->withConsecutive(
                ['Run upgrade based on the database state'],
                ['All modules are up to date.'],
                ['Skip run the upgrade process']
            );
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Checks the database state');
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('php bin/magento setup:db:status --ansi --no-interaction;echo $?')
            ->willReturn([
                'All modules are up to date.',
                0,
            ]);

        $this->process->execute();
    }

    /**
     * @expectedException \Magento\MagentoCloud\Process\ProcessException
     * @expectedExceptionMessage Error during command execution
     */
    public function testExecuteWithException()
    {
        $this->flagManagerMock->expects($this->once())
            ->method('delete')
            ->with(FlagManager::FLAG_REGENERATE);
        $this->shellMock->expects($this->at(0))
            ->method('execute')
            ->willThrowException(new \RuntimeException('Error during command execution'));

        $this->process->execute();
    }

    /**
     * @expectedException \Magento\MagentoCloud\Process\ProcessException
     * @expectedExceptionMessage Command php bin/magento setup:db:status --ansi --no-interaction;echo $? returned code 1
     */
    public function testExecuteDbStatusReturnOne()
    {
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_RUN_UPGRADE_DEPENDING_ON_DB_STATUS)
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Run upgrade based on the database state');
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Checks the database state');
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('php bin/magento setup:db:status --ansi --no-interaction;echo $?')
            ->willReturn([
                'Some message',
                1,
            ]);
        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with('Some message');

        $this->process->execute();
    }
}
