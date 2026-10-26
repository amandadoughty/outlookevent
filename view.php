<?php
require('../../config.php');
$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('outlookevent', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$instance = $DB->get_record('outlookevent', ['id' => $cm->instance], '*', MUST_EXIST);
require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/outlookevent:view', $context);
$PAGE->set_url('/mod/outlookevent/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($instance->name));
$PAGE->set_heading(format_string($course->fullname));
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($instance->name));
echo format_module_intro('outlookevent', $instance, $cm->id);
echo $OUTPUT->footer();
