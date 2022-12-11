<?php

namespace Vnecoms\VendorsSales\Observer;

use Magento\Framework\Event\ObserverInterface;

class PlushCacheVendorPage implements ObserverInterface
{
    /**
     * @var \Vnecoms\Vendors\Model\VendorFactory
     */
    protected $vendorFactory;

    /**
     * FieldsetPrepareBefore constructor.
     * @param \Vnecoms\Vendors\Model\VendorFactory $vendorFactory
     */
    public function __construct(
        \Vnecoms\Vendors\Model\VendorFactory $vendorFactory
    ) {
        $this->vendorFactory = $vendorFactory;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Vnecoms\VendorsSales\Model\Order\Invoice */
        $vendorInvoice = $observer->getVendorInvoice();
        $vendorId = $vendorInvoice->getVendorId();
        $vendor = $this->vendorFactory->create()->load($vendorId);
        $vendor->cleanCache();
    }

}
