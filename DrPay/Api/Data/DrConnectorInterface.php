<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 * @Module: Digitalriver_DrPay
 */

namespace Digitalriver\DrPay\Api\Data;

/**
 * @api
 * @since 100.0.2
 */
interface DrConnectorInterface {

    const ID = 'entity_id';
    const REQUESTOBJ = 'request_obj';
    const REQUISITIONID = 'requisition_id';

    /**
     * Get ID.
     * @return int|null
     */
    public function getId();

    /**
     * 
     * @return string|null
     */
    public function getRequisitionId();

    /**
     * set $requisitionId.
     * @param string $requisitionId
     * @return $this
     */
    public function setRequisitionId($requisitionId);

    /**
     * Get requestObj.
     * @return string|null
     */
    public function getRequestObj();

    /**
     * set $requestObj.
     * @param string $requestObj
     * @return $this
     */
    public function setRequestObj($requestObj);
}
