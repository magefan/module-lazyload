<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 *
 * Glory to Ukraine! Glory to the heroes!
 */
declare(strict_types=1);

namespace Magefan\LazyLoad\Model\Config\Source;

/**
 * Comment statuses
 */
class Method implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @const string
     */
    const JAVASCRIPT = 0;

    /**
     * @const int
     */
    const NATIVE = 1;


    /**
     * Options int
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return  [
            ['value' => self::JAVASCRIPT, 'label' => __('Non-jQuery JavaScript Library (Requires Advanced Configuration)')],
            ['value' => self::NATIVE, 'label' => __('Native Browser Lazy Loading')],
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray(): array
    {
        $array = [];
        foreach ($this->toOptionArray() as $item) {
            $array[$item['value']] = $item['label'];
        }
        return $array;
    }
}
