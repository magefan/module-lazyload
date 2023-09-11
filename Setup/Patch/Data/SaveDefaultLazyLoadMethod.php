<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 *
 * Glory to Ukraine! Glory to the heroes!
 */

declare(strict_types = 1);

namespace Magefan\LazyLoad\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magefan\LazyLoad\Model\Config as Config;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magefan\LazyLoad\Model\Config\Source\Method as LazyLoadMethod;
use Magefan\LazyLoad\Model\Config\Source\BlocksToLazyLoad;

class SaveDefaultLazyLoadMethod implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param Config $config
     * @param WriterInterface $configWriter
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        Config $config,
        WriterInterface $configWriter
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->config = $config;
        $this->configWriter = $configWriter;
    }

    /**
     * Set Default Lazy Load Method as JS to keep existing lazy load config for customers that configured lazy laod.
     *
     * @return void
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $connection = $this->moduleDataSetup->getConnection();
        $tableName = $this->moduleDataSetup->getTable('core_config_data');

        if ($this->config->getBlocksInfo()) {
            $query = $connection->select()
                ->from($tableName, ['config_id', 'value'])
                ->where('path = ?', Config::XML_PATH_LAZY_METHOD);

            // if lazy load was installed previusly and lazy laod method not set yet, set js lazy load as a default method
            if (!$connection->fetchAll($query)) {
                $this->configWriter->save(Config::XML_PATH_LAZY_METHOD, LazyLoadMethod::JAVASCRIPT);
                $this->configWriter->save(Config::XML_PATH_LAZY_BLOCKS_TO_LAZY_LOAD, BlocksToLazyLoad::SELECTED);
            }
        }

        $this->moduleDataSetup->endSetup();
    }

    /**
     * @return array|string[]
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @return array|string[]
     */
    public function getAliases()
    {
        return [];
    }
}
