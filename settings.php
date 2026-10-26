<?php

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext('mod_outlookevent/tenantid', get_string('tenantid', 'mod_outlookevent'), get_string('tenantid_desc', 'mod_outlookevent'), '', PARAM_ALPHANUMEXT));
    $settings->add(new admin_setting_configtext('mod_outlookevent/clientid', get_string('clientid', 'mod_outlookevent'), get_string('clientid_desc', 'mod_outlookevent'), '', PARAM_ALPHANUMEXT));
    $settings->add(new admin_setting_configpasswordunmask('mod_outlookevent/clientsecret', get_string('clientsecret', 'mod_outlookevent'), get_string('clientsecret_desc', 'mod_outlookevent'), ''));
}
