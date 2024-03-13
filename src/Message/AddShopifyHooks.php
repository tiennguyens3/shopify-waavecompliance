<?php

namespace App\Message;

final class AddShopifyHooks
{
    /*
     * Add whatever properties and methods you need
     * to hold the data for this message class.
     */

    private $shopId;

    private $webhookUrl;

    public function __construct(int $shopId, string $webhookUrl)
    {
        $this->shopId = $shopId;
        $this->webhookUrl = $webhookUrl;
    }

    public function getShopId(): int
    {
       return $this->shopId;
    }

    public function getWebhookUrl(): string
    {
       return $this->webhookUrl;
    }
}
