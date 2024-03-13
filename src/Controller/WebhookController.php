<?php

namespace App\Controller;

use App\Handler\ProductHandler;
use App\Handler\CategoryHandler;
use Shopify\Webhooks\Registry;
use Shopify\Webhooks\Topics;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

#[Route('/webhook')]
class WebhookController extends AbstractController
{
    use ShopifyTrait;

    #[Route('/{shopId}', name: 'app_webhook_callback')]
    public function index(Request $request, $shopId, ProductHandler $productHandler, CategoryHandler $categoryHandler, LoggerInterface $logger)
    {
        try {
            $this->shopifyInitialize();

            Registry::addHandler(Topics::COLLECTIONS_CREATE, $categoryHandler);
            Registry::addHandler(Topics::COLLECTIONS_UPDATE, $categoryHandler);
            Registry::addHandler(Topics::PRODUCTS_CREATE, $productHandler);
            Registry::addHandler(Topics::PRODUCTS_UPDATE, $productHandler);

            $headers = $request->headers->all();
            $rawBody = file_get_contents("php://input");
            $response = Registry::process($headers, $rawBody);

            if ($response->isSuccess()) {
                $logger->info("Responded to webhook!");
            } else {
                $logger->error("Webhook handler failed with message: " . $response->getErrorMessage());
            }
        } catch (\Exception $error) {
            $logger->error($error);
        }

        return new Response();
    }
}