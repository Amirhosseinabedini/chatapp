<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-users',
    description: 'Update existing users with display names',
)]
class UpdateUsersCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $users = $this->entityManager->getRepository(User::class)->findAll();
        
        foreach ($users as $user) {
            if (!$user->getDisplayName()) {
                // Extract name from email
                $emailParts = explode('@', $user->getEmail());
                $name = ucfirst($emailParts[0]);
                $user->setDisplayName($name);
                
                $io->info("Updated user {$user->getEmail()} with display name: {$name}");
            }
        }
        
        $this->entityManager->flush();
        
        $io->success('All users updated successfully!');
        
        return Command::SUCCESS;
    }
}

