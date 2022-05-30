<?php

namespace App\Message;

final class GetShopifyProducts
{
    /*
     * Add whatever properties and methods you need
     * to hold the data for this message class.
     */

    private $shopId;

    public function __construct(int $shopId)
    {
        $this->shopId = $shopId;
    }

    public function getShopId(): int
    {
       return $this->shopId;
    }
}
