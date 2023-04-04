<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 *
 * Glory to Ukraine! Glory to the heroes!
 */

declare(strict_types=1);

namespace Magefan\LazyLoad\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magefan\LazyLoad\Model\Config;

/**
 * Init lazy load
 */
class Lazy extends Template
{
    /**
     * @param Context $context
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
    }

    /**
     * Retrieve block html
     *
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->config->getEnabled()) {
            return parent::_toHtml();
        }

        return '';
    }

    /**
     * @return bool
     */
    public function isNoScriptEnabled(): bool
    {
        return (bool)$this->config->isNoScriptEnabled();
    }

    /**
     * @return bool
     */
    public function getIsJavascriptLazyLoadMethod(): bool
    {
        return $this->config->getIsJavascriptLazyLoadMethod();
    }

    /**
     * Retrieve lazy load config json string
     *
     * @return string
     */
    public function getLazyLoadConfig(): string
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
