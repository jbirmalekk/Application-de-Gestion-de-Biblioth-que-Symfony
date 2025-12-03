<?php

namespace App\Command;

use App\Repository\WishlistRepository;
use App\Repository\LivreRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:wishlist:notify-availability',
    description: 'Notify users when their wishlist books become available',
)]
class WishlistNotifyAvailabilityCommand extends Command
{
    public function __construct(
        private WishlistRepository $wishlistRepository,
        private LivreRepository $livreRepository,
        private EntityManagerInterface $entityManager,
        private NotificationService $notificationService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Find all books with notification enabled where stock > 0
        $wishlistItems = $this->wishlistRepository->findNotifiableWithAvailableStock();

        if (empty($wishlistItems)) {
            $io->info('No books to notify about.');
            return Command::SUCCESS;
        }

        $io->writeln(sprintf('Found %d wishlist items to notify', count($wishlistItems)));

        $notificationCount = 0;
        $processedLivres = [];

        foreach ($wishlistItems as $item) {
            $livre = $item->getLivre();
            $user = $item->getUser();
            $livreId = $livre->getId();

            // Send notification only once per book (éviter les doublons d'email)
            if (!isset($processedLivres[$livreId])) {
                $processedLivres[$livreId] = true;
                
                $io->text(sprintf(
                    'Book "%s" is back in stock for user %s',
                    $livre->getTitre(),
                    $user->getEmail()
                ));

                // Ici, vous pourriez envoyer un email réel
                // $this->mailer->send(...);

                $notificationCount++;
            }

            // Créer une notification in-app pour chaque utilisateur concerné
            $this->notificationService->createForUser(
                $user,
                sprintf('Le livre "%s" est de nouveau en stock.', $livre->getTitre()),
                'wishlist_restock',
                $livre
            );

            // Disable notification after sending
            $item->setNotifyWhenAvailable(false);
            $this->entityManager->persist($item);
        }

        $this->entityManager->flush();

        $io->success(sprintf('Successfully notified %d users', $notificationCount));
        return Command::SUCCESS;
    }
}
