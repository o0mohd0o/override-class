<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Sales Order Email Invoice items
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
namespace Vnecoms\VendorsSales\Block\Order\Email\Invoice;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;

class Items extends \Magento\Sales\Block\Order\Email\Invoice\Items
{
    /**
     * @var \Vnecoms\VendorsSales\Model\OrderFactory
     */
    protected $vendorOrderFactory;

    /**
     * @var \Vnecoms\VendorsSales\Model\Order\InvoiceFactory
     */
    protected $vendorInvoiceFactory;

    /**
     * Items constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Vnecoms\VendorsSales\Model\OrderFactory $vendorOrderFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param InvoiceRepositoryInterface $invoiceRepository
     * @param \Vnecoms\VendorsSales\Model\Order\InvoiceFactory $vendorInvoiceFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Vnecoms\VendorsSales\Model\OrderFactory $vendorOrderFactory,
        OrderRepositoryInterface $orderRepository,
        InvoiceRepositoryInterface $invoiceRepository,
        \Vnecoms\VendorsSales\Model\Order\InvoiceFactory $vendorInvoiceFactory,
        array $data = []
    ) {
        parent::__construct($context, $data, $orderRepository, $invoiceRepository);
        $this->vendorOrderFactory = $vendorOrderFactory;
        $this->vendorInvoiceFactory = $vendorInvoiceFactory;
    }

    /**
     * Prepare item before output
     *
     * @param \Magento\Framework\View\Element\AbstractBlock $renderer
     * @return \Magento\Sales\Block\Items\AbstractItems
     */
    protected function _prepareItem(\Magento\Framework\View\Element\AbstractBlock $renderer)
    {
        $renderer->getItem()->setVendorOrder($this->getVendorOrder());
        $renderer->getItem()->setOrder($this->getOrder());
        $renderer->getItem()->setInvoice($this->getInvoice());
        $renderer->getItem()->setSource($this->getVendorInvoice());
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


    /**
     * @return mixed
     */
    public function getVendorInvoice()
    {
        $invoice = $this->getData('vendor_invoice');
        if ($invoice !== null) {
            return $invoice;
        }

        $invoiceId = (int)$this->getData('vendor_invoice_id');
        if ($invoiceId) {
            $vendorInvoice = $this->vendorInvoiceFactory->create()->load($invoiceId);
            $this->setData('vendor_invoice', $vendorInvoice);
        }

        return $this->getData('vendor_invoice');
    }
}
