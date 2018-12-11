<?php
/**
 * Copyright © 2017 Ihor Vansach (ihor@magefan.com). All rights reserved.
 * See LICENSE.txt for license details (http://opensource.org/licenses/osl-3.0.php).
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
}
