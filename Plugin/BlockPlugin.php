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

        if ($this->config->getIsJavascriptLazyLoadMethod()) {

            $numberOfReplacements = $this->config->getBlockFirstImagesToSkip(
                $this->getBlockIdentifier($block)
            );

            if ($numberOfReplacements) {
                $html = $this->removeFirstNImagesWithCustomLabel($html, $numberOfReplacements);
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

            $html = preg_replace(
                '#<img(?!\s+mfdislazy)([^>]*)(?:\ssrc="([^"]*)")([^>]*)\/?>#isU',
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

            if ($numberOfReplacements) {
                $html = $this->revertFirstNImageToInital($html);
            }
        } else {
            $html = preg_replace('#<img(?!\s+mfdislazy)([^>]*)(?:\ssrc="([^"]*)")([^>]*)\/?>#isU', '<img ' .
                ' src="$2" $1 $3 loading="lazy" />
               ', $html);
        }

        return $html;
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
     * @param $html
     * @param int $numberOfReplacements
     * @return array|string|string[]|null
     */
    protected function removeFirstNImagesWithCustomLabel($html, int $numberOfReplacements)
    {
        $count = 0;
        return preg_replace_callback('#<img([^>]*)(?:\ssrc="([^"]*)")([^>]*)\/?>#isU', function ($match) use (&$count, &$numberOfReplacements) {
            $count++;
            if ($count <= $numberOfReplacements) {
                $label = self::REPLACEMENT_LABEL . '_' . $count;
                $imgTag = $match[0];

                if (strpos($imgTag, 'mfdislazy') === false) {
                    $imgTag = str_replace('<img ', '<img mfdislazy="1" ', $imgTag);
                }

                $this->labelsValues[$label] = $imgTag;

                return $label;
            }

            return $match[0];
        }, $html, $numberOfReplacements);
    }

    /**
     * @param $html
     * @return array|mixed|string|string[]
     */
    protected function revertFirstNImageToInital($html)
    {
        foreach ($this->labelsValues as $labelsValue => $img) {
            $html = str_replace($labelsValue, $img, $html);
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

        if ($this->config->getIsAllBlocksAddedToLazy() && !$this->isBlockSkiped($block)) {
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
    private function isBlockSkiped($block): bool
    {
        return in_array(get_class($block), $this->skipBlocks);
    }
}
