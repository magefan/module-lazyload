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
use Magento\Config\Model\ResourceModel\Config as Resource;
use Magento\Framework\Serialize\SerializerInterface;

class ConvertConfigToJsonPatch implements DataPatchInterface
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
     * @var Resource
     */
    private $resource;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param Config $config
     * @param Resource $resource
     * @param SerializerInterface $serializer
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        Config $config,
        Resource $resource,
        SerializerInterface $serializer
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->config = $config;
        $this->resource = $resource;
        $this->serializer = $serializer;
    }

    /**
     * @return void
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $blocks = $this->getBlocks();
        if (empty($blocks)) {
            $this->moduleDataSetup->endSetup();
            return;
        }

        $arrayBlocks = [];
        foreach ($blocks as $block) {
            $arrayBlocks[$this->getNumberHashForBlock($block)] =
                [
                    'block' => $block,
                    'skipNElements' => $this->getSkipNelementsNumber($block)
                ];
        }

        $jsonBlocks = $this->serializer->serialize($arrayBlocks);

        $this->resource->saveConfig(Config::XML_PATH_LAZY_BLOCKS, $jsonBlocks);

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

    /**
     * @param $block
     * @return string
     */
    protected function getSkipNelementsNumber($block): string {
        if ('category.products.list' == $block) {
            return '2';
        }

        return '0';
    }

    /**
     * @param $block
     * @return string
     */
    protected function getNumberHashForBlock ($block): string {
        $numberHashFromString = sprintf('%u', crc32($block));
        $numberHashFromStringSuffix = substr($numberHashFromString, -3);

        return '_' . $numberHashFromString . $numberHashFromStringSuffix . '_' . $numberHashFromStringSuffix;
    }

    /**
     * Retrieve alloved blocks info
     * @return array
     */
    protected function getBlocks(): array
    {
        $blocks = $this->config->getConfig(Config::XML_PATH_LAZY_BLOCKS);
        json_decode($blocks);
        if (json_last_error() === JSON_ERROR_NONE) {
            return [];
        }

        $blocks = str_replace(["\r\n", "\n'\r", "\n"], "\r", $blocks);
        $blocks = explode("\r", $blocks);
        $result = [];
        foreach ($blocks as $block) {
            if ($block = trim($block)) {
                $result[] = $block;
            }
        }

        return $result;
    }
}