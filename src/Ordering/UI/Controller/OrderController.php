<?php

namespace App\Ordering\UI\Controller;

use App\Entity\User;
use App\Ordering\Application\OrderWorkflow;
use App\Ordering\Domain\Repository\OrderRepositoryInterface;
use App\Ordering\Domain\Repository\OrderStatusHistoryRepositoryInterface;
use App\Ordering\Infrastructure\Security\OrderVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Workflow\Exception\LogicException as WorkflowLogicException;

/**
 * Order tracking (#28) and transition actions (#26). Authorization is
 * delegated to App\Ordering\Infrastructure\Security\OrderVoter.
 */
#[Route('/commandes')]
class OrderController extends AbstractController
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly OrderStatusHistoryRepositoryInterface $history,
        private readonly OrderWorkflow $orderWorkflow,
    ) {
    }

    #[Route('', name: 'app_order_index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (null !== $user->getCompany()) {
            $orders = $this->orders->findSubmittedForCompany($user->getCompany());
        } elseif ($this->isGranted(User::ROLE_VALIDATOR)) {
            $orders = $this->orders->findAllSubmitted();
        } else {
            $orders = [];
        }

        return $this->render('order/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/{id}', name: 'app_order_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $order = $this->orders->findById($id);
        if (null === $order || $order->isDraft() || !$this->isGranted(OrderVoter::VIEW, $order)) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        $enabledTransitions = array_filter(
            $this->orderWorkflow->enabledTransitions($order),
            fn ($transition) => $this->isGranted(OrderVoter::attributeForTransition($transition->getName()) ?? '', $order)
        );

        return $this->render('order/show.html.twig', [
            'order' => $order,
            'history' => $this->history->findByOrder($order),
            'enabledTransitions' => $enabledTransitions,
        ]);
    }

    #[Route('/{id}/transition/{transition}', name: 'app_order_transition', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function transition(int $id, string $transition, Request $request): Response
    {
        $order = $this->orders->findById($id);
        if (null === $order || $order->isDraft() || !$this->isGranted(OrderVoter::VIEW, $order)) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        if (!$this->isCsrfTokenValid('order_transition_'.$order->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $attribute = OrderVoter::attributeForTransition($transition);
        if (null === $attribute) {
            throw $this->createNotFoundException('Transition inconnue.');
        }

        $this->denyAccessUnlessGranted($attribute, $order, 'Vous ne pouvez pas effectuer cette action.');

        try {
            $this->orderWorkflow->apply($order, $transition);
            $this->addFlash('success', 'Commande mise à jour.');
        } catch (WorkflowLogicException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_order_show', ['id' => $order->getId()]);
    }
}
