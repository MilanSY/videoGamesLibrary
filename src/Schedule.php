<?php

namespace App;

use App\Schedule\NewsletterScheduledMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule as SymfonySchedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

#[AsSchedule]
class Schedule implements ScheduleProviderInterface
{
    public function __construct(
        private CacheInterface $cache,
    ) {
    }

    public function getSchedule(): SymfonySchedule
    {
        return (new SymfonySchedule())
        
            ->add(
                //RecurringMessage::cron('* * * * *', new NewsletterScheduledMessage())
                RecurringMessage::cron('30 8 * * 1', new NewsletterScheduledMessage())
            )
        ;
    }
}
