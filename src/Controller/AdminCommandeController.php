<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/commandes', name: 'admin_commandes_')]
#[IsGranted('ROLE_ADMIN')]
final class AdminCommandeController extends AbstractController
{
    public function __construct(
        private CommandeRepository $commandeRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $commandes = $this->commandeRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('commande/admin_index.html.twig', [
            'commandes' => $commandes,
        ]);
    }

    #[Route('/{id}/statut', name: 'change_statut', methods: ['POST'])]
    public function changeStatut(Request $request, Commande $commande): Response
    {
        $nouveauStatut = $request->request->get('statut');
        $token = $request->request->get('_token');

        if (!$this->isCsrfTokenValid('change_statut_' . $commande->getId(), $token)) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('admin_commandes_index');
        }

        if (!\in_array($nouveauStatut, ['en_attente', 'validee', 'expediee', 'livree', 'annulee'], true)) {
            $this->addFlash('error', 'Statut invalide.');
            return $this->redirectToRoute('admin_commandes_index');
        }

        $commande->setStatut($nouveauStatut);
        $commande->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        $this->addFlash('success', 'Statut de la commande mis à jour.');

        return $this->redirectToRoute('admin_commandes_index');
    }
}


