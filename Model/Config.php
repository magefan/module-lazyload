<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 *
 * Glory to Ukraine! Glory to the heroes!
 */

namespace Magefan\LazyLoad\Model;

use Magento\Framework\App\Action\Action;

/**
 * Lazy load config
 */
class Config extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_ENABLED = 'mflazyzoad/general/enabled';
    const XML_PATH_AMP_ENABLED = 'pramp/general/enabled';
    const XML_PATH_LAZY_BLOCKS = 'mflazyzoad/general/lazy_blocks';
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
    public function isNoScriptEnabled()
    {
        return (bool)$this->getConfig(self::XML_PATH_LAZY_NOSCRIPT);
    }

    /**
     * Retrieve alloved blocks info
     * @return array
     */
    public function getBlocks()
    {
        if (null === $this->blocks) {
            $blocks = $this->getConfig(self::XML_PATH_LAZY_BLOCKS);

            $blocks = str_replace(["\r\n", "\n'\r", "\n"], "\r", $blocks);
            $blocks = explode("\r", $blocks);
            $this->blocks = [];
            foreach ($blocks as $block) {
                if ($block = trim($block)) {
                    $this->blocks[] = $block;
                }
            }
        }

        return $this->blocks;
    }

    /**
     * Retrieve true if enabled
     * @return int
     */
    public function getEnabled()
    {
        if (null === $this->enabled) {
            $this->enabled = $this->getConfig(self::XML_PATH_ENABLED);

            /* check if Plumrocket AMP enabled */
            if ($this->enabled) {
                $isAmpRequest = $this->getConfig(self::XML_PATH_AMP_ENABLED);
                if ($isAmpRequest) {
                    /* We know that using objectManager is not a not a good practice,
                    but if Plumrocket_AMP is not installed on your magento instance
                    you'll get error during di:compile */
                    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                    $isAmpRequest = $objectManager->get('\Plumrocket\Amp\Helper\Data')
                        ->isAmpRequest();
                }
                $this->enabled = !$isAmpRequest;
            }
        }

        return $this->enabled;
    }
}
