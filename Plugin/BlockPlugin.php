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
     * Core store config
     *
     * @var \Magefan\LazyLoad\Model\Config
     */
    protected $config;

    /**
     * BlockPlugin constructor.
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magefan\LazyLoad\Model\Config $config
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magefan\LazyLoad\Model\Config $config
    ) {
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
        $this->config = $config;
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
        $blockTemplate = $block->getTemplate();
        $blocks = $this->config->getBlocks();

        if (!in_array($blockName, $blocks)
            && !in_array(get_class($block), $blocks)
            && !in_array($blockTemplate, $blocks)
        ) {
            return false;
        }

        return $this->config->getEnabled();
    }

}