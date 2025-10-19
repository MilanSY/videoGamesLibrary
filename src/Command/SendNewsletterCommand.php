<?php

namespace App\Command;

use App\Repository\UserRepository;
use App\Repository\VideoGameRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;

#[AsCommand(
    name: 'app:send-newsletter',
    description: 'Envoie la newsletter aux utilisateurs abonnés avec les jeux sortant dans les 7 prochains jours',
)]
class SendNewsletterCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private VideoGameRepository $videoGameRepository,
        private MailerInterface $mailer
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Récupérer les utilisateurs abonnés à la newsletter
        $subscribedUsers = $this->userRepository->findBy(['subscriptionToNewsletter' => true]);

        if (empty($subscribedUsers)) {
            $io->warning('Aucun utilisateur abonné à la newsletter.');
            return Command::SUCCESS;
        }

        $io->info(sprintf('Nombre d\'utilisateurs abonnés : %d', count($subscribedUsers)));

        // Récupérer les jeux sortant dans les 7 prochains jours
        $now = new \DateTime();
        $sevenDaysLater = (new \DateTime())->modify('+7 days');

        $upcomingGames = $this->videoGameRepository->createQueryBuilder('vg')
            ->where('vg.releaseDate BETWEEN :now AND :sevenDays')
            ->setParameter('now', $now)
            ->setParameter('sevenDays', $sevenDaysLater)
            ->orderBy('vg.releaseDate', 'ASC')
            ->getQuery()
            ->getResult();

        if (empty($upcomingGames)) {
            $io->warning('Aucun jeu ne sort dans les 7 prochains jours.');
            return Command::SUCCESS;
        }

        $io->info(sprintf('Nombre de jeux à venir : %d', count($upcomingGames)));

        // Envoyer l'email à chaque utilisateur abonné
        $sentCount = 0;
        foreach ($subscribedUsers as $user) {
            try {
                $email = (new TemplatedEmail())
                    ->from('newsletter@videogameslibrary.com')
                    ->to($user->getEmail())
                    ->subject('Newsletter - Jeux à venir cette semaine !')
                    ->htmlTemplate('emails/newsletter.html.twig')
                    ->context([
                        'user' => $user,
                        'games' => $upcomingGames,
                    ]);

                $this->mailer->send($email);
                $sentCount++;
                
                $io->text(sprintf('✓ Email envoyé à %s', $user->getEmail()));
                
            } catch (\Exception $e) {
                $io->error(sprintf('Erreur lors de l\'envoi à %s : %s', $user->getEmail(), $e->getMessage()));
            }
        }

        $io->success(sprintf('Newsletter envoyée avec succès à %d utilisateur(s) !', $sentCount));

        return Command::SUCCESS;
    }
}
