<?php

namespace App\Ordering\Infrastructure\Security;

use App\Identity\Domain\Entity\Company;
use App\Identity\Domain\Entity\User;
use App\Ordering\Domain\Entity\Order;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Who may see an order and who may trigger which workflow transition.
 *
 * - ROLE_VALIDATOR (staff) can view every order and drive the whole pipeline.
 * - Anyone else only reaches the orders of their own company, and only
 *   submits or cancels them.
 *
 * This only answers "is this user allowed?"; whether the transition is
 * reachable from the order's current status stays the workflow's job.
 *
 * @extends Voter<string, Order>
 */
final class OrderVoter extends Voter
{
    public const VIEW = 'ORDER_VIEW';

    private const TRANSITION_ATTRIBUTES = [
        'submit' => 'ORDER_SUBMIT',
        'validate' => 'ORDER_VALIDATE',
        'prepare' => 'ORDER_PREPARE',
        'ship' => 'ORDER_SHIP',
        'invoice' => 'ORDER_INVOICE',
        'cancel' => 'ORDER_CANCEL',
    ];

    private const COMPANY_TRANSITIONS = ['submit', 'cancel'];

    public static function attributeForTransition(string $transition): ?string
    {
        return self::TRANSITION_ATTRIBUTES[$transition] ?? null;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Order
            && (self::VIEW === $attribute || in_array($attribute, self::TRANSITION_ATTRIBUTES, true));
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if (in_array(User::ROLE_VALIDATOR, $user->getRoles(), true)) {
            return true;
        }

        if (self::VIEW === $attribute) {
            return $this->belongsToUserCompany($subject, $user);
        }

        $transition = array_search($attribute, self::TRANSITION_ATTRIBUTES, true);

        return in_array($transition, self::COMPANY_TRANSITIONS, true) && $this->belongsToUserCompany($subject, $user);
    }

    private function belongsToUserCompany(Order $order, User $user): bool
    {
        $userCompany = $user->getCompany();

        return null !== $userCompany && $this->isSameCompany($userCompany, $order->getCompany());
    }

    private function isSameCompany(Company $a, Company $b): bool
    {
        return $a === $b || (null !== $a->getId() && $a->getId() === $b->getId());
    }
}
