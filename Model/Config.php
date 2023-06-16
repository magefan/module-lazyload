<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 *
 * Glory to Ukraine! Glory to the heroes!
 */

declare(strict_types=1);

namespace Magefan\LazyLoad\Model;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Lazy load config
 */
class Config extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_ENABLED = 'mflazyzoad/general/enabled';
    const XML_PATH_AMP_ENABLED = 'pramp/general/enabled';
    const XML_PATH_LAZY_BLOCKS = 'mflazyzoad/general/lazy_blocks';
    const XML_PATH_LAZY_METHOD = 'mflazyzoad/general/method';
    const XML_PATH_LAZY_NOSCRIPT = 'mflazyzoad/general/noscript';


    /**
     * @var array
     */
    protected $blocks;

    /**
     * @var bool
     */
    protected $enabled;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @param Context $context
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Context $context,
        SerializerInterface $serializer
    ) {
        $this->serializer = $serializer;
        parent::__construct($context);
    }

    /**
     * Retrieve store config value
     * @param  string $path
     * @return mixed
     */
    public function getConfig($path)
    {
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return bool
     */
    public function getIsJavascriptLazyLoadMethod(): bool
    {
        return (0 === (int)$this->getConfig(self::XML_PATH_LAZY_METHOD));
    }

    /**
     * @return bool
     */
    public function isNoScriptEnabled(): bool
    {
        return (bool)$this->getConfig(self::XML_PATH_LAZY_NOSCRIPT)
            && $this->getIsJavascriptLazyLoadMethod();
    }

    /**
     * Retrieve alloved blocks info
     * @return array
     */
    public function getBlocks(): array
    {
        return array_keys($this->getBlocksInfo());
    }

    /**
     * @param $blockIdentifier
     * @return int
     */
    public function getBlockFirstImagesToSkip($blockIdentifier): int
    {
        $blockInfo = $this->getBlocksInfo();
        if (isset($blockInfo[$blockIdentifier])) {
            return (int)$blockInfo[$blockIdentifier];
        }

        return 0;
    }

    /**
     * @return array
     */
    public function getBlocksInfo(): array
    {
        if (null === $this->blocks) {
            try {
                $blocks = $this->serializer->unserialize($this->getConfig(self::XML_PATH_LAZY_BLOCKS));
            } catch (\InvalidArgumentException $e) {
                return [];
            }

            foreach ($blocks as $blockData) {
                if (!isset($blockData['block_identifier']) || !isset($blockData['first_images_to_skip'])) {
                    continue;
                }

                $this->blocks[$blockData['block_identifier']] = $blockData['first_images_to_skip'];
            }
            
            $this->blocks = null !== $this->blocks ?: [];
        }

        return $this->blocks;
    }

    /**
     * Retrieve true if enabled
     * @return bool
     */
    public function getEnabled(): bool
    {
        if (null === $this->enabled) {
            $this->enabled = (bool)$this->getConfig(self::XML_PATH_ENABLED);

            /* check if Plumrocket AMP enabled */
            if ($this->enabled) {
                /* We know that using objectManager is not a not a good practice,
                    but if Plumrocket_AMP is not installed on your magento instance
                    you'll get error during di:compile */
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

                $isAmpRequest = $this->getConfig(self::XML_PATH_AMP_ENABLED);
                if ($isAmpRequest) {
                    $isAmpRequest = $objectManager->get('\Plumrocket\Amp\Helper\Data')
                        ->isAmpRequest();
                } else {
                    $isAmpRequest = $objectManager->get(\Magento\Framework\App\RequestInterface::class)
                        ->getParam('is_amp');
                }
                $this->enabled = !$isAmpRequest;
            }
        }

        return $this->enabled;
    }
}
