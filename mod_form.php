<?php

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/course/moodleform_mod.php');

final class mod_outlookevent_mod_form extends moodleform_mod
{
    public function definition(): void
    {
        $mform = $this->_form;
        $mform->addElement('text', 'name', get_string('name'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $this->standard_intro_elements();
        $mform->addElement('text', 'targetuser', get_string('targetuser', 'mod_outlookevent'));
        $mform->setType('targetuser', PARAM_EMAIL);
        $mform->addRule('targetuser', null, 'required', null, 'client');
        $mform->addHelpButton('targetuser', 'targetuser', 'mod_outlookevent');
        $mform->addElement('date_time_selector', 'eventstart', get_string('eventstart', 'mod_outlookevent'));
        $mform->addElement('date_time_selector', 'eventend', get_string('eventend', 'mod_outlookevent'));
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    public function validation($data, $files): array
    {
        $errors = parent::validation($data, $files);
        if ($data['eventend'] <= $data['eventstart']) $errors['eventend'] = get_string('eventendbeforestart', 'mod_outlookevent');
        return $errors;
    }
}
