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
use Psr\Log\LoggerInterface;

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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param Config $config
     * @param Resource $resource
     * @param SerializerInterface $serializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        Config $config,
        Resource $resource,
        SerializerInterface $serializer,
        LoggerInterface $logger
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->config = $config;
        $this->resource = $resource;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    /**
     * @return void
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $scopes = $this->getScopes();
        if (empty($scopes)) {
            $this->moduleDataSetup->endSetup();
            return;
        }

        $connection = $this->moduleDataSetup->getConnection();
        $tableName = $this->moduleDataSetup->getTable('core_config_data');

        foreach ($scopes as $scopeId => $blocks) {
            $jsonBlocks = $this->getJsonFroBlocks($blocks);

            try {
                $connection->update(
                    $tableName,
                    ['value' => $jsonBlocks],
                    [
                        'scope_id = ?' => $scopeId,
                        'path = ?' => Config::XML_PATH_LAZY_BLOCKS,
                    ],
                );
            } catch (\Exception $e) {
                $this->logger->debug(__('Magefan LazyLoad ERROR: while converting to json for scope_id: ') . $scopeId);
                continue;
            }
        }

        $this->moduleDataSetup->endSetup();
    }

    /**
     * @param $blocks
     * @return bool|string
     */
    protected function getJsonFroBlocks($blocks) {
        $arrayBlocks = [];
        foreach ($blocks as $block) {
            $arrayBlocks[$this->getNumberHashForBlock($block)] =
                [
                    'block_identifier' => $block,
                    'first_images_to_skip' => $this->getSkipNelementsNumber($block)
                ];
        }

        return $this->serializer->serialize($arrayBlocks);
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
    protected function getScopes(): array
    {
        $connection = $this->moduleDataSetup->getConnection();
        $query = $connection->select()
            ->from($this->moduleDataSetup->getTable('core_config_data'), ['scope_id','value'])
            ->where(
                'path = ?',
                Config::XML_PATH_LAZY_BLOCKS
            )
            ->order('scope_id ASC');

        $result = $connection->fetchAll($query);
        $scopes = [];
        foreach ($result as $scope) {
            if (!isset($scope['scope_id']) || !isset($scope['value'])) {
                continue;
            }

            $blocks = $this->getBlocks($scope['value']);
            if (!empty($blocks)) {
                $scopes[$scope['scope_id']] = $blocks;
            }
        }

        return $scopes;
    }

    /**
     * @param $blocks
     * @return array
     */
    protected function getBlocks ($blocks): array {
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