<?php

namespace App\Console\Commands;

use App\Models\EventTask;
use App\Notifications\TaskDueReminderNotification;
use Illuminate\Console\Command;

class SendTaskReminders extends Command
{
    protected $signature = 'eventra:send-task-reminders {--hours=24 : Look-ahead window in hours}';

    protected $description = 'Remind assignees of event tasks due soon.';

    public function handle(): int
    {
        $tasks = EventTask::with(['event', 'assignedUser'])
            ->whereNull('reminder_sent_at')
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereNotNull('assigned_to')
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [now()->toDateString(), now()->addHours((int) $this->option('hours'))->toDateString()])
            ->get();

        foreach ($tasks as $task) {
            if (! $task->assignedUser) {
                continue;
            }
            $task->assignedUser->notify(new TaskDueReminderNotification($task));
            $task->forceFill(['reminder_sent_at' => now()])->save();
        }

        $this->info("Task reminders sent for {$tasks->count()} task(s).");

        return self::SUCCESS;
    }
}
