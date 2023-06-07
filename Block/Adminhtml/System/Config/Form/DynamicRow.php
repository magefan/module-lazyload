<?php

namespace Magefan\LazyLoad\Block\Adminhtml\System\Config\Form;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

class DynamicRow extends AbstractFieldArray
{
    /**
     * Prepare rendering the new field by adding all the needed columns
     */
    protected function _prepareToRender()
    {
        $this->addColumn('block', ['label' => __('Block'), 'style' => 'width:170px']);
        $this->addColumn('skipNElements', ['label' => __('Skip First N Images'), 'style' => 'width:170px']);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }
}