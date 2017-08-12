<?php
/**
 * Copyright © 2017 Ihor Vansach (ihor@magefan.com). All rights reserved.
 * See LICENSE.txt for license details (http://opensource.org/licenses/osl-3.0.php).
 *
 * Glory to Ukraine! Glory to the heroes!
 */

namespace Magefan\LazyLoad\Observer;


class CoreLayoutRenderElement implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * Request
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;
    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var array
     */
    protected $blocks;


    /**
     * @param \Magento\Framework\App\RequestInterface            $request
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param  \Magento\Framework\Event\Observer
     * @return \Magento\Framework\Event\Observer this object
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (PHP_SAPI === 'cli'
            || !in_array($observer->getElementName(), $this->getBlocks())
            || $this->request->isXmlHttpRequest()
            || !$this->isEnabled()
        ) {
            return;
        }

        $transport = $observer->getTransport();
        $html = $transport->getOutput();

        $html = preg_replace('#<img\s+([^>]*)(?:src="([^"]*)")([^>]*)\/?>#isU', '<img data-original="$2" $1 $3/>', $html);

        // var_dump($html);
        // exit();

        $transport->setOutput($html);
    }

    protected function isEnabled()
    {
        $enabled = $this->scopeConfig->getValue(
            'mflazyzoad/general/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );


        /* check if Plumrocket AMP enabled */
        if ($enabled) {
            $isAmpRequest = $this->scopeConfig->getValue(
                'pramp/general/enabled',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            if ($isAmpRequest) {
                /* We know that using objectManager is not a not a good practice,
                but if Plumrocket_AMP is not installed on your magento instance
                you'll get error during di:compile */
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $isAmpRequest = $objectManager->get('\Plumrocket\Amp\Helper\Data')
                    ->isAmpRequest();
            }
            $enabled = !$isAmpRequest;
        }
        return $enabled;
    }

    protected function getBlocks()
    {
        if (null === $this->blocks) {
            $blocks = $this->scopeConfig->getValue(
                'mflazyzoad/general/lazy_blocks',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );

            $blocks = str_replace(["\r\n", "\n'\r", "\n"], "\r", $blocks);
            $blocks = explode("\r", $blocks);
            $this->blocks = [];
            foreach ($blocks as $block) {
                if ($block = trim($block)) {
                    $this->blocks[] = $block;
                }
            }
        }

        return $this->blocks;
    }
}