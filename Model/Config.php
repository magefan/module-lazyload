<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 *
 * Glory to Ukraine! Glory to the heroes!
 */

declare(strict_types=1);

namespace Magefan\LazyLoad\Model;

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
