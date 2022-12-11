<?php

declare(strict_types=1);

namespace Vnecoms\VendorsSales\Plugin\Invoice;

use Vnecoms\VendorsSales\Model\Order;
use Vnecoms\VendorsSales\Model\Order\InvoiceFactory;

class View
{
    /**
     * @var InvoiceFactory
     */
    private $vendorInvoiceFactory;

    /**
     * Tax module helper
     *
     * @var \Magento\Framework\Module\Manager
     */
    protected $_moduleManage;

    /**
     * View constructor.
     * @param InvoiceFactory $vendorInvoiceFactory
     * @param \Magento\Framework\Module\Manager $moduleManage
     */
    public function __construct(
        InvoiceFactory $vendorInvoiceFactory,
        \Magento\Framework\Module\Manager $moduleManage
    ) {
        $this->vendorInvoiceFactory = $vendorInvoiceFactory;
        $this->_moduleManage    = $moduleManage;
    }

    public function aroundGetCreditMemoUrl(
        \Magento\Sales\Block\Adminhtml\Order\Invoice\View $subject,
        callable $proceed
    )
    {
        $invoice = $subject->getInvoice();
        $vendorInvoice = $this->vendorInvoiceFactory->create()->getCollection()
            ->addFieldToFilter("invoice_id", $invoice->getId())
            ->getFirstItem();

        if ($vendorInvoice->getId()) {
            $isVendorShip  = $this->_moduleManage->isEnabled("Vnecoms_VendorsShipping");
            if ($isVendorShip) {
                $om = \Magento\Framework\App\ObjectManager::getInstance();
                $helper = $om->create('Vnecoms\VendorsShipping\Helper\Data');
                if (!$helper->isEnabled()) {
                    return $subject->getUrl(
                        'sales/order_creditmemo/start',
                        ['order_id' => $invoice->getOrder()->getId(), 'invoice_id' => $invoice->getId()]
                    );
                }
            }
            return $subject->getUrl(
                'vendors/sales_creditmemo/start',
                ['vorder_id' => $vendorInvoice->getVendorOrder()->getId(), 'vinvoice_id' => $vendorInvoice->getId()]
            );
        }
        return $subject->getUrl(
            'sales/order_creditmemo/start',
            ['order_id' => $invoice->getOrder()->getId(), 'invoice_id' => $invoice->getId()]
        );
    }
}
