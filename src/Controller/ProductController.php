<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/product')]
class ProductController extends AbstractController
{
    #[Route('/shopify', name: 'app_product_shopify')]
    public function index(Request $request, ProductRepository $productRepository): JsonResponse
    {
        $shopId = $request->get('shop_id');
        if (empty($shopId)) {
            return $this->json([]);
        }

        $content = $request->getContent();
        $data = json_decode($content, true);
        if (empty($data['id'])) {
            return $this->json([]);
        }

        $shopifyId = $data['id'];
        $product = $productRepository->findOneBy([
            'shopify_id' => $shopifyId
        ]);

        $date = new \DateTimeImmutable($data['updated_at']);
        $price = 0;
        if (isset($data['variants'][0]['price'])) {
            $price = $data['variants'][0]['price'];
        }

        if (empty($product)) {
            $product = new Product();
            $product->setCreatedAt($date);
        }

        $product->setShopId($shopId);
        $product->setShopifyId($shopifyId);
        $product->setName($data['title']);
        $product->setPrice($price);
        $product->setUrl($data['handle']);
        $product->setUpdatedAt($date);

        $productRepository->add($product, true);

        return $this->json([
            'success' => true
        ]);
    }
}
