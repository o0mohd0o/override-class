<?php
/**
 * @category    Magento
 * @package     Magento_Sales
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsSales\Block\Adminhtml\Vorder\View;

/**
 * Adminhtml sales order view
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class History extends \Magento\Sales\Block\Adminhtml\Order\View\History
{
    /**
     * Submit URL getter
     *
     * @return string
     */
    public function getSubmitUrl()
    {
        /*$vendorId = $this->getRequest()->getParam('vendor_id');
        $vendorOrderId = $this->getRequest()->getParam('vendor_order_id');
        $params = $this->getRequest()->getParams();
        $params = array_merge($params,[
            'vendor_order_id'=>$vendorOrderId,
            'vendor_id' => $vendorId
        ]);*/
        return $this->getUrl('vendors/*/addComment', ['vorder_id' => $this->getOrder()->getId()]);
    }

}
