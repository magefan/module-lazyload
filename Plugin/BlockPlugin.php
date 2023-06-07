<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 *
 * Glory to Ukraine! Glory to the heroes!
 */

declare(strict_types = 1);

namespace Magefan\LazyLoad\Plugin;

use Magefan\LazyLoad\Model\Config;

/**
 * Plugin for sitemap generation
 */
class BlockPlugin
{
    const LAZY_TAG = '<!-- MAGEFAN_LAZY_LOAD -->';

    const CUSTOM_LABEL = 'mf-lazy';

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
     * @var array
     */
    protected $labelsValues = [];

    /**
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param Config $config
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        Config $config
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
        if (!$this->isEnabled($block, (string)$html)) {
            return $html;
        }

        $blockName = $block->getBlockId() ?: $block->getNameInLayout();
        $numberOfReplacements = (int)($this->config->getBlocks()[$blockName] ?? 0);

        if ($numberOfReplacements) {
            $html = $this->removeFirstNImagesWithCustomLabel($html, $numberOfReplacements);
        }

        if ($this->config->getIsJavascriptLazyLoadMethod()) {
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
        } else {
            $html = preg_replace('#<img(?!\s+mfdislazy)([^>]*)(?:\ssrc="([^"]*)")([^>]*)\/?>#isU', '<img ' .
                ' src="$2" $1 $3 loading="lazy" />
               ', $html);
        }

        if ($numberOfReplacements) {
            return $this->revertFirstNImageToInital($html);
        }

        return $html;
    }

    /**
     * @param $html
     * @param int $numberOfReplacements
     * @return array|string|string[]|null
     */
    protected function removeFirstNImagesWithCustomLabel($html, int $numberOfReplacements) {
        $count = 0;
        return preg_replace_callback('#<img([^>]*)(?:\ssrc="([^"]*)")([^>]*)\/?>#isU', function ($match) use (&$count, &$numberOfReplacements) {
            $count++;
            if ($count <= $numberOfReplacements) {
                $label = self::CUSTOM_LABEL . '_' . $count;
                $this->labelsValues[$label] = $match[0];

                return $label;
            }

            return $match[0];
        }, $html, $numberOfReplacements);
    }

    /**
     * @param $html
     * @return array|string|string[]|null
     */
    protected function revertFirstNImageToInital($html) {
        return preg_replace_callback('/' . self::CUSTOM_LABEL .'_\d+\b(.*?)/', function($match) use (&$count) {
            return $this->labelsValues[$match[0]] ?? $match[0];
        }, $html);
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

        if (!in_array($blockName, array_keys($blocks))
            && !in_array(get_class($block), $blocks)
            && !in_array($blockTemplate, $blocks)
            && (false === strpos($html, self::LAZY_TAG))
        ) {
            return false;
        }

        return true;
    }
}
