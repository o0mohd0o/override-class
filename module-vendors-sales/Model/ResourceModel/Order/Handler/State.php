<?php

namespace Vnecoms\VendorsSales\Model\ResourceModel\Order\Handler;

use Vnecoms\VendorsSales\Model\Order;
use Magento\Sales\Model\Order as BaseOrder;

/**
 * Class State
 */
class State
{
    /**
     * @param Order $order
     * @param BaseOrder $mainOrder
     * @return $this
     */
    public function check(Order $order, BaseOrder $mainOrder)
    {
        $currentState = $order->getState();
        if ($currentState == BaseOrder::STATE_NEW  && $mainOrder->getIsInProcess() && $mainOrder->getIsInProcess() == $order->getId()) {
            $order->setState(BaseOrder::STATE_PROCESSING)
                ->setStatus($mainOrder->getConfig()->getStateDefaultStatus(BaseOrder::STATE_PROCESSING));
            $currentState = BaseOrder::STATE_PROCESSING;
        }

        if (!$order->isCanceled() && !$order->canUnhold() && !$order->canInvoice()) {
            if (in_array($currentState, [BaseOrder::STATE_PROCESSING, BaseOrder::STATE_COMPLETE])
                && !$order->canCreditmemo()
                && !$order->canShip()
            ) {
                $order->setState(BaseOrder::STATE_CLOSED)
                    ->setStatus($mainOrder->getConfig()->getStateDefaultStatus(BaseOrder::STATE_CLOSED));
            } elseif ($currentState === BaseOrder::STATE_PROCESSING && !$order->canShip()) {
                $order->setState(BaseOrder::STATE_COMPLETE)
                    ->setStatus($mainOrder->getConfig()->getStateDefaultStatus(BaseOrder::STATE_COMPLETE));
            }
        }
        return $this;
    }
}
