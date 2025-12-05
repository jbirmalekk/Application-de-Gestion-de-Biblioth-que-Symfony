<?php

namespace App\Service;

use App\Entity\ResetPasswordRequest;
use App\Entity\User;
use App\Repository\ResetPasswordRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class PasswordResetService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ResetPasswordRequestRepository $resetPasswordRequestRepository,
        private MailerInterface $mailer
    ) {
    }

    public function createResetRequest(User $user): ResetPasswordRequest
    {
        // Invalider toutes les demandes précédentes de cet utilisateur
        $this->resetPasswordRequestRepository->invalidatePreviousRequests($user);

        // Créer une nouvelle demande
        $resetRequest = new ResetPasswordRequest();
        $resetRequest->setUser($user);

        $this->entityManager->persist($resetRequest);
        $this->entityManager->flush();

        return $resetRequest;
    }

    public function sendResetEmail(User $user, string $token): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('noreply@bibliotheque.com', 'Bibliothèque'))
            ->to($user->getEmail())
            ->subject('Réinitialisation de votre mot de passe')
            ->htmlTemplate('emails/reset_password.html.twig')
            ->context([
                'user' => $user,
                'token' => $token,
                'expiresAt' => (new \DateTime())->modify('+1 hour'),
            ]);

        $this->mailer->send($email);
    }

    public function validateToken(string $token): ?ResetPasswordRequest
    {
        return $this->resetPasswordRequestRepository->findValidToken($token);
    }

    public function resetPassword(ResetPasswordRequest $resetRequest, string $newPassword): void
    {
        $user = $resetRequest->getUser();
        
        // Le mot de passe sera hashé par le UserPasswordHasher dans le contrôleur
        $user->setPassword($newPassword);
        
        // Marquer la demande comme utilisée
        $resetRequest->setIsUsed(true);
        
        $this->entityManager->flush();
    }
}
