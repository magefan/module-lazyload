<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 *
 * Glory to Ukraine! Glory to the heroes!
 */
declare(strict_types=1);

namespace Magefan\LazyLoad\Model\Config\Source;

class BlocksToLazyLoad implements \Magento\Framework\Option\ArrayInterface
{

    const ALL = 0;
    const SELECTED = 1;

    /**
     * @return array[]
     */
    public function toOptionArray(): array
    {
        return  [
            ['value' => self::ALL, 'label' => __('All')],
            ['value' => self::SELECTED, 'label' => __('Selected')],
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
