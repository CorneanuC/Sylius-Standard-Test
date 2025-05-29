<?php

namespace App\Service;

use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Repository\OrderItemRepositoryInterface;
use Sylius\Component\Order\Modifier\OrderModifierInterface;
use Sylius\Component\Order\Modifier\OrderItemQuantityModifierInterface;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Inventory\Checker\AvailabilityCheckerInterface;
use Sylius\Component\Order\Repository\OrderRepositoryInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;



class OrderItemReorderService
{
    public function __construct(
        private OrderItemRepositoryInterface $orderItemRepository,
        private CartContextInterface $cartContext,
        private OrderItemQuantityModifierInterface $orderItemQuantityModifier,
        private OrderModifierInterface $orderModifier,
        private OrderProcessorInterface $orderProcessor,
        private AvailabilityCheckerInterface $availabilityChecker,
        private AuthorizationCheckerInterface $authorizationChecker,
        private FactoryInterface $orderItemFactory,
        private OrderRepositoryInterface $orderRepository,
        private LoggerInterface $logger,
        private TokenStorageInterface $tokenStorage,
    ) {}

    /**
     * @return array [success(bool), message(string), redirectRoute(string), redirectParams(array)]
     */
    public function reorder(int $id, string $csrfToken, callable $isCsrfTokenValid): array
    {
        if (!$isCsrfTokenValid('reorder_item', $csrfToken)) {
            return [false, 'Invalid CSRF token.', 'sylius_shop_account_order_index', []];
        }

        $originalOrderItem = $this->orderItemRepository->find($id);
        if (null === $originalOrderItem || !$originalOrderItem instanceof OrderItemInterface) {
            return [false, 'Original item not found.', 'sylius_shop_account_order_index', []];
        }

        $order = $originalOrderItem->getOrder();
        if (null === $order) {
            return [false, 'Order not found.', 'sylius_shop_account_order_index', []];
        }

        $variant = $originalOrderItem->getVariant();
        if (null === $variant) {
            return [false, 'Product variant not found.', 'sylius_shop_account_order_show', ['id' => $order->getId()]];
        }

        $quantity = $originalOrderItem->getQuantity();
        if (!$this->availabilityChecker->isStockSufficient($variant, $quantity)) {
            return [false, 'The product is not available in the desired quantity.', 'sylius_shop_account_order_show', ['id' => $order->getId()]];
        }

        if (!$this->authorizationChecker->isGranted('ORDER_VIEW', $order)) {
            return [false, 'Access denied.', 'sylius_shop_account_order_index', []];
        }

        $cart = $this->cartContext->getCart();
        try {
            $newOrderItem = $this->orderItemFactory->createNew();
            $newOrderItem->setVariant($variant);
            $this->orderItemQuantityModifier->modify($newOrderItem, $quantity);

            $this->orderModifier->addToOrder($cart, $newOrderItem);
            $this->orderProcessor->process($cart);
            $this->orderRepository->add($cart);

            //logging the reorder action
            $user = $this->tokenStorage->getToken() ? $this->tokenStorage->getToken()->getUser() : null;
            $userEmail = ($user instanceof \Sylius\Component\Core\Model\ShopUserInterface) ? $user->getEmail() : null;

            $this->logger->info('Order item reordered', [
                'order_id' => $order->getId(),
                'order_item_id' => $originalOrderItem->getId(),
                'variant_id' => $variant->getId(),
                'quantity' => $quantity,
                'current_user' => $userEmail,
            ]);

            return [true, 'Product successfully added to cart.', 'sylius_shop_cart_summary', []];
        } catch (\Throwable $e) {
            $user = $this->tokenStorage->getToken() ? $this->tokenStorage->getToken()->getUser() : null;
            $userEmail = ($user instanceof \Sylius\Component\Core\Model\ShopUserInterface) ? $user->getEmail() : null;

            $this->logger->error('Failed to reorder item', [
                'error' => $e->getMessage(),
                'order_id' => $order->getId(),
                'order_item_id' => $originalOrderItem->getId(),
                'current_user' => $userEmail,
            ]);
            return [false, 'Could not add product to cart: ' . $e->getMessage(), 'sylius_shop_account_order_show', ['id' => $originalOrderItem->getId()]];
        }
    }
}
