<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ResetPasswordRequestFormType;
use App\Form\ResetPasswordFormType;
use App\Repository\UserRepository;
use App\Service\PasswordResetService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class ResetPasswordController extends AbstractController
{
    public function __construct(
        private PasswordResetService $passwordResetService,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    #[Route('/reset-password/request', name: 'app_forgot_password_request')]
    public function request(Request $request): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $user = $this->userRepository->findOneBy(['email' => $email]);

            // Ne pas révéler si l'email existe ou non (sécurité)
            if ($user) {
                $resetRequest = $this->passwordResetService->createResetRequest($user);
                
                try {
                    $this->passwordResetService->sendResetEmail($user, $resetRequest->getToken());
                } catch (\Exception $e) {
                    $this->addFlash('danger', 'Une erreur est survenue lors de l\'envoi de l\'email.');
                    return $this->redirectToRoute('app_forgot_password_request');
                }
            }

            // Toujours afficher le même message pour éviter l'énumération des emails
            $this->addFlash('success', 'Si un compte existe avec cet email, vous recevrez un lien de réinitialisation.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('reset_password/request.html.twig', [
            'requestForm' => $form->createView(),
        ]);
    }

    #[Route('/reset-password/reset/{token}', name: 'app_reset_password')]
    public function reset(Request $request, string $token): Response
    {
        $resetRequest = $this->passwordResetService->validateToken($token);

        if (!$resetRequest) {
            $this->addFlash('danger', 'Le lien de réinitialisation est invalide ou a expiré.');
            return $this->redirectToRoute('app_forgot_password_request');
        }

        $form = $this->createForm(ResetPasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $this->passwordHasher->hashPassword(
                $resetRequest->getUser(),
                $newPassword
            );

            $this->passwordResetService->resetPassword($resetRequest, $hashedPassword);

            $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès !');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form->createView(),
            'token' => $token,
        ]);
    }

    #[Route('/reset-password/check-email', name: 'app_check_email')]
    public function checkEmail(): Response
    {
        return $this->render('reset_password/check_email.html.twig');
    }

    #[Route('/reset-password/config-help', name: 'app_reset_password_config_help')]
    public function configHelp(): Response
    {
        return $this->render('reset_password/config_help.html.twig');
    }
}
