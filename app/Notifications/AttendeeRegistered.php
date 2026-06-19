<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class AttendeeRegistered extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Event $event) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $eventName = $this->event->payload['name'] ?? 'the event';

        return (new MailMessage)
            ->subject("You're registered for {$eventName}")
            ->greeting("You're in!")
            ->line("You have successfully registered for **{$eventName}**.")
            ->line("Location: {$this->event->location_name}");
    }
}
