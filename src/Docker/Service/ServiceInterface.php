<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Docker\Service;

use Illuminate\Config\Repository;

/**
 * Service Interface
 */
interface ServiceInterface
{

    /**
     * Return docker compose configuration
     * @return array
     */
    public function getConfig(): array;

    /**
     * Return depend configuration
     *
     * @return Repository
     */
    public function getDepends();
}