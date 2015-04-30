<?php

class block_mycourses_edit_form extends block_edit_form {

    protected function specific_definition($mform) {

        // Section header title according to language file.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

	//Headline for Settings, details
	$mform->addElement('html', '<b>'.get_string('settings_details', 'block_mycourses').'</b>');

	// Show recent cat. path?
        $mform->addElement('advcheckbox', 'config_showcategorypath', get_string('showcategorypath', 'block_mycourses'));
	$mform->addHelpButton('config_showcategorypath','showcategorypathinfo','block_mycourses');
        $mform->setDefault('config_showcategorypath', 0);
        $mform->setType('config_showcategorypath', PARAM_INT);

	// Show teachers?
        $mform->addElement('advcheckbox', 'config_showteachers', get_string('showteachers', 'block_mycourses'));
	$mform->addHelpButton('config_showteachers','showteachersinfo','block_mycourses');
        $mform->setDefault('config_showteachers', 1);
        $mform->setType('config_showteachers', PARAM_INT);

	// Show secretaries?
        $mform->addElement('advcheckbox', 'config_showsecretaries', get_string('showsecretaries', 'block_mycourses'));
	$mform->addHelpButton('config_showsecretaries','showsecretariesinfo','block_mycourses');
        $mform->setDefault('config_showsecretaries', 1);
        $mform->setType('config_showsecretaries', PARAM_INT);

	// Show roles?
        $mform->addElement('advcheckbox', 'config_showroles', get_string('showroles', 'block_mycourses'));
	$mform->addHelpButton('config_showroles','showrolesinfo','block_mycourses');
        $mform->setDefault('config_showroles', 0);
        $mform->setType('config_showroles', PARAM_INT);

	// Show recent activity?
	$mform->addElement('advcheckbox', 'config_showrecentactivity', get_string('showrecentactivity', 'block_mycourses'));
	$mform->addHelpButton('config_showrecentactivity','showrecentactivityinfo','block_mycourses');
        $mform->setDefault('config_showrecentactivity', 1);
        $mform->setType('config_showrecentactivity', PARAM_INT);

	//Headline for Settings, grouping
	$mform->addElement('html', '<b>'.get_string('settings_sorting', 'block_mycourses').'</b>');

	// Divide courses by category path?
        $mform->addElement('advcheckbox', 'config_dividebycategory', get_string('dividebycategory', 'block_mycourses'));
	$mform->addHelpButton('config_dividebycategory','dividebycategoryinfo','block_mycourses');
        $mform->setDefault('config_dividebycategory', 0);
        $mform->setType('config_dividebycategory', PARAM_INT);

	//Headline for Settings, others
	//$mform->addElement('html', '<b>anderes</b>');


    }

}