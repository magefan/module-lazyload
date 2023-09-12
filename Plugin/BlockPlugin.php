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

    const REPLACEMENT_LABEL = 'mf-lazy';

    const PATTERN = '#<img(?!\s+mfdislazy)([^>]*)(?:\ssrc="([^"]*)")([^>]*)\/?>#isU';

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
    protected $skipBlocks = [];

    /**
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param Config $config
     * @param array $skipBlocks
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        Config $config,
        array $skipBlocks
    ) {
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
        $this->config = $config;
        $this->skipBlocks = $skipBlocks;
    }

    /**
     * @param \Magento\Framework\View\Element\AbstractBlock $block
     * @param string $html
     * @return string
     */
    public function afterToHtml(\Magento\Framework\View\Element\AbstractBlock $block, $html)
    {
        if (!$html || !$this->isEnabled($block, (string)$html)) {
            return $html;
        }

        $numberOfReplacements = $this->config->getBlockFirstImagesToSkip($this->getBlockIdentifier($block));

        if ($numberOfReplacements) {
            $html = $this->removeLoadingLazyAttributeFromFirstNImages($html, $numberOfReplacements);
        }

        $html = $this->config->getIsJavascriptLazyLoadMethod()
            ? $this->prepareForJsLazyLoad($block, $html)
            : $this->prepareForNativeBrowserLazyLoad($html);

        return $html;
    }


    /**
     * @param string $html
     * @return string
     */
    private function prepareForJsLazyLoad($block, string $html): string
    {
        $pixelSrc = ' src="' . $block->getViewFileUrl('Magefan_LazyLoad::images/pixel.jpg') . '"';
        $tmpSrc = 'TMP_SRC';

        $html = str_replace($pixelSrc, $tmpSrc, $html);

        $noscript = '';
        if ($this->config->isNoScriptEnabled()) {
            $noscript = '<noscript>
                    <img src="$2"  $1 $3  />
                </noscript>';
        }

        $html = preg_replace(
            self::PATTERN,
            '<img data-original="$2" $1 $3/>' . $noscript,
            $html
        );

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
     * @param string $html
     * @return string
     */
    protected function prepareForNativeBrowserLazyLoad(string $html) :string
    {
        return preg_replace(self::PATTERN, '<img src="$2" $1 $3 loading="lazy" />', $html);
    }

    /**
     * @param \Magento\Framework\View\Element\AbstractBlock $block
     * @return string
     */
    protected function getBlockIdentifier(\Magento\Framework\View\Element\AbstractBlock $block): string
    {
        $blockName = $block->getBlockId() ?: $block->getNameInLayout();
        $blockTemplate = $block->getTemplate();
        $blocks = $this->config->getBlocks();

        if (in_array($blockName, $blocks)) {
            return $blockName;
        } elseif (in_array(get_class($block), $blocks)) {
            return get_class($block);
        } elseif (in_array($blockTemplate, $blocks)) {
            return $blockTemplate;
        }

        return '';
    }

    /**
     * @param string $html
     * @param int $numberOfReplacements
     * @return string
     */
    protected function removeLoadingLazyAttributeFromFirstNImages(string $html, int $numberOfReplacements):string
    {
        $position = 0;

        if (preg_match_all(self::PATTERN, $html, $matches, PREG_OFFSET_CAPTURE) !== false) {
            foreach ($matches[0] as $i => $match) {
                if ($i > $numberOfReplacements - 1) {
                    break;
                }

                $offset = $match[1] + $position;
                $htmlTag = $matches[0][$i][0];

                $newHtmlTag = str_replace(
                    ['loading="lazy"', '<img '],
                    ['', '<img mfdislazy="1" '],
                    $htmlTag
                );

                $html = substr_replace($html, $newHtmlTag, $offset, strlen($htmlTag));
                $position = $position + (strlen($newHtmlTag) - strlen($htmlTag));
            }
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

        if ($this->config->isAllBlocksAddedToLazy() && !$this->isBlockSkipped($block)) {
            return true;
        }

        if (false !== strpos($html, self::LAZY_TAG)) {
            return true;
        }

        if (!$this->getBlockIdentifier($block)) {
            return false;
        }

        return true;
    }

    /**
     * @param $block
     * @return bool
     */
    private function isBlockSkipped($block): bool
    {
        return in_array(get_class($block), $this->skipBlocks);
    }
}
