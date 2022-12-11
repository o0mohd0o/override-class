<?php

namespace Vnecoms\VendorsSales\Controller\Adminhtml\Sales\Order;

class AddComment extends \Vnecoms\VendorsSales\Controller\Adminhtml\Sales\Order
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    protected $_aclResource = 'Vnecoms_VendorsSales::sales_order_action_comment';

    /**
     * Add order comment action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $vendorOrderId = $this->getRequest()->getParam('vorder_id');

        $order = $this->_initOrder();

        if ($order) {
            try {
                $data = $this->getRequest()->getPost('history');
                if (empty($data['comment'])) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('Please enter a comment.'));
                }

                $notify = isset($data['is_customer_notified']) ? $data['is_customer_notified'] : false;
                $visible = isset($data['is_visible_on_front']) ? $data['is_visible_on_front'] : false;

                $history = $order->addStatusHistoryComment($data['comment'], $data['status']);
                $history->setVendorId($order->getVendorId());
                $history->setVendorOrderStatus($vendorOrderId);
                $history->setIsVisibleOnFront($visible);
                $history->setIsCustomerNotified($notify);
                $history->save();
                $vendorOrder = $this->_coreRegistry->registry('vendor_order');
                $vendorOrder->setStatus($data['status'])->save();
                $comment = trim(strip_tags($data['comment']));

                $order->save();
                /** @var \Magento\Sales\Model\Order\Email\Sender\OrderCommentSender $orderCommentSender */
                $orderCommentSender = $this->_objectManager
                    ->create('Magento\Sales\Model\Order\Email\Sender\OrderCommentSender');

                $orderCommentSender->send($order->getOrder(), $notify, $comment);

                return $this->resultPageFactory->create();
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $response = ['error' => true, 'message' => $e->getMessage()];
            } catch (\Exception $e) {
                $response = ['error' => true, 'message' => __('We cannot add order history.')];
            }
            if (is_array($response)) {
                $resultJson = $this->resultJsonFactory->create();
                $resultJson->setData($response);
                return $resultJson;
            }
        }
        return $this->resultRedirectFactory->create()->setPath('vendors/sales_order/');
    }
}
