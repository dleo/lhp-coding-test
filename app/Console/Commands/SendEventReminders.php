<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ReminderWindow;
use App\Models\Attendee;
use App\Notifications\EventReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

final class SendEventReminders extends Command
{
    protected $signature = 'events:send-reminders';

    protected $description = 'Send event reminder notifications to attendees at 3-day and 24-hour windows';

    private const TOLERANCE_MINUTES = 30;

    /**
     * @return array<int, array{offsetHours: int, column: string, window: ReminderWindow}>
     */
    private function windowDescriptors(): array
    {
        return [
            [
                'offsetHours' => 72,
                'column' => 'reminder_3d_sent_at',
                'window' => ReminderWindow::ThreeDays,
            ],
            [
                'offsetHours' => 24,
                'column' => 'reminder_24h_sent_at',
                'window' => ReminderWindow::TwentyFourHours,
            ],
        ];
    }

    public function handle(): int
    {
        foreach ($this->windowDescriptors() as $descriptor) {
            $this->processWindow(
                $descriptor['offsetHours'],
                $descriptor['column'],
                $descriptor['window'],
            );
        }

        return self::SUCCESS;
    }

    private function processWindow(int $offsetHours, string $sentAtColumn, ReminderWindow $window): void
    {
        $target = now()->addHours($offsetHours);
        $lower = (clone $target)->subMinutes(self::TOLERANCE_MINUTES)->getTimestamp();
        $upper = (clone $target)->addMinutes(self::TOLERANCE_MINUTES)->getTimestamp();

        $attendees = Attendee::query()
            ->whereNull($sentAtColumn)
            ->whereHas('event', static function ($query) use ($lower, $upper): void {
                $query->whereBetween('created_time', [$lower, $upper]);
            })
            ->with('event')
            ->get();

        foreach ($attendees as $attendee) {
            $attendee->notify(new EventReminder($attendee->event, $window));
            $attendee->update([$sentAtColumn => Carbon::now()]);
        }
    }
}
