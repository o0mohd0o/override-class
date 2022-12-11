<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsSales\Block\Vendors\Order\Invoice;

use Magento\Sales\Model\Order\Invoice;

/**
 * Adminhtml order invoice totals block
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Totals extends \Magento\Sales\Block\Adminhtml\Order\Invoice\Totals
{
    /**
     * @var \Vnecoms\VendorsSales\Model\Order\Invoice
     */
    protected $_vendorInvoice;
    
    /**
     * Initialize order totals array
     *
     * @return $this
     */
    protected function _initTotals()
    {
        parent::_initTotals();
        if (isset($this->_totals['shipping'])) {
            $this->_totals['shipping'] = new \Magento\Framework\DataObject(
                [
                    'code' => 'shipping',
                    'value' => $this->getSource()->getShippingAmount(),
                    'base_value' => $this->getSource()->getBaseShippingAmount(),
                    'label' => __('Shipping & Handling'),
                ]
            );
        }
        return $this;
    }
    
    /**
     * Get vendor invoice.
     * @return \Vnecoms\VendorsSales\Model\Order\Invoice
     */
    public function getVendorInvoice()
    {
        if (!$this->_vendorInvoice) {
            if ($invoice = $this->_coreRegistry->registry('vendor_invoice')) {
                $this->_vendorInvoice = $invoice;
            } else {
                $om = \Magento\Framework\App\ObjectManager::getInstance();
                $this->_vendorInvoice = $om->create('Vnecoms\VendorsSales\Model\Order\Invoice');
                $this->_vendorInvoice->setData($this->getInvoice()->getData());

                /*
                $totalCreditAmount = $this->_vendorInvoice->getCreditAmount();
                $baseTotalCreditAmount = $this->_vendorInvoice->getBaseCreditAmount();
                $this->_vendorInvoice->setCreditAmount(0);
                $this->_vendorInvoice->setBaseCreditAmount(0);
                $this->_vendorInvoice->setGrandTotal($this->_vendorInvoice->getGrandTotal() - $totalCreditAmount);
                $this->_vendorInvoice->setBaseGrandTotal($this->_vendorInvoice->getBaseGrandTotal() - $baseTotalCreditAmount);
                */

                $vendorOrder = $this->_coreRegistry->registry('vendor_order');
                $this->_vendorInvoice->setVendorOrderId($vendorOrder->getId());

            }
        }
        return $this->_vendorInvoice;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Magento\Sales\Block\Adminhtml\Order\Invoice\Totals::getSource()
     */
    public function getSource()
    {
        return $this->getVendorInvoice();
    }
}
