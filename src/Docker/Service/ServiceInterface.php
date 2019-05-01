<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Docker\Service;

/**
 * Service Interface
 */
interface ServiceInterface
{
    /**
     * Return docker compose configuration
     * @return array
     */
    public function getComposeConfig(): array;
}