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
     * @return bool
     */
    public function isNoScriptEnabled()
    {
        return (bool)$this->_scopeConfig->getValue(
            \Magefan\LazyLoad\Model\Config::XML_PATH_LAZY_NOSCRIPT,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieve lazy load config json string
     *
     * @return string
     */
    public function getLazyLoadConfig()
    {
        $config = $this->getData('lazy_load_config');

        if (!is_array($config)) {
            $config = [];
        }

        if (!isset($config['elements_selector'])) {
            $config['elements_selector'] = 'img,div';
        }

        if (!isset($config['data_srcset'])) {
            $config['data_srcset'] = 'originalset';
        }

        return json_encode($config);
    }
}
