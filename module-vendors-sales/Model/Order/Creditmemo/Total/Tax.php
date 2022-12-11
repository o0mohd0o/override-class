<?php
/**
 * Copyright © Vnecoms, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsSales\Model\Order\Creditmemo\Total;

use Magento\Framework\App\ObjectManager;

class Tax extends \Magento\Sales\Model\Order\Creditmemo\Total\Tax
{
    /**
     * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @return $this
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function collect(\Magento\Sales\Model\Order\Creditmemo $creditmemo)
    {
        $vendorOrder = $creditmemo->getVendorOrder();
        if (!$vendorOrder) {
            $vendorOrderId = $creditmemo->getVendorOrderId();
            $vendorOrder = ObjectManager::getInstance()->create("\Vnecoms\VendorsSales\Model\Order")
                ->load($vendorOrderId);
        }

        if ($vendorOrder->getId()) {
            $shippingTaxAmount = 0;
            $baseShippingTaxAmount = 0;
            $totalTax = 0;
            $baseTotalTax = 0;
            $totalDiscountTaxCompensation = 0;
            $baseTotalDiscountTaxCompensation = 0;

            $order = $creditmemo->getOrder();

            /** @var $item \Magento\Sales\Model\Order\Creditmemo\Item */
            foreach ($creditmemo->getAllItems() as $item) {
                $orderItem = $item->getOrderItem();
                if ($orderItem->isDummy() || $item->getQty() <= 0) {
                    continue;
                }
                $orderItemTax = (double)$orderItem->getTaxInvoiced();
                $baseOrderItemTax = (double)$orderItem->getBaseTaxInvoiced();
                $orderItemQty = (double)$orderItem->getQtyInvoiced();

                if ($orderItemTax && $orderItemQty) {
                    /**
                     * Check item tax amount
                     */

                    $tax = $orderItemTax - $orderItem->getTaxRefunded();
                    $baseTax = $baseOrderItemTax - $orderItem->getBaseTaxRefunded();
                    $discountTaxCompensation = $orderItem->getDiscountTaxCompensationInvoiced() -
                        $orderItem->getDiscountTaxCompensationRefunded();
                    $baseDiscountTaxCompensation = $orderItem->getBaseDiscountTaxCompensationInvoiced() -
                        $orderItem->getBaseDiscountTaxCompensationRefunded();
                    if (!$item->isLast()) {
                        $availableQty = $orderItemQty - $orderItem->getQtyRefunded();
                        $tax = $creditmemo->roundPrice($tax / $availableQty * $item->getQty());
                        $baseTax = $creditmemo->roundPrice($baseTax / $availableQty * $item->getQty(), 'base');
                        $discountTaxCompensation =
                            $creditmemo->roundPrice($discountTaxCompensation / $availableQty * $item->getQty());
                        $baseDiscountTaxCompensation =
                            $creditmemo->roundPrice($baseDiscountTaxCompensation / $availableQty * $item->getQty(), 'base');
                    }

                    $item->setTaxAmount($tax);
                    $item->setBaseTaxAmount($baseTax);
                    $item->setDiscountTaxCompensationAmount($discountTaxCompensation);
                    $item->setBaseDiscountTaxCompensationAmount($baseDiscountTaxCompensation);

                    $totalTax += $tax;
                    $baseTotalTax += $baseTax;
                    $totalDiscountTaxCompensation += $discountTaxCompensation;
                    $baseTotalDiscountTaxCompensation += $baseDiscountTaxCompensation;
                }
            }

            $isPartialShippingRefunded = false;
            $baseOrderShippingAmount = (float)$vendorOrder->getBaseShippingAmount();
            if ($invoice = $creditmemo->getInvoice()) {
                //recalculate tax amounts in case if refund shipping value was changed
                if ($baseOrderShippingAmount && $creditmemo->getBaseShippingAmount() !== null) {
                    $taxFactor = $creditmemo->getBaseShippingAmount() / $baseOrderShippingAmount;
                    $shippingTaxAmount = $invoice->getShippingTaxAmount() * $taxFactor;
                    $baseShippingTaxAmount = $invoice->getBaseShippingTaxAmount() * $taxFactor;
                    $totalDiscountTaxCompensation += $invoice->getShippingDiscountTaxCompensationAmount() * $taxFactor;
                    $baseTotalDiscountTaxCompensation +=
                        $invoice->getBaseShippingDiscountTaxCompensationAmnt() * $taxFactor;
                    $shippingDiscountTaxCompensationAmount =
                        $invoice->getShippingDiscountTaxCompensationAmount() * $taxFactor;
                    $baseShippingDiscountTaxCompensationAmount =
                        $invoice->getBaseShippingDiscountTaxCompensationAmnt() * $taxFactor;
                    $shippingTaxAmount = $creditmemo->roundPrice($shippingTaxAmount);
                    $baseShippingTaxAmount = $creditmemo->roundPrice($baseShippingTaxAmount, 'base');
                    $totalDiscountTaxCompensation = $creditmemo->roundPrice($totalDiscountTaxCompensation);
                    $baseTotalDiscountTaxCompensation = $creditmemo->roundPrice($baseTotalDiscountTaxCompensation, 'base');
                    $shippingDiscountTaxCompensationAmount =
                        $creditmemo->roundPrice($shippingDiscountTaxCompensationAmount);
                    $baseShippingDiscountTaxCompensationAmount =
                        $creditmemo->roundPrice($baseShippingDiscountTaxCompensationAmount, 'base');
                    if ($taxFactor < 1 && $invoice->getShippingTaxAmount() > 0) {
                        $isPartialShippingRefunded = true;
                    }
                    $totalTax += $shippingTaxAmount;
                    $baseTotalTax += $baseShippingTaxAmount;
                }
            } else {
                $orderShippingAmount = $vendorOrder->getShippingAmount();

                $baseOrderShippingRefundedAmount = $vendorOrder->getBaseShippingRefunded();

                $shippingTaxAmount = 0;
                $baseShippingTaxAmount = 0;
                $shippingDiscountTaxCompensationAmount = 0;
                $baseShippingDiscountTaxCompensationAmount = 0;

                $shippingDelta = $baseOrderShippingAmount - $baseOrderShippingRefundedAmount;

                if ($shippingDelta > $creditmemo->getBaseShippingAmount()) {
                    $part = $creditmemo->getShippingAmount() / $orderShippingAmount;
                    $basePart = $creditmemo->getBaseShippingAmount() / $baseOrderShippingAmount;
                    $shippingTaxAmount = $vendorOrder->getShippingTaxAmount() * $part;
                    $baseShippingTaxAmount = $vendorOrder->getBaseShippingTaxAmount() * $basePart;
                    $shippingDiscountTaxCompensationAmount = $vendorOrder->getShippingDiscountTaxCompensationAmount() * $part;
                    $baseShippingDiscountTaxCompensationAmount =
                        $vendorOrder->getBaseShippingDiscountTaxCompensationAmnt() * $basePart;
                    $shippingTaxAmount = $creditmemo->roundPrice($shippingTaxAmount);
                    $baseShippingTaxAmount = $creditmemo->roundPrice($baseShippingTaxAmount, 'base');
                    $shippingDiscountTaxCompensationAmount =
                        $creditmemo->roundPrice($shippingDiscountTaxCompensationAmount);
                    $baseShippingDiscountTaxCompensationAmount =
                        $creditmemo->roundPrice($baseShippingDiscountTaxCompensationAmount, 'base');
                    if ($part < 1 && $vendorOrder->getShippingTaxAmount() > 0) {
                        $isPartialShippingRefunded = true;
                    }
                } elseif ($shippingDelta == $creditmemo->getBaseShippingAmount()) {
                    $shippingTaxAmount = $vendorOrder->getShippingTaxAmount() - $vendorOrder->getShippingTaxRefunded();
                    $baseShippingTaxAmount = $vendorOrder->getBaseShippingTaxAmount() - $vendorOrder->getBaseShippingTaxRefunded();
                    $shippingDiscountTaxCompensationAmount = $vendorOrder->getShippingDiscountTaxCompensationAmount() -
                        $vendorOrder->getShippingDiscountTaxCompensationRefunded();
                    $baseShippingDiscountTaxCompensationAmount = $vendorOrder->getBaseShippingDiscountTaxCompensationAmnt() -
                        $vendorOrder->getBaseShippingDiscountTaxCompensationRefunded();
                }

                $totalTax += $shippingTaxAmount;
                $baseTotalTax += $baseShippingTaxAmount;
                $totalDiscountTaxCompensation += $shippingDiscountTaxCompensationAmount;
                $baseTotalDiscountTaxCompensation += $baseShippingDiscountTaxCompensationAmount;
            }

            $allowedTax = $vendorOrder->getTaxInvoiced() - $vendorOrder->getTaxRefunded() - $creditmemo->getTaxAmount();
            $allowedBaseTax = $vendorOrder->getBaseTaxInvoiced() - $vendorOrder->getBaseTaxRefunded() - $creditmemo->getBaseTaxAmount();


            $allowedDiscountTaxCompensation = $vendorOrder->getDiscountTaxCompensationInvoiced() +
                $vendorOrder->getShippingDiscountTaxCompensationAmount() -
                $vendorOrder->getDiscountTaxCompensationRefunded() -
                $vendorOrder->getShippingDiscountTaxCompensationRefunded() -
                $creditmemo->getDiscountTaxCompensationAmount() -
                $creditmemo->getShippingDiscountTaxCompensationAmount();
            $allowedBaseDiscountTaxCompensation = $vendorOrder->getBaseDiscountTaxCompensationInvoiced() +
                $vendorOrder->getBaseShippingDiscountTaxCompensationAmnt() -
                $vendorOrder->getBaseDiscountTaxCompensationRefunded() -
                $vendorOrder->getBaseShippingDiscountTaxCompensationRefunded() -
                $creditmemo->getBaseShippingDiscountTaxCompensationAmnt() -
                $creditmemo->getBaseDiscountTaxCompensationAmount();

            if ($creditmemo->isLast() && !$isPartialShippingRefunded) {
                $totalTax = $allowedTax;
                $baseTotalTax = $allowedBaseTax;
                $totalDiscountTaxCompensation = $allowedDiscountTaxCompensation;
                $baseTotalDiscountTaxCompensation = $allowedBaseDiscountTaxCompensation;
            } else {
                $totalTax = min($allowedTax, $totalTax);
                $baseTotalTax = min($allowedBaseTax, $baseTotalTax);
                $totalDiscountTaxCompensation =
                    min($allowedDiscountTaxCompensation, $totalDiscountTaxCompensation);
                $baseTotalDiscountTaxCompensation =
                    min($allowedBaseDiscountTaxCompensation, $baseTotalDiscountTaxCompensation);
            }

            $creditmemo->setTaxAmount($creditmemo->getTaxAmount() + $totalTax);
            $creditmemo->setBaseTaxAmount($creditmemo->getBaseTaxAmount() + $baseTotalTax);
            $creditmemo->setDiscountTaxCompensationAmount($totalDiscountTaxCompensation);
            $creditmemo->setBaseDiscountTaxCompensationAmount($baseTotalDiscountTaxCompensation);

            $creditmemo->setShippingTaxAmount($shippingTaxAmount);
            $creditmemo->setBaseShippingTaxAmount($baseShippingTaxAmount);

            $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $totalTax + $totalDiscountTaxCompensation);

            $creditmemo->setBaseGrandTotal(
                $creditmemo->getBaseGrandTotal() +
                $baseTotalTax + $baseTotalDiscountTaxCompensation
            );

            return $this;
        }
        return parent::collect($creditmemo);
    }
}
