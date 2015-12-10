<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file adds the settings pages to the navigation menu
 *
 * @package   mod_sepl
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/sepl/adminlib.php');

$ADMIN->add('modsettings', new admin_category('modseplfolder', new lang_string('pluginname', 'mod_sepl'), $module->is_enabled() === false));

$settings = new admin_settingpage($section, get_string('settings', 'mod_sepl'), 'moodle/site:config', $module->is_enabled() === false);

if ($ADMIN->fulltree) {
    $menu = array();
    foreach (core_component::get_plugin_list('seplfeedback') as $type => $notused) {
        $visible = !get_config('seplfeedback_' . $type, 'disabled');
        if ($visible) {
            $menu['seplfeedback_' . $type] = new lang_string('pluginname', 'seplfeedback_' . $type);
        }
    }

    // The default here is feedback_comments (if it exists).
    $name = new lang_string('feedbackplugin', 'mod_sepl');
    $description = new lang_string('feedbackpluginforgradebook', 'mod_sepl');
    $settings->add(new admin_setting_configselect('sepl/feedback_plugin_for_gradebook',
                                                  $name,
                                                  $description,
                                                  'seplfeedback_comments',
                                                  $menu));

    $name = new lang_string('showrecentsubmissions', 'mod_sepl');
    $description = new lang_string('configshowrecentsubmissions', 'mod_sepl');
    $settings->add(new admin_setting_configcheckbox('sepl/showrecentsubmissions',
                                                    $name,
                                                    $description,
                                                    0));

    $name = new lang_string('sendsubmissionreceipts', 'mod_sepl');
    $description = new lang_string('sendsubmissionreceipts_help', 'mod_sepl');
    $settings->add(new admin_setting_configcheckbox('sepl/submissionreceipts',
                                                    $name,
                                                    $description,
                                                    1));

    $name = new lang_string('submissionstatement', 'mod_sepl');
    $description = new lang_string('submissionstatement_help', 'mod_sepl');
    $default = get_string('submissionstatementdefault', 'mod_sepl');
    $settings->add(new admin_setting_configtextarea('sepl/submissionstatement',
                                                    $name,
                                                    $description,
                                                    $default));

    $name = new lang_string('defaultsettings', 'mod_sepl');
    $description = new lang_string('defaultsettings_help', 'mod_sepl');
    $settings->add(new admin_setting_heading('defaultsettings', $name, $description));

    $name = new lang_string('alwaysshowdescription', 'mod_sepl');
    $description = new lang_string('alwaysshowdescription_help', 'mod_sepl');
    $setting = new admin_setting_configcheckbox('sepl/alwaysshowdescription',
                                                    $name,
                                                    $description,
                                                    1);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('allowsubmissionsfromdate', 'mod_sepl');
    $description = new lang_string('allowsubmissionsfromdate_help', 'mod_sepl');
    $setting = new admin_setting_configduration('sepl/allowsubmissionsfromdate',
                                                    $name,
                                                    $description,
                                                    0);
    $setting->set_enabled_flag_options(admin_setting_flag::ENABLED, true);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('duedate', 'mod_sepl');
    $description = new lang_string('duedate_help', 'mod_sepl');
    $setting = new admin_setting_configduration('sepl/duedate',
                                                    $name,
                                                    $description,
                                                    604800);
    $setting->set_enabled_flag_options(admin_setting_flag::ENABLED, true);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('cutoffdate', 'mod_sepl');
    $description = new lang_string('cutoffdate_help', 'mod_sepl');
    $setting = new admin_setting_configduration('sepl/cutoffdate',
                                                    $name,
                                                    $description,
                                                    1209600);
    $setting->set_enabled_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('submissiondrafts', 'mod_sepl');
    $description = new lang_string('submissiondrafts_help', 'mod_sepl');
    $setting = new admin_setting_configcheckbox('sepl/submissiondrafts',
                                                    $name,
                                                    $description,
                                                    0);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('requiresubmissionstatement', 'mod_sepl');
    $description = new lang_string('requiresubmissionstatement_help', 'mod_sepl');
    $setting = new admin_setting_configcheckbox('sepl/requiresubmissionstatement',
                                                    $name,
                                                    $description,
                                                    0);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    // Constants from "locallib.php".
    $options = array(
        'none' => get_string('attemptreopenmethod_none', 'mod_sepl'),
        'manual' => get_string('attemptreopenmethod_manual', 'mod_sepl'),
        'untilpass' => get_string('attemptreopenmethod_untilpass', 'mod_sepl')
    );
    $name = new lang_string('attemptreopenmethod', 'mod_sepl');
    $description = new lang_string('attemptreopenmethod_help', 'mod_sepl');
    $setting = new admin_setting_configselect('sepl/attemptreopenmethod',
                                                    $name,
                                                    $description,
                                                    'none',
                                                    $options);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    // Constants from "locallib.php".
    $options = array(-1 => get_string('unlimitedattempts', 'mod_sepl'));
    $options += array_combine(range(1, 30), range(1, 30));
    $name = new lang_string('maxattempts', 'mod_sepl');
    $description = new lang_string('maxattempts_help', 'mod_sepl');
    $setting = new admin_setting_configselect('sepl/maxattempts',
                                                    $name,
                                                    $description,
                                                    -1,
                                                    $options);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('teamsubmission', 'mod_sepl');
    $description = new lang_string('teamsubmission_help', 'mod_sepl');
    $setting = new admin_setting_configcheckbox('sepl/teamsubmission',
                                                    $name,
                                                    $description,
                                                    0);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('preventsubmissionnotingroup', 'mod_sepl');
    $description = new lang_string('preventsubmissionnotingroup_help', 'mod_sepl');
    $setting = new admin_setting_configcheckbox('sepl/preventsubmissionnotingroup',
        $name,
        $description,
        0);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('requireallteammemberssubmit', 'mod_sepl');
    $description = new lang_string('requireallteammemberssubmit_help', 'mod_sepl');
    $setting = new admin_setting_configcheckbox('sepl/requireallteammemberssubmit',
                                                    $name,
                                                    $description,
                                                    0);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('teamsubmissiongroupingid', 'mod_sepl');
    $description = new lang_string('teamsubmissiongroupingid_help', 'mod_sepl');
    $setting = new admin_setting_configempty('sepl/teamsubmissiongroupingid',
                                                    $name,
                                                    $description);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('sendnotifications', 'mod_sepl');
    $description = new lang_string('sendnotifications_help', 'mod_sepl');
    $setting = new admin_setting_configcheckbox('sepl/sendnotifications',
                                                    $name,
                                                    $description,
                                                    0);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('sendlatenotifications', 'mod_sepl');
    $description = new lang_string('sendlatenotifications_help', 'mod_sepl');
    $setting = new admin_setting_configcheckbox('sepl/sendlatenotifications',
                                                    $name,
                                                    $description,
                                                    0);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('sendstudentnotificationsdefault', 'mod_sepl');
    $description = new lang_string('sendstudentnotificationsdefault_help', 'mod_sepl');
    $setting = new admin_setting_configcheckbox('sepl/sendstudentnotifications',
                                                    $name,
                                                    $description,
                                                    1);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('blindmarking', 'mod_sepl');
    $description = new lang_string('blindmarking_help', 'mod_sepl');
    $setting = new admin_setting_configcheckbox('sepl/blindmarking',
                                                    $name,
                                                    $description,
                                                    0);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('markingworkflow', 'mod_sepl');
    $description = new lang_string('markingworkflow_help', 'mod_sepl');
    $setting = new admin_setting_configcheckbox('sepl/markingworkflow',
                                                    $name,
                                                    $description,
                                                    0);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);

    $name = new lang_string('markingallocation', 'mod_sepl');
    $description = new lang_string('markingallocation_help', 'mod_sepl');
    $setting = new admin_setting_configcheckbox('sepl/markingallocation',
                                                    $name,
                                                    $description,
                                                    0);
    $setting->set_advanced_flag_options(admin_setting_flag::ENABLED, false);
    $setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
    $settings->add($setting);
}

$ADMIN->add('modseplfolder', $settings);
// Tell core we already added the settings structure.
$settings = null;

$ADMIN->add('modseplfolder', new admin_category('seplsubmissionplugins',
    new lang_string('submissionplugins', 'sepl'), !$module->is_enabled()));
$ADMIN->add('seplsubmissionplugins', new sepl_admin_page_manage_sepl_plugins('seplsubmission'));
$ADMIN->add('modseplfolder', new admin_category('seplfeedbackplugins',
    new lang_string('feedbackplugins', 'sepl'), !$module->is_enabled()));
$ADMIN->add('seplfeedbackplugins', new sepl_admin_page_manage_sepl_plugins('seplfeedback'));

foreach (core_plugin_manager::instance()->get_plugins_of_type('seplsubmission') as $plugin) {
    /** @var \mod_sepl\plugininfo\seplsubmission $plugin */
    $plugin->load_settings($ADMIN, 'seplsubmissionplugins', $hassiteconfig);
}

foreach (core_plugin_manager::instance()->get_plugins_of_type('seplfeedback') as $plugin) {
    /** @var \mod_sepl\plugininfo\seplfeedback $plugin */
    $plugin->load_settings($ADMIN, 'seplfeedbackplugins', $hassiteconfig);
}
