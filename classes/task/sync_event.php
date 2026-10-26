<?php

declare(strict_types=1);

namespace mod_outlookevent\task;
defined('MOODLE_INTERNAL') || die();

final class sync_event extends \core\task\adhoc_task
{
    public static function queue(int $id): void
    {
        $task = new self();
        $task->set_custom_data((object)['id' => $id]);
        \core\task\manager::queue_adhoc_task($task);
    }

    public function execute(): void
    {
        global $DB;
        $id = (int)$this->get_custom_data()->id;
        $factory = \core\lock\lock_config::get_lock_factory('mod_outlookevent');
        $lock = $factory->get_lock('sync_' . $id, 60);
        if (!$lock) throw new \moodle_exception('synclockfailed', 'mod_outlookevent');
        try {
            $activity = $DB->get_record('outlookevent', ['id' => $id]);
            if (!$activity) return;
            $eventid = \mod_outlookevent\outlook_calendar::create()->sync($activity);
            if ($eventid !== $activity->grapheventid) $DB->set_field('outlookevent', 'grapheventid', $eventid, ['id' => $id]);
        } finally {
            $lock->release();
        }
    }
}
