<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsSales\Controller\Vendors\Invoice\AbstractInvoice;

/**
 * Class Email
 *
 * @package Magento\Sales\Controller\Adminhtml\Invoice\AbstractInvoice
 */
abstract class Email extends \Vnecoms\Vendors\Controller\Vendors\Action
{

    /**
     * @var \Magento\Backend\Model\View\Result\ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * Email constructor.
     * @param \Vnecoms\Vendors\App\Action\Context $context
     * @param \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory
     */
    public function __construct(
        \Vnecoms\Vendors\App\Action\Context $context,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory
    ) {
        parent::__construct($context);
        $this->resultForwardFactory = $resultForwardFactory;
    }

    /**
     * Notify user
     *
     * @return \Magento\Backend\Model\View\Result\Forward|\Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $invoiceId = $this->getRequest()->getParam('invoice_id');
        if (!$invoiceId) {
            return $this->resultForwardFactory->create()->forward('noroute');
        }
        $vendorInvoice = $this->_objectManager->create(\Vnecoms\VendorsSales\Model\Order\Invoice::class)->load($invoiceId);
        if (!$vendorInvoice) {
            return $this->resultForwardFactory->create()->forward('noroute');
        }
        $invoice = $vendorInvoice->getInvoice();
        $this->_objectManager->create(
            \Magento\Sales\Api\InvoiceManagementInterface::class
        )->notify($invoice->getEntityId());

        $this->messageManager->addSuccessMessage(__('You sent the message.'));
        return $this->resultRedirectFactory->create()->setPath(
            'sales/order_invoice/view',
            ['invoice_id' => $invoiceId]
        );
    }
}
