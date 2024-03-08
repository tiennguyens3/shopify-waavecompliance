<?php

namespace App\MessageHandler;

use App\Entity\Shop;
use App\Message\AddShopifyHooks;
use Shopify\Clients\Rest;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class AddShopifyHooksHandler implements MessageHandlerInterface
{
    private $entityManager;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->entityManager = $doctrine->getManager();
    }

    public function __invoke(AddShopifyHooks $message)
    {
        $shopId = $message->getShopId();

        $shop = $this->entityManager->getRepository(Shop::class)->find($shopId);

        if (empty($shop)) {
            return;
        }

        $client = new Rest($shop->getDomain(), $shop->getAccessToken());

        $hooks = [
            'collections/create' => $message->getCategoryUrl(),
            'collections/update' => $message->getCategoryUrl(),
            'products/create' => $message->getProductUrl(),
            'products/update' => $message->getProductUrl()
        ];

        foreach ($hooks as $key => $value) {
            $body = [
                'webhook' => [
                    'topic' => $key,
                    'address' => $value
                ]
            ];

            //$client->post('webhook', $body);
        }
    }
}
