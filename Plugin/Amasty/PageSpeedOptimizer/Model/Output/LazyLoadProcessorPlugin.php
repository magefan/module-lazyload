<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 *
 * Glory to Ukraine! Glory to the heroes!
 */

namespace Magefan\LazyLoad\Plugin\Amasty\PageSpeedOptimizer\Model\Output;

use Magefan\LazyLoad\Model\Config;

/**
 * Class LazyLoadProcessorPlugin
 */
class LazyLoadProcessorPlugin
{
/**
     * @var Config
     */
    private $config;

    /**
     * LazyLoadProcessorPlugin constructor.
     * @param Config $config
     */
    public function __construct(
        Config $config

    ) {
        $this->config = $config;
    }

    /**
     * @param $subject
     * @param callable $proceed
     * @param $image
     * @param $imagePath
     * @return mixed|null|string|string[]
     */
    public function aroundReplaceWithPictureTag($subject, callable $proceed, $image, $imagePath)
    {
        if (!$this->config->getEnabled()) {
            return $proceed($image, $imagePath);
        }

        $originImagePath = $imagePath;

        if (strpos($imagePath, 'Magefan_LazyLoad/images/pixel.jpg')) {

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

    /**
     * @param $subject
     * @param callable $proceed
     * @param algorithm
     * @param $image
     * @param $imagePath
     * @return mixed|null|string|string[]
     */
    public function aroundReplace($subject, callable $proceed, $algorithm, $image, $imagePath)
    {

        if (!$this->config->getEnabled()) {
            return $proceed($algorithm, $image, $imagePath);
        }

        $originImagePath = $imagePath;

        if (strpos($imagePath, 'Magefan_LazyLoad/images/pixel.jpg')) {

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

        $html = $proceed($algorithm, $image, $imagePath);

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
