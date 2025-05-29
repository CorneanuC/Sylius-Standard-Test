<?php

namespace App\Security\Voter;


use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class OrderVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === 'ORDER_VIEW' && $subject instanceof OrderInterface;
    }

       protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!is_object($user)) {
            return false;
        }

        /** @var OrderInterface $order */
        $order = $subject;

        $customer = $order->getCustomer();

        if ($customer instanceof \Sylius\Component\Core\Model\CustomerInterface) {
            return $customer->getUser() === $user;
        }

        return false;
    }
}