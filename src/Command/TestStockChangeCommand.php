<?php

namespace App\Command;

use App\Entity\Livre;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-stock-change',
    description: 'Test stock change notification by modifying a book quantity',
)]
class TestStockChangeCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('livre-id', InputArgument::REQUIRED, 'The livre ID to modify')
            ->addArgument('new-quantity', InputArgument::REQUIRED, 'New quantity for the book');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $livreId = (int)$input->getArgument('livre-id');
        $newQuantity = (int)$input->getArgument('new-quantity');

        $livre = $this->entityManager->getRepository(Livre::class)->find($livreId);
        if (!$livre) {
            $io->error("Livre with ID $livreId not found!");
            return Command::FAILURE;
        }

        $oldQuantity = $livre->getQte();
        $io->info("Current quantity: $oldQuantity");
        $io->info("New quantity: $newQuantity");

        $livre->setQte($newQuantity);
        
        $io->info("Flushing changes to database...");
        $this->entityManager->flush();

        $io->success("Stock changed from $oldQuantity to $newQuantity!");
        
        // Check notifications created
        $notifications = $this->entityManager->getRepository(\App\Entity\Notification::class)
            ->findBy(['livre' => $livre], ['createdAt' => 'DESC'], 5);
        
        $io->section('Last 5 notifications for this book:');
        foreach ($notifications as $notif) {
            $io->writeln(sprintf(
                "- User: %s | Type: %s | Message: %s | Created: %s",
                $notif->getUser()->getEmail(),
                $notif->getType(),
                substr($notif->getMessage(), 0, 50),
                $notif->getCreatedAt()->format('Y-m-d H:i:s')
            ));
        }

        return Command::SUCCESS;
    }
}
