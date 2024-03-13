<?php

namespace App\Controller;

use App\Repository\ShopRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/category')]
class CategoryController extends AbstractController
{
    #[Route('/{shopId}', name: 'app_category_index')]
    public function index(Request $request, $shopId, ShopRepository $shopRepository)
    {
        $shop = $shopRepository->find($shopId);
        if (empty($shop)) {
            return $this->json([]);
        }

        $data = [];
        $categories = $shop->getCategories()->toArray();
        foreach ($categories as $value) {
            $data[] = [
                'id' => $value->getId(),
                'name' => $value->getName()
            ];
        }

        return $this->json($data);
    }
}
