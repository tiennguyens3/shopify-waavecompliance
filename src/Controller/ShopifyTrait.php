<?php

namespace App\Controller;

use Shopify\Auth\FileSessionStorage;
use Shopify\Context;

trait ShopifyTrait
{
    public function shopifyInitialize(): void
    {
        $path = $this->getParameter('kernel.project_dir') . '/var/sessions/' . $_ENV['APP_ENV'];
        Context::initialize(
            $_ENV['SHOPIFY_API_KEY'],
            $_ENV['SHOPIFY_API_SECRET'],
            $_ENV['SHOPIFY_APP_SCOPES'],
            $_ENV['SHOPIFY_APP_HOST_NAME'],
            new FileSessionStorage($path),
            $_ENV['SHOPIFY_API_VERSION']
        );
    }

    public function shopifySessionIsOnline(): bool
    {
        return true;
    }
}
