<?php

namespace Magento\MagentoCloud\Docker\Service;

use Composer\Package\Version\VersionParser;
use Composer\Semver\Constraint\Constraint;
use Magento\MagentoCloud\Docker\ConfigurationMismatchException;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Illuminate\Contracts\Config\Repository;
use PHP_CodeSniffer\Exceptions\RuntimeException;

class DockerfileGenerator
{
    const PHP_CLI_BUILD_CONTEXT = './docker/php-cli';
    const PHP_FPM_BUILD_CONTEXT = './docker/php-fpm';
    const DOCKERFILE = 'Dockerfile';

    const OS_DEPENDENCY = 'os_dependency';
    const INSTALL_COMMAND = 'install_command';

    /**
     * @var File
     */
    private $file;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var Config
     */
    private $serviceConfig;

    /**
     * @var VersionParser
     */
    private $versionParser;

    public function __construct(
        File $file,
        DirectoryList $directoryList,
        Config $serviceConfig,
        VersionParser $versionParser
    )
    {
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->serviceConfig = $serviceConfig;
        $this->versionParser = $versionParser;
    }

    /**
     * @param Repository $config
     * @throws \Magento\MagentoCloud\Docker\ConfigurationMismatchException
     * @throws \Magento\MagentoCloud\Filesystem\FileSystemException
     */
    public function generate(Repository $config)
    {
        foreach ($this->getConfig($config) as $context => $dockerfileConfig) {
            $contextPath = $this->directoryList->getMagentoRoot() . '/' . $context;
            $this->file->createDirectory($contextPath);
            $this->file->filePutContents(
                $contextPath . '/' . self::DOCKERFILE,
                $this->buildDockerfile($dockerfileConfig)
            );
        }
    }

    private function buildDockerfile(array $config): string
    {
        $result[] = 'FROM ' . $config['image'];
        if (isset($config['entrypoint'])) {
            $result[] = 'ENTRYPOINT ["' . $config['entrypoint'] . '"]';
        }

        if (!empty($config['extensions'])) {
            $result[] = 'RUN apt-get update -y';
        }
        $osDependens = [];
        $installString = [];
        foreach ($config['extensions'] as $extName => $extConfig) {
            if ($extConfig[self::OS_DEPENDENCY]) {
                $osDependens = array_replace($osDependens, $extConfig[self::OS_DEPENDENCY]);
            }
            if ($extConfig[self::INSTALL_COMMAND]) {
                $installString[] = 'RUN ' . $extConfig[self::INSTALL_COMMAND];
            }
        }
        if (!empty($osDependens)) {
            $result[] = 'RUN apt-get install -y ' . implode(' ', $osDependens);
        }
        if (!empty($installString)) {
            $result[] = implode(PHP_EOL, $installString);
        }
        return implode(PHP_EOL, $result);
    }

    /**
     * @param Repository $config
     * @return array
     * @throws \Magento\MagentoCloud\Docker\ConfigurationMismatchException
     */
    private function getConfig(Repository $config): array
    {
        $phpVersion = $config->get(Config::KEY_PHP, '') ?: $this->serviceConfig->getPhpVersion();

        if (!in_array($phpVersion, ['7.0', '7.1', '7.2'])) {
            throw new ConfigurationMismatchException('Some error');
        }

        $runtimeExtensions = $this->serviceConfig->getPhpExtensions();
        $test = array_flip($runtimeExtensions);
        $test1 = $this->phpExtensions();
        $phpExtensions = array_intersect_key(
            $test1,
            $test
        );

        $phpConstraint = new Constraint('==', $phpVersion);

        $result = [];
        foreach ($phpExtensions as $phpExtension => $phpExtensionVersions) {
            foreach ($phpExtensionVersions as $phpExtensionVersion => $phpExtensionVersionConfig) {
                $packageConstraint = $this->versionParser->parseConstraints($phpExtensionVersion);
                if ($phpConstraint->matches($packageConstraint)) {
                    $result[$phpExtension] = $phpExtensionVersionConfig;
                }
            }
        }

        $phpDockerfileCommonConfiguration = [
            'entrypoint' => '/docker-entrypoint.sh',
            'extensions' => $result
        ];

        return [
            self::PHP_FPM_BUILD_CONTEXT => array_merge(
                ['image' => sprintf('magento/magento-cloud-docker-php:%s-fpm', $phpVersion)],
                $phpDockerfileCommonConfiguration
            ),
            self::PHP_CLI_BUILD_CONTEXT => array_merge(
                ['image' => sprintf('magento/magento-cloud-docker-php:%s-cli', $phpVersion)],
                $phpDockerfileCommonConfiguration
            )
        ];
    }

    private function phpExtensions(): array
    {
        return [
            'ssh2' => [
                '7.*' => [
                    self::OS_DEPENDENCY => ['libssh2-1', 'libssh2-1-dev'],
                    self::INSTALL_COMMAND => 'pecl install ssh2-1.1.2 && docker-php-ext-enable ssh2',
                ]
            ]
        ];
    }
}





