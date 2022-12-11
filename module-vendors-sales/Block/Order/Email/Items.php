<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Sales Order Email order items
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
namespace Vnecoms\VendorsSales\Block\Order\Email;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class Items extends \Magento\Sales\Block\Order\Email\Items
{
    /**
     * @var \Vnecoms\VendorsSales\Model\OrderFactory
     */
    protected $vendorOrderFactory;

    /**
     * Items constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Vnecoms\VendorsSales\Model\OrderFactory $vendorOrderFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Vnecoms\VendorsSales\Model\OrderFactory $vendorOrderFactory,
        OrderRepositoryInterface $orderRepository,
        array $data = []
    ) {
        parent::__construct($context, $data, $orderRepository);
        $this->vendorOrderFactory = $vendorOrderFactory;
    }

    /**
     * Prepare item before output
     *
     * @param \Magento\Framework\View\Element\AbstractBlock $renderer
     * @return void
     */
    protected function _prepareItem(\Magento\Framework\View\Element\AbstractBlock $renderer)
    {
        $vendorOrder = $this->getVendorOrder();
        $renderer->getItem()->setVendorOrder($vendorOrder);
    }

    /**
     * @return mixed
     */
    public function getVendorOrder()
    {
        $order = $this->getData('vendor_order');
        if ($order !== null) {
            return $order;
        }

        $orderId = (int)$this->getData('vendor_order_id');
        if ($orderId) {
            $vendorOrder = $this->vendorOrderFactory->create()->load($orderId);
            $this->setData('vendor_order', $vendorOrder);
        }

        return $this->getData('vendor_order');
    }

}
