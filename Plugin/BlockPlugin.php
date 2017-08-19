<?php
/**
 * Copyright © 2017 Ihor Vansach (ihor@magefan.com). All rights reserved.
 * See LICENSE.txt for license details (http://opensource.org/licenses/osl-3.0.php).
 *
 * Glory to Ukraine! Glory to the heroes!
 */

namespace Magefan\LazyLoad\Plugin;

/**
 * Plugin for sitemap generation
 */
class BlockPlugin
{
    /**
     * Request
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;
    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var array
     */
    protected $blocks;


    /**
     * @param \Magento\Framework\App\RequestInterface            $request
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
    }


    /**
     * @param \Magento\Framework\View\Element\AbstractBlock $block
     * @param string $html
     * @return string
     */
    public function afterToHtml(\Magento\Framework\View\Element\AbstractBlock $block, $html)
    {
        if (!$this->isEnabled($block)) {
            return $html;
        }

        $html = preg_replace('#<img\s+([^>]*)(?:src="([^"]*)")([^>]*)\/?>#isU', '<img src="' .
            $block->getViewFileUrl('Magefan_LazyLoad::images/pixel.jpg') . '" ' .
            'data-original="$2" $1 $3/>', $html);

        /*
        $i = $block->getNameInLayout(). ' ' . $block->getBlockId() . ' ' . get_class($block);
        $html =  'start of <br/><br/>'. $i . $html;
        */
        return $html;
    }

    /**
     * Check if lazy load is available
     * @param \Magento\Framework\View\Element\AbstractBlock $block
     * @return boolean
     */
    protected function isEnabled($block)
    {
        if (PHP_SAPI === 'cli' || $this->request->isXmlHttpRequest()) {
            return false;
        }

        $blockName = $block->getBlockId() ?: $block->getNameInLayout();
        $blocks = $this->getBlocks();

        if (!in_array($blockName, $blocks)
            && !in_array(get_class($block), $blocks)
        ) {
            return false;
        }

        $enabled = $this->scopeConfig->getValue(
            'mflazyzoad/general/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        /* check if Plumrocket AMP enabled */
        if ($enabled) {
            $isAmpRequest = $this->scopeConfig->getValue(
                'pramp/general/enabled',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            if ($isAmpRequest) {
                /* We know that using objectManager is not a not a good practice,
                but if Plumrocket_AMP is not installed on your magento instance
                you'll get error during di:compile */
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $isAmpRequest = $objectManager->get('\Plumrocket\Amp\Helper\Data')
                    ->isAmpRequest();
            }
            $enabled = !$isAmpRequest;
        }

        return $enabled;
    }

    /**
     * Retrieve alloved blocks info
     * @return array
     */
    protected function getBlocks()
    {
        if (null === $this->blocks) {
            $blocks = $this->scopeConfig->getValue(
                'mflazyzoad/general/lazy_blocks',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );

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
}