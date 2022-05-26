<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Routing\Annotation\Route;
use Shopify\Auth\OAuth;
use Shopify\Auth\OAuthCookie;
use App\Repository\ShopRepository;
use App\Entity\Shop;

class LoginController extends AbstractController
{
    use ShopifyTrait;

    #[Route('/login', name: 'app_login')]
    public function index(Request $request): Response
    {
        $this->shopifyInitialize();

        if ($request->isMethod('POST')) {
            $shop = $request->get('shop');
            $redirectPath = '/callback';
            $isOnline = $this->shopifySessionIsOnline();

            $response = new RedirectResponse($redirectPath);

            $url = OAuth::begin($shop, $redirectPath, $isOnline, function(OAuthCookie $cookie) use ($response) {
                $response->headers->setCookie(
                    Cookie::create(
                        $cookie->getName(),
                        $cookie->getValue(),
                        0
                    )
                );
                return true;
            });


            $response->setTargetUrl($url);

            return $response;
        }

        return $this->render('login/index.html.twig');
    }

    #[Route('/callback', name: 'app_callback')]
    public function callback(Request $request, ShopRepository $shopRepository): Response
    {
        $this->shopifyInitialize();

        // Shopify callback
        $cookies = $request->cookies->all();
        $query = $request->query->all();

        $response = new Response();

        try {
            $session = OAuth::callback($cookies, $query, function(OAuthCookie $cookie) use ($response) {
                $response->headers->setCookie(
                    Cookie::create(
                        $cookie->getName(),
                        $cookie->getValue(),
                        0
                    )
                );
                return true;
            });
        } catch (\Exception $e) {
            return $this->redirectToRoute('app_login', [], Response::HTTP_SEE_OTHER);
        }

        $shopDomain = $session->getShop();
        $accessToken = $session->getAccessToken();

        $shop = $shopRepository->findOneBy(['domain' => $shopDomain]);
        if (empty($shop)) {
            $shop = new Shop();
            $shop->setCreatedAt(new \DateTimeImmutable());
        }

        $shop->setDomain($shopDomain);
        $shop->setAccessToken($accessToken);
        $shop->setUpdatedAt(new \DateTimeImmutable());

        $shopRepository->add($shop, true);

        return $this->redirectToRoute('app_home');
    }
}
