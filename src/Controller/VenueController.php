<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Routing\Annotation\Route;
use Shopify\Auth\FileSessionStorage;
use Shopify\Auth\OAuth;
use Shopify\Context;
use Shopify\Utils;
use App\Repository\ShopRepository;
use App\Repository\VenueRepository;
use App\Form\VenueType;
use App\Entity\Venue;

class VenueController extends AbstractController
{
    use ShopifyTrait;

    #[Route('/', name: 'app_home')]
    public function index(Request $request, ShopRepository $shopRepository, VenueRepository $venueRepository): Response
    {
        $this->shopifyInitialize();

        $cookies = $request->cookies->all();
        $isOnline = $this->shopifySessionIsOnline();

        $token = isset($cookies['token']) ? $cookies['token'] : '';
        $headers = ['Authorization' => "Bearer $token"];

        try {
            $session = Utils::loadCurrentSession($headers, $cookies, $isOnline);
        } catch(\Exception $e) {
            return $this->redirectToRoute('app_login', [], Response::HTTP_SEE_OTHER);
        }

        $shopDomain = $session->getShop();
        $accessToken = $session->getAccessToken();

        $shop = $shopRepository->findOneBy(['domain' => $shopDomain]);
        if (empty($shop)) {
            return $this->redirectToRoute('app_login', [], Response::HTTP_SEE_OTHER);
        }

        $venue = $shop->getVenue();
        if (empty($venue)) {
            $venue = new Venue();
            $venue->setCreatedAt(new \DateTimeImmutable());
        }

        $venue->setUpdatedAt(new \DateTimeImmutable());

        $form = $this->createForm(VenueType::class, $venue);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $shop->setVenue($venue);
            $venueRepository->add($venue, true);

            return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('venue/index.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
