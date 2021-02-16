<?php

/**
 *
 * @category Digitalriver
 * @package  Digitalriver_DrPay
 */

namespace Digitalriver\DrPay\Model;

use Digitalriver\DrPay\Model\DrConnectorFactory as ResourceDrConnector;
use Magento\Framework\Json\Helper\Data as JsonHelperData;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Sales\Model\OrderFactory;
use \Magento\Sales\Model\Order as Order;

/**
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DrConnectorRepository extends \Magento\Framework\Model\AbstractModel
{

    /**
     * @var ResourceDrConnector
     */
    protected $resource;

    /**
     *
     * @var type
     */
    protected $orderFactory;

    /**
     *
     * @var type
     */
    protected $jsonHelper;

    /**
     *
     * @param \Digitalriver\DrConnector\Model\DrConnectorFactory $resource
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     */
    public function __construct(
        ResourceDrConnector $resource,
        JsonHelperData $jsonHelper,
        OrderFactory $orderFactory
    ) {
        $this->orderFactory = $orderFactory;
        $this->resource = $resource;
        $this->jsonHelper = $jsonHelper;
    }

  /**
   * {@inheritdoc}
   */
    public function saveFulFillment($OrderLevelElectronicFulfillmentRequest)
    {

        $response = [];
        $lineItemIds = [];
        $electronicFulfillmentNotices = [(object) []];
        $requisitionId = $OrderLevelElectronicFulfillmentRequest['requisitionID'];
        $lineItemsIds = $OrderLevelElectronicFulfillmentRequest['lineItemLevelRequest'];
        $requestObj = $this->jsonHelper->jsonEncode($OrderLevelElectronicFulfillmentRequest);
        // Getting lineItemids
        if (is_array($lineItemsIds) && isset($lineItemsIds['quantity'])) {
            $lineItemIds[] = ['qty' => $lineItemsIds['quantity'],'lineitemid'=>$lineItemsIds['lineItemID']];
        } else {
            foreach ($lineItemsIds as $lineItemid) {
                if (is_array($lineItemid)) {
                      $lineItemIds[] = ['qty' => $lineItemid['quantity'],'lineitemid'=>$lineItemid['lineItemID']];
                }
            }
        }
        $data = [ 'requisition_id' => $requisitionId, 'request_obj' => $requestObj, 'line_item_ids'=> $this->jsonHelper->jsonEncode($lineItemIds)];
        try {
            if ($requisitionId) {
                $order = $this->orderFactory->create()->load($requisitionId, 'dr_order_id');
                if ($order->getId() && $order->getStatus() != \Magento\Sales\Model\Order::STATE_CANCELED) {                   
                    $model = $this->resource->create();
                    $model->load($order->getDrOrderId(), 'requisition_id');
                    if (!$model->getId() || $order->getDrOrderState() != "Submitted") {
						 if($order->getDrOrderState() != "Submitted"){ 
							//update order status to processing as OFI means payment received
							$order->setDrOrderState("Submitted"); 
							$order->setState(Order::STATE_PROCESSING); 
							$order->setStatus(Order::STATE_PROCESSING);
							$order->save();
						}
                        $model->setData($data);
                        $model->save();
                        $response = ['ElectronicFulfillmentResponse' => [
                                "responseMessage" => "The request has been successfully processed by Magento",
                                "successful" => "true",
                                "isAutoRetriable" => "false",
                                "electronicFulfillmentNotices" => $electronicFulfillmentNotices
                            ]
                        ];
                    } else {
                        $response = ['ElectronicFulfillmentResponse' => [
                                "responseMessage" => "The request has already saved in Magento",
                                "successful" => "false",
                                "isAutoRetriable" => "false",
                                "electronicFulfillmentNotices" => $electronicFulfillmentNotices
                            ]
                        ];
                    }
                }
            } else {
                $response = ['ElectronicFulfillmentResponse' => [
                        "responseMessage" => "Please Provide the requisitionID.",
                        "successful" => "false",
                        "isAutoRetriable" => "false",
                        "electronicFulfillmentNotices" => $electronicFulfillmentNotices
                    ]
                ];
            }
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }

        return $response;
    }
}
