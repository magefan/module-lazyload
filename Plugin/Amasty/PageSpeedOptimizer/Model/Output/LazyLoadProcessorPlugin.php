<?php
/**
 * Copyright © 2017 Ihor Vansach (ihor@magefan.com). All rights reserved.
 * See LICENSE.txt for license details (http://opensource.org/licenses/osl-3.0.php).
 *
 * Glory to Ukraine! Glory to the heroes!
 */

namespace Magefan\LazyLoad\Plugin\Amasty\PageSpeedOptimizer\Model\Output;

/**
 * Class LazyLoadProcessorPlugin
 * @package Magefan\LazyLoad\Plugin\Amasty\PageSpeedOptimizer\Model\Output
 */
class LazyLoadProcessorPlugin
{
    /**
     * @param $subject
     * @param callable $proceed
     * @param $image
     * @param $imagePath
     * @return mixed|null|string|string[]
     */
    public function aroundReplaceWithPictureTag($subject, callable $proceed, $image, $imagePath)
    {
        $originImagePath = $imagePath;
        if (strpos($imagePath, 'pixel.jpg')) {

            $doStr = 'data-original="';
            $p1 = strpos($image, $doStr);
            if ($p1 !== false) {
                $p1 += strlen($doStr);
                $p2 = strpos($image, '"', $p1);
                if ($p2 !== false) {
                    $imagePath = substr($image, $p1, $p2 - $p1);
                }
            }
        }

        $html = $proceed($image, $imagePath);

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