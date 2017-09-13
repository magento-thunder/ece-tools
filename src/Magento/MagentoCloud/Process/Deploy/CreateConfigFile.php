<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Config\Deploy as DeployConfig;

/**
 * @inheritdoc
 */
class CreateConfigFile implements ProcessInterface
{
    /**
     * @var File
     */
    private $file;

    /**
     * @var DeployConfig
     */
    private $deployConfig;

    /**
     * @param DeployConfig $deployConfig
     * @param File $file
     */
    public function __construct(DeployConfig $deployConfig, File $file)
    {
        $this->deployConfig = $deployConfig;
        $this->file = $file;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $configFile = $this->deployConfig->getConfigFilePath();

        if ($this->file->isExists($configFile)) {
            return;
        }

        $updatedConfig = '<?php' . "\n" . 'return array();';
        $this->file->filePutContents($configFile, $updatedConfig);
    }
}