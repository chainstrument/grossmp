<?php

namespace App\Ordering\Application;

use App\Ordering\Domain\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Thin wrapper around the "order" Symfony Workflow (config/packages/workflow.yaml)
 * — the application layer's only door into transitioning an Order. Guards and
 * side effects (stock consumption, history) live in
 * App\Ordering\Infrastructure\Workflow\OrderWorkflowSubscriber, triggered by
 * the workflow's own events, not called from here.
 */
final class OrderWorkflow
{
    public function __construct(
        #[Autowire(service: 'state_machine.order')]
        private readonly WorkflowInterface $workflow,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function can(Order $order, string $transition): bool
    {
        return $this->workflow->can($order, $transition);
    }

    /**
     * @throws \Symfony\Component\Workflow\Exception\LogicException if the
     *                                                              transition isn't enabled (blocked by a guard, or not reachable
     *                                                              from the order's current status)
     */
    public function apply(Order $order, string $transition): void
    {
        $this->workflow->apply($order, $transition);
        $this->entityManager->flush();
    }

    /**
     * @return Transition[] transitions currently available from the order's status
     */
    public function enabledTransitions(Order $order): array
    {
        return $this->workflow->getEnabledTransitions($order);
    }
}
