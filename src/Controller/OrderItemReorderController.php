<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\OrderItemReorderService;

class OrderItemReorderController extends AbstractController
{
    public function __construct(
    private OrderItemReorderService $orderItemReorderService,
    ) {}

    #[IsGranted('ROLE_USER')]
    #[Route(path: '/{_locale}/account/order-items/{id}/reorder', name: 'sylius_shop_account_order_item_reorder', methods: ['POST'])]
    public function reorder(Request $request, int $id): RedirectResponse
    {
        [$success, $message, $route, $params] = $this->orderItemReorderService->reorder(
            $id,
            $request->request->get('_csrf_token'),
            fn($id, $token) => $this->isCsrfTokenValid($id, $token)
        );
        
        $this->addFlash($success ? 'success' : 'error', $message);
        return $this->redirectToRoute($route, $params);
    }
}