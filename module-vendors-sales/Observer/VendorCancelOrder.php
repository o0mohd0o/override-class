<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsSales\Observer;

use Magento\Framework\Event\ObserverInterface;
use Vnecoms\VendorsConfig\Helper\Data;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order as BaseOrder;

class VendorCancelOrder implements ObserverInterface
{
    /**
     * @var \Vnecoms\Vendors\Helper\Data
     */
    protected $_vendorHelper;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Vnecoms\VendorsSales\Model\OrderFactory
     */
    protected $_vendorOrderFactory;


    /**
     * VendorCancelOrder constructor.
     * @param \Vnecoms\Vendors\Helper\Data $vendorHelper
     * @param OrderRepositoryInterface $orderRepository
     * @param \Vnecoms\VendorsSales\Model\OrderFactory $vendorOrderFactory
     */
    public function __construct(
        \Vnecoms\Vendors\Helper\Data $vendorHelper,
        OrderRepositoryInterface $orderRepository,
        \Vnecoms\VendorsSales\Model\OrderFactory $vendorOrderFactory
    ) {
        $this->_vendorHelper = $vendorHelper;
        $this->orderRepository = $orderRepository;
        $this->_vendorOrderFactory = $vendorOrderFactory;
    }

    /**
     * Add multiple vendor order row for each vendor.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /* Do nothing if the extension is not enabled.*/
        if (!$this->_vendorHelper->moduleEnabled()) {
            return;
        }
        $order = $observer->getOrder()->getOrder();
        //$order = $this->orderRepository->get($order->getId());
        $isCancel = true;

        $vendorOrders = $this->_vendorOrderFactory->create()
          ->getCollection()->addFieldToFilter("order_id", $order->getId());
        foreach ($vendorOrders as $vendorOrder) {
            if ( $vendorOrder->getState() != BaseOrder::STATE_CANCELED ) {
                $isCancel = false;
                break;
            }
        }

        if ($isCancel && $order->getState() != BaseOrder::STATE_CANCELED) {
            $order->setState(BaseOrder::STATE_CANCELED)
                ->setStatus($order->getConfig()->getStateDefaultStatus(BaseOrder::STATE_CANCELED));
        }
        $this->orderRepository->save($order);
        return $this;
    }
}
