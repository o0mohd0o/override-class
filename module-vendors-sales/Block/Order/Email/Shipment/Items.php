<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Sales Order Email Shipment items
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
namespace Vnecoms\VendorsSales\Block\Order\Email\Shipment;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;

class Items extends \Magento\Sales\Block\Order\Email\Shipment\Items
{
    /**
     * @var \Vnecoms\VendorsSales\Model\OrderFactory
     */
    protected $vendorOrderFactory;

    /**
     * Items constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Vnecoms\VendorsSales\Model\OrderFactory $vendorOrderFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Vnecoms\VendorsSales\Model\OrderFactory $vendorOrderFactory,
        OrderRepositoryInterface $orderRepository,
        ShipmentRepositoryInterface $shipmentRepository,
        array $data = []
    ) {
        parent::__construct($context, $data, $orderRepository, $shipmentRepository);
        $this->vendorOrderFactory = $vendorOrderFactory;
    }

    /**
     * Prepare item before output
     *
     * @param \Magento\Framework\View\Element\AbstractBlock $renderer
     * @return \Magento\Sales\Block\Items\AbstractItems
     */
    protected function _prepareItem(\Magento\Framework\View\Element\AbstractBlock $renderer)
    {
        $renderer->getItem()->setOrder($this->getOrder());
        $renderer->getItem()->setVendorOrder($this->getVendorOrder());
        $renderer->getItem()->setSource($this->getShipment());
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
