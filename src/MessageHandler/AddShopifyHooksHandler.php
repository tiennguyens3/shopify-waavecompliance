<?php

namespace App\MessageHandler;

use App\Message\AddShopifyHooks;
use App\Repository\ShopRepository;
use Shopify\Webhooks\Topics;
use Shopify\Webhooks\Registry;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class AddShopifyHooksHandler implements MessageHandlerInterface
{
    private $shopRepository;

    public function __construct(ShopRepository $shopRepository)
    {
        $this->shopRepository = $shopRepository;
    }

    public function __invoke(AddShopifyHooks $message)
    {
        $shopId = $message->getShopId();
        $shop = $this->shopRepository->find($shopId);
        if (empty($shop)) {
            return;
        }

        $webhookUrl = $message->getWebhookUrl();

        $topics = [
            Topics::COLLECTIONS_CREATE,
            Topics::COLLECTIONS_UPDATE,
            Topics::PRODUCTS_CREATE,
            Topics::PRODUCTS_UPDATE
        ];

        foreach ($topics as $topic) {
            Registry::register(
                $webhookUrl,
                $topic,
                $shop->getDomain(),
                $shop->getAccessToken()
            );
        }
    }
}
