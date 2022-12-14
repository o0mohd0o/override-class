<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsSales\Model\Order\Invoice\Total;

use Magento\Sales\Model\Order\Invoice;

/**
 * Discount invoice
 */
class Discount extends \Magento\Sales\Model\Order\Invoice\Total\Discount
{
    /**
     * Collect invoice
     *
     * @param Invoice $invoice
     * @return $this
     */
    public function collect(Invoice $invoice)
    {
        $invoice->setDiscountAmount(0);
        $invoice->setBaseDiscountAmount(0);

        $totalDiscountAmount = 0;
        $baseTotalDiscountAmount = 0;
        
        /**
         * Checking if shipping discount was added in previous invoices.
         * So basically if we have invoice with positive discount and it
         * was not canceled we don't add shipping discount to this one.
         */
        if ($this->isShippingDiscount($invoice)) {
            $vendorOrder = $invoice->getVendorOrder();
            if ($vendorOrder && $vendorOrder->getId()) {
                $totalDiscountAmount = $totalDiscountAmount + $vendorOrder->getShippingDiscountAmount();
                $baseTotalDiscountAmount = $baseTotalDiscountAmount +
                    $vendorOrder->getBaseShippingDiscountAmount();
            } else {
                $totalDiscountAmount = $totalDiscountAmount + $invoice->getOrder()->getShippingDiscountAmount();
                $baseTotalDiscountAmount = $baseTotalDiscountAmount +
                    $invoice->getOrder()->getBaseShippingDiscountAmount();
            }
        }

        /** @var $item \Magento\Sales\Model\Order\Invoice\Item */
        foreach ($invoice->getAllItems() as $item) {
            $orderItem = $item->getOrderItem();
            if ($orderItem->isDummy()) {
                continue;
            }

            $orderItemDiscount = (double)$orderItem->getDiscountAmount();
            $baseOrderItemDiscount = (double)$orderItem->getBaseDiscountAmount();
            $orderItemQty = $orderItem->getQtyOrdered();

            if ($orderItemDiscount && $orderItemQty) {
                /**
                 * Resolve rounding problems
                 */
                $discount = $orderItemDiscount - $orderItem->getDiscountInvoiced();
                $baseDiscount = $baseOrderItemDiscount - $orderItem->getBaseDiscountInvoiced();
                if (!$item->isLast()) {
                    $activeQty = $orderItemQty - $orderItem->getQtyInvoiced();
                    $discount = $invoice->roundPrice($discount / $activeQty * $item->getQty(), 'regular', true);
                    $baseDiscount = $invoice->roundPrice($baseDiscount / $activeQty * $item->getQty(), 'base', true);
                }

                $item->setDiscountAmount($discount);
                $item->setBaseDiscountAmount($baseDiscount);

                $totalDiscountAmount += $discount;
                $baseTotalDiscountAmount += $baseDiscount;
            }
        }

        $invoice->setDiscountAmount(-$totalDiscountAmount);
        $invoice->setBaseDiscountAmount(-$baseTotalDiscountAmount);

        $grandTotal = $invoice->getGrandTotal() - $totalDiscountAmount < 0.0001
            ? 0 : $invoice->getGrandTotal() - $totalDiscountAmount;
        $baseGrandTotal = $invoice->getBaseGrandTotal() - $baseTotalDiscountAmount < 0.0001
            ? 0 : $invoice->getBaseGrandTotal() - $baseTotalDiscountAmount;

        $invoice->setRealGrandTotal($invoice->getGrandTotal() - $totalDiscountAmount);
        $invoice->setRealBaseGrandTotal($invoice->getBaseGrandTotal() - $baseTotalDiscountAmount);

        $invoice->setGrandTotal($grandTotal);
        $invoice->setBaseGrandTotal($baseGrandTotal);
        return $this;
    }

    /**
     * Checking if shipping discount was added in previous invoices.
     *
     * @param Invoice $invoice
     * @return bool
     */
    private function isShippingDiscount(Invoice $invoice): bool
    {
        $addShippingDiscount = true;
        $vendorOrder = $invoice->getVendorOrder();
        if ($vendorOrder && $vendorOrder->getId()) {
            foreach ($vendorOrder->getInvoiceCollection() as $previousInvoice) {
                if ($previousInvoice->getDiscountAmount()) {
                    $addShippingDiscount = false;
                }
            }
        }
        else {
            foreach ($invoice->getOrder()->getInvoiceCollection() as $previousInvoice) {
                if ($previousInvoice->getDiscountAmount()) {
                    $addShippingDiscount = false;
                }
            }
        }
        return $addShippingDiscount;
    }
}
