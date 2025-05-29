<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;


class OrderItemReorderController extends AbstractController{


     #[Route(path: '/{_locale}/account/order-items/{id}/reorder', name: 'sylius_shop_account_order_item_reorder', methods: ['POST'])]
    public function reorder(Request $request, int $id): RedirectResponse
    {
        dd('in controller');
    }

}