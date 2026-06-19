<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\ReminderWindow;
use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class EventReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Event $event,
        public readonly ReminderWindow $window,
    ) {}

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
        $label = $this->window->value;

        return (new MailMessage)
            ->subject("Reminder: {$eventName} is {$label}")
            ->greeting("Don't forget!")
            ->line("**{$eventName}** is coming up {$label}.")
            ->line("Location: {$this->event->location_name}");
    }
}
