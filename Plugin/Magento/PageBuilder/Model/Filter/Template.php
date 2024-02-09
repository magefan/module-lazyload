<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 *
 * Glory to Ukraine! Glory to the heroes!
 */

declare(strict_types = 1);

namespace Magefan\LazyLoad\Plugin\Magento\PageBuilder\Model\Filter;

use Magefan\LazyLoad\Model\Config;

class Template
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * @param $subject
     * @param $result
     * @return mixed|string
     */
    public function afterFilter($subject, $result)
    {
        if ($this->config->getEnabled()) {
            $this->moveMfDislazyAttributeDirectAfterImg($result);
        }

        return $result;
    }

    /**
     * @param string $result
     * @return void
     */
    private function moveMfDislazyAttributeDirectAfterImg(string &$result)
    {
        if (strpos($result, 'mfdislazy="1"') !== false) {
            $result = explode('<img ', $result);

            foreach ($result as $key => $imgStart) {
                if (strpos($imgStart, 'mfdislazy="1"') !== false) {
                    $result[$key] = 'mfdislazy="1" ' . str_replace('mfdislazy="1"', '', $imgStart);
                }
            }

            $result = implode('<img ', $result);
        }
    }
}
