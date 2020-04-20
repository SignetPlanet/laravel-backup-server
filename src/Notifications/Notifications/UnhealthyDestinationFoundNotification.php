<?php

namespace Spatie\BackupServer\Notifications\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackAttachment;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use Spatie\BackupServer\Notifications\Notifications\Concerns\HandlesNotifications;
use Spatie\BackupServer\Tasks\Monitor\Events\UnhealthyDestinationFoundEvent;

class UnhealthyDestinationFoundNotification extends Notification implements ShouldQueue
{
    use HandlesNotifications, Queueable;

    public UnhealthyDestinationFoundEvent $event;

    public function __construct(UnhealthyDestinationFoundEvent $event)
    {
        $this->event = $event;
    }

    public function toMail(): MailMessage
    {
        return (new MailMessage)
            ->from($this->fromEmail(), $this->fromName())
            ->subject(trans('backup-server::notifications.unhealthy_destination_found_subject', $this->translationParameters()))
            ->line(trans('backup-server::notifications.unhealthy_destination_found_body', $this->translationParameters()))
            ->line("Found problems: " . collect($this->event->failureMessages)->join(', '));
    }

    public function toSlack(): SlackMessage
    {
        $message = (new SlackMessage)
            ->success()
            ->content(trans('backup-server::notifications.unhealthy_destination_found_subject', $this->translationParameters()));

        foreach ($this->event->failureMessages as $failureMessage) {
            $message->attachment(function (SlackAttachment $attachment) use ($failureMessage) {
                $attachment->content($failureMessage);
            });
        }

        return $message;
    }

    protected function translationParameters(): array
    {
        return [
            'destination_name' => $this->event->destination->name,
        ];
    }
}
