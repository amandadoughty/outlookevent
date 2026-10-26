<?php

defined('MOODLE_INTERNAL') || die();
function outlookevent_supports(string $feature): ?bool
{
    return match ($feature) {
        FEATURE_MOD_INTRO, FEATURE_SHOW_DESCRIPTION => true,
        default => null
    };
}

function outlookevent_add_instance(stdClass $data, ?mod_outlookevent_mod_form $mform = null): int
{
    global $DB;
    $data->timecreated = $data->timemodified = time();
    $data->grapheventid = null;
    $id = $DB->insert_record('outlookevent', $data);
    \mod_outlookevent\task\sync_event::queue($id);
    return $id;
}

function outlookevent_update_instance(stdClass $data, ?mod_outlookevent_mod_form $mform = null): bool
{
    global $DB;
    $id = (int)$data->instance;
    $factory = \core\lock\lock_config::get_lock_factory('mod_outlookevent');
    $lock = $factory->get_lock('sync_' . $id, 60);
    if (!$lock) throw new \moodle_exception('synclockfailed', 'mod_outlookevent');
    try {
        $old = $DB->get_record('outlookevent', ['id' => $id], '*', MUST_EXIST);
        if ($old->targetuser !== $data->targetuser && !empty($old->grapheventid)) {
            \mod_outlookevent\task\delete_event::queue($old->targetuser, $old->grapheventid);
            $old->grapheventid = null;
        }
        $data->id = $id;
        $data->grapheventid = $old->grapheventid;
        $data->timemodified = time();
        $DB->update_record('outlookevent', $data);
    } finally {
        $lock->release();
    }
    \mod_outlookevent\task\sync_event::queue($id);
    return true;
}

function outlookevent_delete_instance(int $id): bool
{
    global $DB;
    $factory = \core\lock\lock_config::get_lock_factory('mod_outlookevent');
    $lock = $factory->get_lock('sync_' . $id, 60);
    if (!$lock) throw new \moodle_exception('synclockfailed', 'mod_outlookevent');
    try {
        $activity = $DB->get_record('outlookevent', ['id' => $id]);
        if (!$activity) return false;
        if (!empty($activity->grapheventid)) \mod_outlookevent\task\delete_event::queue($activity->targetuser, $activity->grapheventid);
        $DB->delete_records('outlookevent', ['id' => $id]);
        return true;
    } finally {
        $lock->release();
    }
}
