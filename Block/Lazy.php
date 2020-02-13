<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 *
 * Glory to Ukraine! Glory to the heroes!
 */

namespace Magefan\LazyLoad\Block;

use Magento\Store\Model\ScopeInterface;

/**
 * Init lazy load
 */
class Lazy extends \Magento\Framework\View\Element\Template
{
    /**
     * Retrieve block html
     *
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->_scopeConfig->getValue(
            'mflazyzoad/general/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        )) {
            return parent::_toHtml();
        }

        return '';
    }

    /**
     * Retrieve lazy load config json string
     *
     * @return string
     */
    public function getLazyLoadConfig()
    {
        $config = $this->getData('lazy_load_config');

        if (!$config || !is_array($config)) {
            $config = [
                'elements_selector' => 'img,div',
                'data_srcset' => 'originalset',
            ];
        }

        return json_encode($config);
    }
}
