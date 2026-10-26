<?php

declare(strict_types=1);

namespace mod_outlookevent\task;
defined('MOODLE_INTERNAL') || die();

final class delete_event extends \core\task\adhoc_task
{
    public static function queue(string $user, string $eventid): void
    {
        $task = new self();
        $task->set_custom_data((object)['user' => $user, 'eventid' => $eventid]);
        \core\task\manager::queue_adhoc_task($task);
    }

    public function execute(): void
    {
        $d = $this->get_custom_data();
        \mod_outlookevent\outlook_calendar::create()->delete((string)$d->user, (string)$d->eventid);
    }
}
