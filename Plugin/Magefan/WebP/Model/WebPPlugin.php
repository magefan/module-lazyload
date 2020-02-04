<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 *
 * Glory to Ukraine! Glory to the heroes!
 */

namespace Magefan\LazyLoad\Plugin\Magefan\WebP\Model;

use Magefan\LazyLoad\Model\Config;
use Magefan\WebP\Model\WebP;
use Magento\Framework\Filesystem\Driver\File;

/**
 * Class WebPPlugin
 */
class WebPPlugin
{
    const LAZY_TAG = '<!-- MAGEFAN_LAZY_LOAD -->';

    /**
     * @var WebP 
     */
    private $webp;

    /**
     * @var Config 
     */
    private $config;

    public function __construct(
        WebP  $webp,
        Config $config,
        File $fileDriver

    ) {
        $this->webp = $webp;
        $this->config = $config;
        $this->fileDriver = $fileDriver;
    }

    public function aroundGetPictureTagHtml($subject, callable $proceed, $webpUrl, $imagePath, $htmlTag)
    {

        if (!$this->config->getEnabled()) {
            return $proceed($webpUrl, $imagePath, $htmlTag);
        }

        $originImagePath = $imagePath;

        if (strpos($imagePath, 'Magefan_LazyLoad/images/pixel.jpg')) {

            $doStr = 'data-original="';
            $p1 = strpos($htmlTag, $doStr);

            if ($p1 !== false) {
                $p1 += strlen($doStr);
                $p2 = strpos($htmlTag, '"', $p1);
                if ($p2 !== false) {
                    $imagePath = substr($htmlTag, $p1, $p2 - $p1);
                    $webpUrl = $this->webp->getWebpUrl($imagePath);

                }
            }

        } else {

            return  $proceed($webpUrl, $imagePath, $htmlTag);
        }

        if (!$this->fileDriver->isExists($this->webp->getPathFromUrl($imagePath))) {
            return $htmlTag;
        }

        $html = $proceed($webpUrl, $imagePath, $htmlTag);

        if ($originImagePath != $imagePath) {
            if (strpos($html, '<picture') !== false) {
                $tmpSrc = 'TMP_SRC';
                $pixelSrc = 'srcset="' . $originImagePath . '"';
                $html = str_replace($pixelSrc, $tmpSrc, $html);
                $html = preg_replace('#<source\s+([^>]*)(?:srcset="([^"]*)")([^>]*)?>#isU', '<source ' . $pixelSrc .
                    ' data-originalset="$2" $1 $3/>', $html);
                $html = str_replace($tmpSrc, $pixelSrc, $html);
            }
        }

        return $html;
    }
}
