<?php

namespace SwedbankPay\Api\Service\Payment\Transaction\Resource\Response\Data;

use SwedbankPay\Api\Service\Resource\Data\ResponseInterface;

interface TransactionsResponseInterface extends ResponseInterface
{
    const PAYMENT = 'payment';

    /**
    * @return string
    */
    public function getPayment();
    
    /**
    * @param string $payment
    * @return $this
    */
    public function setPayment($payment);
}
