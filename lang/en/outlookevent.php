<?php

$string['pluginname'] = 'Outlook event';
$string['modulename'] = 'Outlook event';
$string['modulenameplural'] = 'Outlook events';
$string['pluginadministration'] = 'Outlook event administration';
$string['outlookevent:addinstance'] = 'Add an Outlook event activity';
$string['outlookevent:view'] = 'View an Outlook event activity';
$string['tenantid'] = 'Microsoft Entra tenant ID';
$string['tenantid_desc'] = 'The tenant ID used to authenticate with Microsoft Graph.';
$string['clientid'] = 'Application client ID';
$string['clientid_desc'] = 'The client ID of the Microsoft Entra application registration. The application requires the Microsoft Graph Calendars.ReadWrite application permission with administrator consent and access to the organiser mailbox. The organiser calendar must support the teamsForBusiness online meeting provider.';
$string['clientsecret'] = 'Application client secret';
$string['clientsecret_desc'] = 'The client secret used by the Microsoft Entra application.';
$string['targetuser'] = 'Outlook user';
$string['targetuser_help'] = 'The user principal name of the Outlook calendar owner and Teams meeting organiser. This user must have an Exchange Online mailbox and be enabled for Teams meetings. Saving the activity queues a Teams meeting synchronisation and Outlook invitations to all actively enrolled course users, using their Moodle email addresses. Suspended users and the organiser are excluded. Invitations and updates are sent by Microsoft Graph when Moodle cron processes the task; enrolment changes are picked up the next time the activity is saved.';
$string['eventstart'] = 'Event start';
$string['eventend'] = 'Event end';
$string['eventendbeforestart'] = 'The event end must be after the event start.';
$string['graphnotconfigured'] = 'Microsoft Graph authentication has not been configured.';
$string['graphauthconnectionerror'] = 'Unable to connect to Microsoft authentication services.';
$string['graphautherror'] = 'Microsoft Graph authentication failed.';
$string['graphinvalidresponse'] = 'Microsoft Graph returned an invalid response.';
$string['graphapierror'] = 'The Microsoft Graph request failed.';
$string['graphconnectionerror'] = 'Unable to connect to Microsoft Graph.';
$string['invalidattendeeemail'] = 'Enrolled user {$a} does not have a valid email address. Correct their email address before synchronising the meeting.';
$string['synclockfailed'] = 'Unable to obtain the Outlook event synchronisation lock.';
