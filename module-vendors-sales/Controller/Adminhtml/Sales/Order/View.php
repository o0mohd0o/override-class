<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsSales\Controller\Adminhtml\Sales\Order;

class View extends \Vnecoms\VendorsSales\Controller\Adminhtml\Sales\Order
{
    /**
     * View order detail
     *
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $order = $this->_initOrder();
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($order) {
            try {
                $resultPage = $this->_initAction();
                $resultPage->getConfig()->getTitle()->prepend(__('Orders'));
            } catch (\Exception $e) {
                $this->logger->critical($e);
                $this->messageManager->addErrorMessage(__('Exception occurred during order load'));
                $resultRedirect->setPath('vendors/sales_order/index');
                return $resultRedirect;
            }
            $resultPage->getConfig()->getTitle()->prepend(sprintf("#%s", $order->getIncrementId()));
            return $resultPage;
        }
        $resultRedirect->setPath('vendors/sales_order/index');
        return $resultRedirect;
    }
}
