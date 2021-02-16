<?php

/**
 *
 * @category Digitalriver
 * @package  Digitalriver_DrPay
 */

namespace Digitalriver\DrPay\Model;

class DrConnector extends \Magento\Framework\Model\AbstractModel
{

    protected function _construct()
    {
        $this->_init('Digitalriver\DrPay\Model\ResourceModel\DrConnector');
    }
}
