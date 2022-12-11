<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsSales\Block\Order\Email\Creditmemo;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\CreditmemoRepositoryInterface;

/**
 * Sales Order Email creditmemo items
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Items extends \Magento\Sales\Block\Order\Email\Creditmemo\Items
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
     * @param CreditmemoRepositoryInterface $creditmemo
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Vnecoms\VendorsSales\Model\OrderFactory $vendorOrderFactory,
        OrderRepositoryInterface $orderRepository,
        CreditmemoRepositoryInterface $creditmemo,
        array $data = []
    ) {
        parent::__construct($context, $data, $orderRepository, $creditmemo);
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
        $renderer->getItem()->setOrder($this->getOrder());
        $renderer->getItem()->setVendorOrder($this->getVendorOrder());
        $renderer->getItem()->setSource($this->getCreditmemo());
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
