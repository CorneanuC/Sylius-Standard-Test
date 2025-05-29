<?php

namespace App\Tests\Security\Voter;

use App\Security\Voter\OrderVoter;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class OrderVoterTest extends TestCase
{
    public function testOrderViewGrantedForMatchingUser()
    {
        $user = $this->createMock(ShopUserInterface::class);
        $customer = $this->createMock(CustomerInterface::class);
        $customer->method('getUser')->willReturn($user);
        $order = $this->createMock(OrderInterface::class);
        $order->method('getCustomer')->willReturn($customer);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $voter = new OrderVoter();
        $result = $voter->vote($token, $order, ['ORDER_VIEW']);
        $this->assertSame(Voter::ACCESS_GRANTED, $result);
    }
    public function testOrderViewDeniedForNonMatchingUser()
    {
        $user = $this->createMock(ShopUserInterface::class);
        $otherUser = $this->createMock(ShopUserInterface::class);
        $customer = $this->createMock(CustomerInterface::class);
        $customer->method('getUser')->willReturn($otherUser);
        $order = $this->createMock(OrderInterface::class);
        $order->method('getCustomer')->willReturn($customer);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $voter = new OrderVoter();
        $result = $voter->vote($token, $order, ['ORDER_VIEW']);
        $this->assertSame(Voter::ACCESS_DENIED, $result);
    }
}
