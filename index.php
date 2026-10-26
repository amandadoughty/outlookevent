<?php
require('../../config.php');
$id = required_param('id', PARAM_INT);
$course = get_course($id);
require_course_login($course);
$PAGE->set_url('/mod/outlookevent/index.php', ['id' => $course->id]);
$PAGE->set_title(get_string('modulenameplural', 'mod_outlookevent'));
$PAGE->set_heading(format_string($course->fullname));
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'mod_outlookevent'));
$instances = get_all_instances_in_course('outlookevent', $course);
foreach ($instances as $instance) {
    $url = new moodle_url('/mod/outlookevent/view.php', ['id' => $instance->coursemodule]);
    echo html_writer::div(html_writer::link($url, format_string($instance->name)));
}
echo $OUTPUT->footer();
