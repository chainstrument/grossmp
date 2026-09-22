<?php

namespace App\Identity\UI\Controller;

use App\Identity\Application\AccountEditor;
use App\Identity\Domain\Entity\User;
use App\Identity\UI\Form\UserAccountType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/account', name: 'app_account')]
#[IsGranted('ROLE_USER')]
class AccountController extends AbstractController
{
    #[Route('', name: '', methods: ['GET', 'POST'])]
    public function edit(Request $request, AccountEditor $accountEditor): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(UserAccountType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $accountEditor->save($user);

            $this->addFlash('success', 'Vos informations ont été mises à jour.');

            return $this->redirectToRoute('app_account');
        }

        return $this->render('account/edit.html.twig', [
            'form' => $form,
        ]);
    }
}
