<?php

namespace App\Command;

use App\Schedule\NewsletterScheduledMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:test-scheduler',
    description: 'Test le scheduler en envoyant manuellement le message',
)]
class TestSchedulerCommand extends Command
{
    public function __construct(
        private MessageBusInterface $messageBus
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->info('Envoi manuel du message de newsletter via le MessageBus...');

        // Dispatcher le message comme le ferait le scheduler
        $this->messageBus->dispatch(new NewsletterScheduledMessage());

        $io->success('Message dispatché avec succès ! Le MessageHandler devrait avoir envoyé les emails.');

        return Command::SUCCESS;
    }
}
