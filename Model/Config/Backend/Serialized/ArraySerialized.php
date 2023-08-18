<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 *
 * Glory to Ukraine! Glory to the heroes!
 */
declare(strict_types=1);

namespace Magefan\LazyLoad\Model\Config\Backend\Serialized;


/**
 * @api
 * @since 100.0.2
 */
class ArraySerialized extends \Magento\Config\Model\Config\Backend\Serialized
{
    /**
     * Unset array element with '__empty' key
     *
     * @return $this
     */
    public function beforeSave()
    {
        if ($this->preventConfigDataClean()) {
            return $this;
        }

        $value = $this->getValue();

        if (is_array($value)) {
            unset($value['__empty']);
        }
        $this->setValue($value);
        return parent::beforeSave();
    }

    /**
     * @return bool
     */
    private function preventConfigDataClean(): bool
    {
        $fieldsetData = $this->getData('fieldset_data');

        $blocksToLazyLoad = $fieldsetData['blocks_to_lazy_load'] ?? false;
        $method = $fieldsetData['method'] ?? false;

        return $method == \Magefan\LazyLoad\Model\Config\Source\Method::NATIVE;
           // $blocksToLazyLoad == \Magefan\LazyLoad\Model\Config\Source\BlocksToLazyLoad::ALL ||

    }
}
