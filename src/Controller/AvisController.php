<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Entity\Livre;
use App\Form\AvisType;
use App\Repository\AvisRepository;
use App\Repository\LivreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/avis')]
#[IsGranted('ROLE_USER')]
final class AvisController extends AbstractController
{
    #[Route('/livre/{id}/new', name: 'app_avis_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        Livre $livre,
        EntityManagerInterface $entityManager,
        AvisRepository $avisRepository
    ): Response {
        $user = $this->getUser();

        // Vérifier si l'utilisateur a déjà laissé un avis pour ce livre
        $existingAvis = $avisRepository->findOneBy([
            'livre' => $livre,
            'user' => $user,
        ]);

        if ($existingAvis) {
            $this->addFlash('warning', 'Vous avez déjà laissé un avis pour ce livre.');
            return $this->redirectToRoute('app_livre_show', ['id' => $livre->getId()]);
        }

        $avis = new Avis();
        $avis->setLivre($livre);
        $avis->setUser($user);

        $form = $this->createForm(AvisType::class, $avis);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Synchroniser les étoiles avec la note
            $avis->setEtoiles($avis->getNote());
            // Par défaut, l'avis n'est pas approuvé
            $avis->setApprouve(false);

            $entityManager->persist($avis);
            $entityManager->flush();

            $this->addFlash('success', 'Votre avis a été soumis avec succès. Il sera publié après modération par un administrateur.');

            return $this->redirectToRoute('app_livre_show', ['id' => $livre->getId()]);
        }

        return $this->render('avis/new.html.twig', [
            'avis' => $avis,
            'livre' => $livre,
            'form' => $form,
        ]);
    }
}

