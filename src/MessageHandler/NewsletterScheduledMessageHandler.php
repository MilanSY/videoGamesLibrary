<?php

namespace App\MessageHandler;

use App\Repository\UserRepository;
use App\Repository\VideoGameRepository;
use App\Schedule\NewsletterScheduledMessage;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

#[AsMessageHandler]
class NewsletterScheduledMessageHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private VideoGameRepository $videoGameRepository,
        private MailerInterface $mailer
    ) {
    }

    public function __invoke(NewsletterScheduledMessage $message): void
    {
        // Récupérer les utilisateurs abonnés à la newsletter
        $subscribedUsers = $this->userRepository->findBy(['subscriptionToNewsletter' => true]);

        if (empty($subscribedUsers)) {
            return;
        }

        // Calculer la date dans 7 jours
        $today = new \DateTime();
        $nextWeek = (new \DateTime())->modify('+7 days');

        // Récupérer les jeux sortant dans les 7 prochains jours
        $upcomingGames = $this->videoGameRepository->createQueryBuilder('v')
            ->where('v.releaseDate BETWEEN :today AND :nextWeek')
            ->setParameter('today', $today)
            ->setParameter('nextWeek', $nextWeek)
            ->orderBy('v.releaseDate', 'ASC')
            ->getQuery()
            ->getResult();

        if (empty($upcomingGames)) {
            return;
        }

        // Envoyer l'email à chaque utilisateur abonné
        foreach ($subscribedUsers as $user) {
            try {
                $email = (new TemplatedEmail())
                    ->from('noreply@videogameslibrary.com')
                    ->to($user->getEmail())
                    ->subject('🎮 Newsletter - Jeux vidéo à venir cette semaine')
                    ->htmlTemplate('emails/newsletter.html.twig')
                    ->context([
                        'user' => $user,
                        'games' => $upcomingGames,
                    ]);

                $this->mailer->send($email);
                
                // Attendre 5 secondes entre chaque envoi pour éviter les limites de taux Mailtrap
                sleep(5);
                
            } catch (\Exception $e) {
                // Log silencieux des erreurs
            }
        }
    }
}
