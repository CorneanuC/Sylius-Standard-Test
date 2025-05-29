<?php

namespace App\Tests\Service;

use App\Service\OrderItemReorderService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
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
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class OrderItemReorderServiceTest extends TestCase
{
    public function testReorderReturnsErrorIfCsrfInvalid()
    {
        $service = new OrderItemReorderService(
            $this->createMock(OrderItemRepositoryInterface::class),
            $this->createMock(CartContextInterface::class),
            $this->createMock(OrderItemQuantityModifierInterface::class),
            $this->createMock(OrderModifierInterface::class),
            $this->createMock(OrderProcessorInterface::class),
            $this->createMock(AvailabilityCheckerInterface::class),
            $this->createMock(AuthorizationCheckerInterface::class),
            $this->createMock(FactoryInterface::class),
            $this->createMock(OrderRepositoryInterface::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(TokenStorageInterface::class),
        );
        $result = $service->reorder(1, 'bad_token', fn() => false);
        $this->assertFalse($result[0]);
        $this->assertStringContainsString('Invalid CSRF token', $result[1]);
    }

    public function testOriginalItemNotFound()
    {
        $repo = $this->createMock(OrderItemRepositoryInterface::class);
        $repo->method('find')->willReturn(null);
        $service = new OrderItemReorderService(
            $repo,
            $this->createMock(CartContextInterface::class),
            $this->createMock(OrderItemQuantityModifierInterface::class),
            $this->createMock(OrderModifierInterface::class),
            $this->createMock(OrderProcessorInterface::class),
            $this->createMock(AvailabilityCheckerInterface::class),
            $this->createMock(AuthorizationCheckerInterface::class),
            $this->createMock(FactoryInterface::class),
            $this->createMock(OrderRepositoryInterface::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(TokenStorageInterface::class),
        );
        $result = $service->reorder(1, 'token', fn() => true);
        $this->assertFalse($result[0]);
        $this->assertStringContainsString('Original item not found.', $result[1]);
    }

    public function testOrderNotFound()
    {
        $item = $this->createMock(OrderItemInterface::class);
        $item->method('getOrder')->willReturn(null);
        $repo = $this->createMock(OrderItemRepositoryInterface::class);
        $repo->method('find')->willReturn($item);
        $service = new OrderItemReorderService(
            $repo,
            $this->createMock(CartContextInterface::class),
            $this->createMock(OrderItemQuantityModifierInterface::class),
            $this->createMock(OrderModifierInterface::class),
            $this->createMock(OrderProcessorInterface::class),
            $this->createMock(AvailabilityCheckerInterface::class),
            $this->createMock(AuthorizationCheckerInterface::class),
            $this->createMock(FactoryInterface::class),
            $this->createMock(OrderRepositoryInterface::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(TokenStorageInterface::class),
        );
        $result = $service->reorder(1, 'token', fn() => true);
        $this->assertFalse($result[0]);
        $this->assertStringContainsString('Order not found.', $result[1]);
    }

    public function testProductVariantNotFound()
    {
        $order = $this->createMock(\Sylius\Component\Core\Model\OrderInterface::class);
        $order->method('getId')->willReturn(1);
        $item = $this->createMock(OrderItemInterface::class);
        $item->method('getOrder')->willReturn($order);
        $item->method('getVariant')->willReturn(null);
        $repo = $this->createMock(OrderItemRepositoryInterface::class);
        $repo->method('find')->willReturn($item);
        $service = new OrderItemReorderService(
            $repo,
            $this->createMock(CartContextInterface::class),
            $this->createMock(OrderItemQuantityModifierInterface::class),
            $this->createMock(OrderModifierInterface::class),
            $this->createMock(OrderProcessorInterface::class),
            $this->createMock(AvailabilityCheckerInterface::class),
            $this->createMock(AuthorizationCheckerInterface::class),
            $this->createMock(FactoryInterface::class),
            $this->createMock(OrderRepositoryInterface::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(TokenStorageInterface::class),
        );
        $result = $service->reorder(1, 'token', fn() => true);
        $this->assertFalse($result[0]);
        $this->assertStringContainsString('Product variant not found.', $result[1]);
    }

    public function testProductNotAvailableInDesiredQuantity()
    {
        $variant = $this->createMock(\Sylius\Component\Core\Model\ProductVariantInterface::class);
        $order = $this->createMock(\Sylius\Component\Core\Model\OrderInterface::class);
        $order->method('getId')->willReturn(1);
        $item = $this->createMock(OrderItemInterface::class);
        $item->method('getOrder')->willReturn($order);
        $item->method('getVariant')->willReturn($variant);
        $item->method('getQuantity')->willReturn(5);
        $repo = $this->createMock(OrderItemRepositoryInterface::class);
        $repo->method('find')->willReturn($item);
        $availabilityChecker = $this->createMock(AvailabilityCheckerInterface::class);
        $availabilityChecker->method('isStockSufficient')->willReturn(false);
        $service = new OrderItemReorderService(
            $repo,
            $this->createMock(CartContextInterface::class),
            $this->createMock(OrderItemQuantityModifierInterface::class),
            $this->createMock(OrderModifierInterface::class),
            $this->createMock(OrderProcessorInterface::class),
            $availabilityChecker,
            $this->createMock(AuthorizationCheckerInterface::class),
            $this->createMock(FactoryInterface::class),
            $this->createMock(OrderRepositoryInterface::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(TokenStorageInterface::class),
        );
        $result = $service->reorder(1, 'token', fn() => true);
        $this->assertFalse($result[0]);
        $this->assertStringContainsString('The product is not available in the desired quantity.', $result[1]);
    }
}
