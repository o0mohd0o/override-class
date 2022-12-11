<?php

namespace Vnecoms\VendorsSales\Observer;

use Magento\Framework\Event\ObserverInterface;
use Vnecoms\VendorsConfig\Helper\Data;
use Magento\Sales\Model\Order;

class ProcessOrderStatus implements ObserverInterface
{
    /**
     * @var \Vnecoms\VendorsSales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var \Vnecoms\VendorsSales\Model\ResourceModel\Order\Handler\State
     */
    protected $stateHandler;

    /**
     * ProcessOrderStatus constructor.
     * @param \Vnecoms\VendorsSales\Model\ResourceModel\Order\CollectionFactory $collectionFactory
     * @param \Vnecoms\VendorsSales\Model\ResourceModel\Order\Handler\State $stateHandler
     */
    public function __construct(
        \Vnecoms\VendorsSales\Model\ResourceModel\Order\CollectionFactory $collectionFactory,
        \Vnecoms\VendorsSales\Model\ResourceModel\Order\Handler\State $stateHandler
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->stateHandler = $stateHandler;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getOrder();
        $vendorOrderCollection = $this->collectionFactory->create()->addFieldToFilter('order_id', $order->getId());
        foreach($vendorOrderCollection as $vendorOrder){
            $this->stateHandler->check($vendorOrder, $order);
            $vendorOrder->save();
        }
    }
}
