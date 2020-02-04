<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 *
 * Glory to Ukraine! Glory to the heroes!
 */

namespace Magefan\LazyLoad\Plugin\Magefan\WebP\Model;

use Magefan\WebP\Model\WebP;

/**
 * Class WebPPlugin
 */
class WebPPlugin
{
    const LAZY_TAG = '<!-- MAGEFAN_LAZY_LOAD -->';

    /**
     * @var Convertor
     */
    private $webp;

    public function __construct(
        WebP  $webp

    ) {
        $this->webp = $webp;
    }

    public function aroundGetPictureTagHtml($subject, callable $proceed, $webpUrl, $imagePath, $htmlTag)
    {
        $result = false;

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

                    $result = $this->webp->convert($imagePath, $webpUrl);

                }
            }
        }

        if (!$result) {
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
