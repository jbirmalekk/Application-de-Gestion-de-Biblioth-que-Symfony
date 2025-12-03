<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile', name: 'profile_')]
#[IsGranted('ROLE_USER')]
final class ProfileController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(ProfileFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentPassword = $form->get('currentPassword')->getData();
            $newPassword = $form->get('newPassword')->getData();
            $confirmPassword = $form->get('confirmPassword')->getData();

            // Si l'utilisateur souhaite changer de mot de passe
            if ($newPassword || $confirmPassword || $currentPassword) {
                // Vérifier que au moins un champ de mot de passe est rempli
                if (!$newPassword && !$confirmPassword && !$currentPassword) {
                    // Aucun changement de mot de passe demandé
                } else {
                    // L'utilisateur veut changer le mot de passe
                    if (!$currentPassword) {
                        $this->addFlash('error', 'Veuillez saisir votre mot de passe actuel.');
                        return $this->redirectToRoute('profile_index');
                    }

                    if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
                        $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
                        return $this->redirectToRoute('profile_index');
                    }

                    if (!$newPassword) {
                        $this->addFlash('error', 'Veuillez saisir un nouveau mot de passe.');
                        return $this->redirectToRoute('profile_index');
                    }

                    if ($newPassword !== $confirmPassword) {
                        $this->addFlash('error', 'Les deux nouveaux mots de passe ne correspondent pas.');
                        return $this->redirectToRoute('profile_index');
                    }

                    if (strlen($newPassword) < 8) {
                        $this->addFlash('error', 'Le mot de passe doit contenir au moins 8 caractères.');
                        return $this->redirectToRoute('profile_index');
                    }

                    $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
                    $user->setPassword($hashedPassword);
                    error_log(sprintf('Password changed for user: %s', $user->getEmail()));
                }
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');

            return $this->redirectToRoute('profile_index');
        }

        return $this->render('profile/index.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }
}

