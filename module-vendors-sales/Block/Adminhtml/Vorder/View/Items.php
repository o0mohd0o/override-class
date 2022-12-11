<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsSales\Block\Adminhtml\Vorder\View;

/**
 * Order view tabs
 */
class Items extends \Magento\Sales\Block\Adminhtml\Order\View\Items
{
    /**
     * Retrieve order items collection
     *
     * @return Collection
     */
    public function getItemsCollection()
    {
        $items = [];
        $itemCollection = $this->getOrder()->getOrder()->getItemsCollection();
        $vendorOrderId = $this->getOrder()->getId();
        foreach ($itemCollection as $item) {
            if ($item->getVendorOrderId() == $vendorOrderId) {
                $items[] = $item;
            }
        }

        return $items;
    }
}
