<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 *
 * Glory to Ukraine! Glory to the heroes!
 */

declare(strict_types=1);

namespace Magefan\LazyLoad\Block\Adminhtml\System\Config\Form;


class Js extends \Magento\Config\Block\System\Config\Form\Field
{
    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('js.phtml');
    }

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return parent::render($element) . $this->toHtml();
    }
}
