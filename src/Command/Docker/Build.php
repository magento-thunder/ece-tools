<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command\Docker;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\RepositoryFactory;
use Magento\MagentoCloud\Docker\ComposeManagerFactory;
use Magento\MagentoCloud\Docker\ConfigurationMismatchException;
use Magento\MagentoCloud\Docker\Service\Config;
use Magento\MagentoCloud\Docker\Service\Version\Validator as VersionValidator;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Builds Docker configuration for Magento project.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Build extends Command
{
    const NAME = 'docker:build';

    const OPTION_PHP = 'php';
    const OPTION_NGINX = 'nginx';
    const OPTION_DB = 'db';
    const OPTION_REDIS = 'redis';
    const OPTION_ES = 'es';
    const OPTION_RABBIT_MQ = 'rmq';
    const OPTION_MODE = 'mode';

    const MAP = [
        self::OPTION_PHP => Config::KEY_PHP,
        self::OPTION_DB => Config::KEY_DB,
        self::OPTION_NGINX => Config::KEY_NGINX,
        self::OPTION_REDIS => Config::KEY_REDIS,
        self::OPTION_ES => Config::KEY_ELASTICSEARCH,
        self::OPTION_RABBIT_MQ => Config::KEY_RABBITMQ,
    ];

    /**
     * @var ComposeManagerFactory
     */
    private $builderFactory;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var RepositoryFactory
     */
    private $configFactory;

    /**
     * @var Config
     */
    private $serviceConfig;

    /**
     * @var VersionValidator
     */
    private $versionValidator;

    /**
     * @param ComposeManagerFactory $builderFactory
     * @param Environment $environment
     * @param RepositoryFactory $configFactory
     * @param Config $serviceConfig
     * @param VersionValidator $versionValidator
     */
    public function __construct(
        ComposeManagerFactory $builderFactory,
        Environment $environment,
        RepositoryFactory $configFactory,
        Config $serviceConfig,
        VersionValidator $versionValidator
    ){
        $this->builderFactory = $builderFactory;
        $this->environment = $environment;
        $this->configFactory = $configFactory;
        $this->serviceConfig = $serviceConfig;
        $this->versionValidator = $versionValidator;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Build docker configuration')
            ->addOption(
                self::OPTION_PHP,
                null,
                InputOption::VALUE_OPTIONAL,
                'PHP version'
            )->addOption(
                self::OPTION_NGINX,
                null,
                InputOption::VALUE_OPTIONAL,
                'Nginx version'
            )->addOption(
                self::OPTION_DB,
                null,
                InputOption::VALUE_OPTIONAL,
                'DB version'
            )->addOption(
                self::OPTION_REDIS,
                null,
                InputOption::VALUE_OPTIONAL,
                'Redis version'
            )->addOption(
                self::OPTION_ES,
                null,
                InputOption::VALUE_OPTIONAL,
                'Elasticsearch version'
            )->addOption(
                self::OPTION_RABBIT_MQ,
                null,
                InputOption::VALUE_OPTIONAL,
                'RabbitMQ version'
            )->addOption(
                self::OPTION_MODE,
                'm',
                InputOption::VALUE_OPTIONAL,
                sprintf(
                    'Mode of environment (%s)',
                    implode(', ', [ComposeManagerFactory::COMPOSE_DEVELOPER, ComposeManagerFactory::COMPOSE_PRODUCTION])
                ),
                ComposeManagerFactory::COMPOSE_PRODUCTION
            );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     *
     * @throws ConfigurationMismatchException
     * @throws \Magento\MagentoCloud\Package\UndefinedPackageException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getOption(self::OPTION_MODE);

        $builder = $this->builderFactory->create($type);
        $config = $this->configFactory->create();

        foreach (self::MAP as $key => $option) {
            if ($value = $input->getOption($option)) {
                $config->set($key, $value);
            }
        }

        $versionList = $this->serviceConfig->getAllServiceVersions($config);
        $unsupportedErrorMsg = $this->versionValidator->validateVersions($versionList);

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            'There are some service versions which are not supported'
            . ' by current Magento version:' . "\n" . implode("\n", $unsupportedErrorMsg) . "\n"
            . 'Do you want to continue?[y/N]',
            false
        );

        if ($unsupportedErrorMsg && !$helper->ask($input, $output, $question) && $input->isInteractive()) {
            return null;
        }

        $builder->build($config);

        try {
            $this->getApplication()
                ->find(ConfigConvert::NAME)
                ->run(new ArrayInput([]), $output);
        } catch (\Exception $exception) {
            throw new ConfigurationMismatchException($exception->getMessage(), $exception->getCode(), $exception);
        }

        $output->writeln('<info>Configuration was built.</info>');
    }

    /**
     * @inheritdoc
     */
    public function isEnabled(): bool
    {
        return !$this->environment->isMasterBranch();
    }
}
