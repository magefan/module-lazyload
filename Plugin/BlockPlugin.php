<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 *
 * Glory to Ukraine! Glory to the heroes!
 */

declare(strict_types = 1);

namespace Magefan\LazyLoad\Plugin;

/**
 * Plugin for sitemap generation
 */
class BlockPlugin
{
    const LAZY_TAG = '<!-- MAGEFAN_LAZY_LOAD -->';

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
     * Lazy store config
     *
     * @var \Magefan\LazyLoad\Model\Config
     */
    protected $config;

    /**
     * BlockPlugin constructor
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param null|\Magefan\LazyLoad\Model\Config $config
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        $config = null
    ) {
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->config = $config ?: $objectManager->get(
            \Magefan\LazyLoad\Model\Config::class
        );
    }


    /**
     * @param \Magento\Framework\View\Element\AbstractBlock $block
     * @param string $html
     * @return string
     */
    public function afterToHtml(\Magento\Framework\View\Element\AbstractBlock $block, $html)
    {
        if (!$this->isEnabled($block, (string)$html)) {
            return $html;
        }

        $pixelSrc = ' src="' . $block->getViewFileUrl('Magefan_LazyLoad::images/pixel.jpg') . '"';
        $tmpSrc = 'TMP_SRC';

        $html = str_replace($pixelSrc, $tmpSrc, $html);

        $noscript = '';
        if ($this->config->isNoScriptEnabled()) {
            $noscript = '<noscript>
                <img src="$2"  $1 $3  />
            </noscript>';
        }

        $html = preg_replace('#<img(?!\s+mfdislazy)([^>]*)(?:\ssrc="([^"]*)")([^>]*)\/?>#isU', '<img ' .
            ' data-original="$2" $1 $3/>
           ' . $noscript, $html);

        $html = str_replace(' data-original=', $pixelSrc . ' data-original=', $html);

        $html = str_replace($tmpSrc, $pixelSrc, $html);
        $html = str_replace(self::LAZY_TAG, '', $html);

        /* Disable Owl Slider LazyLoad */
        $html = str_replace(
            ['"lazyLoad":true,', '&quot;lazyLoad&quot;:true,', 'owl-lazy'],
            ['"lazyLoad":false,', '&quot;lazyLoad&quot;:false,', ''],
            $html
        );

        /* Fix for page builder bg images */
        if (false !== strpos($html, 'background-image-')) {
            $html = str_replace('.background-image-', '.tmpbgimg-', $html);
            $html = str_replace('background-image-', 'mflazy-background-image mflazy-background-image-', $html);
            $html = str_replace('.tmpbgimg-', '.background-image-', $html);
        }

        return $html;
    }

    /**
     * Check if lazy load is available for block
     * @param \Magento\Framework\View\Element\AbstractBlock $block
     * @param string $html
     * @return boolean
     */
    protected function isEnabled($block, string $html): bool
    {
        if (PHP_SAPI === 'cli'
            || $this->request->isXmlHttpRequest()
            || false !== stripos($this->request->getFullActionName(), 'ajax')
            || false !== stripos($this->request->getServer('REQUEST_URI'), 'layerednavigationajax')
            || $this->request->getParam('isAjax')
        ) {
            return false;
        }

        if (!$this->config->getEnabled()) {
            return false;
        }

        $blockName = $block->getBlockId() ?: $block->getNameInLayout();
        $blockTemplate = $block->getTemplate();
        $blocks = $this->config->getBlocks();

        if (!in_array($blockName, $blocks)
            && !in_array(get_class($block), $blocks)
            && !in_array($blockTemplate, $blocks)
            && (false === strpos($html, self::LAZY_TAG))
        ) {
            return false;
        }

        return true;
    }
}
