<?php

namespace App\Controller;

use App\Entity\Livre;
use App\Service\SubscriptionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/premium', name: 'premium_')]
#[IsGranted('ROLE_USER')]
class PremiumController extends AbstractController
{
    public function __construct(
        private SubscriptionService $subscriptionService
    ) {}

    #[Route('/read/{id}', name: 'read', methods: ['GET'])]
    public function read(Livre $livre): Response
    {
        $user = $this->getUser();

        // Vérifier si le livre est premium
        if (!$livre->isPremium()) {
            return $this->redirectToRoute('app_livre_show', ['id' => $livre->getId()]);
        }

        // Vérifier si l'utilisateur peut accéder au livre
        if (!$this->subscriptionService->canAccessPremiumBook($user, $livre)) {
            $this->addFlash('warning', 'Ce livre est réservé aux abonnés Premium. Souscrivez pour y accéder!');
            return $this->redirectToRoute('subscription_index');
        }

        // Afficher le lecteur sécurisé
        return $this->render('premium/read.html.twig', [
            'livre' => $livre,
        ]);
    }
}
