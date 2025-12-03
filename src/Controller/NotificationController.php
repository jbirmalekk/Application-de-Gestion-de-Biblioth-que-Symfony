<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/notifications', name: 'notifications_')]
#[IsGranted('ROLE_USER')]
class NotificationController extends AbstractController
{
    public function __construct(
        private NotificationRepository $notificationRepository,
        private NotificationService $notificationService,
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        $notifications = $this->notificationRepository->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );

        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/mark-read/{id}', name: 'mark_read', methods: ['POST'])]
    public function markRead(Notification $notification): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if ($notification->getUser() === $this->getUser()) {
            $this->notificationService->markAsRead($notification);
        }

        return $this->redirectToRoute('notifications_index');
    }

    #[Route('/mark-all-read', name: 'mark_all_read', methods: ['POST'])]
    public function markAllRead(): Response
    {
        $user = $this->getUser();
        $notifications = $this->notificationRepository->findUnreadByUser($user);

        foreach ($notifications as $notification) {
            $notification->setIsRead(true);
            $this->entityManager->persist($notification);
        }

        $this->entityManager->flush();

        return $this->redirectToRoute('notifications_index');
    }

    #[Route('/delete/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Notification $notification): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        // Vérifier que la notification appartient à l'utilisateur
        if ($notification->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cette notification');
        }

        $this->entityManager->remove($notification);
        $this->entityManager->flush();

        $this->addFlash('success', 'Notification supprimée');

        return $this->redirectToRoute('notifications_index');
    }

    #[Route('/delete-all', name: 'delete_all', methods: ['POST'])]
    public function deleteAll(): Response
    {
        $user = $this->getUser();
        $notifications = $this->notificationRepository->findBy(['user' => $user]);

        foreach ($notifications as $notification) {
            $this->entityManager->remove($notification);
        }

        $this->entityManager->flush();

        $this->addFlash('success', 'Toutes les notifications ont été supprimées');

        return $this->redirectToRoute('notifications_index');
    }
}


