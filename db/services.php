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
 * Web service for mod sepl
 * @package    mod_sepl
 * @subpackage db
 * @since      Moodle 2.4
 * @copyright  2012 Paul Charsley
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = array(

        'mod_sepl_get_grades' => array(
                'classname'   => 'mod_sepl_external',
                'methodname'  => 'get_grades',
                'classpath'   => 'mod/sepl/externallib.php',
                'description' => 'Returns grades from the seplment',
                'type'        => 'read'
        ),

        'mod_sepl_get_seplments' => array(
                'classname'   => 'mod_sepl_external',
                'methodname'  => 'get_seplments',
                'classpath'   => 'mod/sepl/externallib.php',
                'description' => 'Returns the courses and seplments for the users capability',
                'type'        => 'read'
        ),

        'mod_sepl_get_submissions' => array(
                'classname' => 'mod_sepl_external',
                'methodname' => 'get_submissions',
                'classpath' => 'mod/sepl/externallib.php',
                'description' => 'Returns the submissions for seplments',
                'type' => 'read'
        ),

        'mod_sepl_get_user_flags' => array(
                'classname' => 'mod_sepl_external',
                'methodname' => 'get_user_flags',
                'classpath' => 'mod/sepl/externallib.php',
                'description' => 'Returns the user flags for seplments',
                'type' => 'read'
        ),
	'mod_sepl_get_sphere' => array(
                'classname'   => 'mod_sepl_external',
                'methodname'  => 'get_sphere',
                'classpath'   => 'mod/sepl/externallib.php',
                'description' => 'Returns sphereengine data',
                'type'        => 'read'
        ),

        'mod_sepl_set_user_flags' => array(
                'classname'   => 'mod_sepl_external',
                'methodname'  => 'set_user_flags',
                'classpath'   => 'mod/sepl/externallib.php',
                'description' => 'Creates or updates user flags',
                'type'        => 'write',
                'capabilities'=> 'mod/sepl:grade'
        ),
	'mod_sepl_set_sphere' => array(
                'classname'   => 'mod_sepl_external',
                'methodname'  => 'set_sphere',
                'classpath'   => 'mod/sepl/externallib.php',
                'description' => 'Creates or updates sphereengine data',
                'type'        => 'write',
                'capabilities'=> 'mod/sepl:grade'
        ),

        'mod_sepl_get_user_mappings' => array(
                'classname' => 'mod_sepl_external',
                'methodname' => 'get_user_mappings',
                'classpath' => 'mod/sepl/externallib.php',
                'description' => 'Returns the blind marking mappings for seplments',
                'type' => 'read'
        ),

        'mod_sepl_revert_submissions_to_draft' => array(
                'classname' => 'mod_sepl_external',
                'methodname' => 'revert_submissions_to_draft',
                'classpath' => 'mod/sepl/externallib.php',
                'description' => 'Reverts the list of submissions to draft status',
                'type' => 'write'
        ),

        'mod_sepl_lock_submissions' => array(
                'classname' => 'mod_sepl_external',
                'methodname' => 'lock_submissions',
                'classpath' => 'mod/sepl/externallib.php',
                'description' => 'Prevent students from making changes to a list of submissions',
                'type' => 'write'
        ),

        'mod_sepl_unlock_submissions' => array(
                'classname' => 'mod_sepl_external',
                'methodname' => 'unlock_submissions',
                'classpath' => 'mod/sepl/externallib.php',
                'description' => 'Allow students to make changes to a list of submissions',
                'type' => 'write'
        ),

        'mod_sepl_save_submission' => array(
                'classname' => 'mod_sepl_external',
                'methodname' => 'save_submission',
                'classpath' => 'mod/sepl/externallib.php',
                'description' => 'Update the current students submission',
                'type' => 'write'
        ),

        'mod_sepl_submit_for_grading' => array(
                'classname' => 'mod_sepl_external',
                'methodname' => 'submit_for_grading',
                'classpath' => 'mod/sepl/externallib.php',
                'description' => 'Submit the current students seplment for grading',
                'type' => 'write'
        ),

        'mod_sepl_save_grade' => array(
                'classname' => 'mod_sepl_external',
                'methodname' => 'save_grade',
                'classpath' => 'mod/sepl/externallib.php',
                'description' => 'Save a grade update for a single student.',
                'type' => 'write'
        ),

        'mod_sepl_save_grades' => array(
                'classname' => 'mod_sepl_external',
                'methodname' => 'save_grades',
                'classpath' => 'mod/sepl/externallib.php',
                'description' => 'Save multiple grade updates for an seplment.',
                'type' => 'write'
        ),

        'mod_sepl_save_user_extensions' => array(
                'classname' => 'mod_sepl_external',
                'methodname' => 'save_user_extensions',
                'classpath' => 'mod/sepl/externallib.php',
                'description' => 'Save a list of seplment extensions',
                'type' => 'write'
        ),

        'mod_sepl_reveal_identities' => array(
                'classname' => 'mod_sepl_external',
                'methodname' => 'reveal_identities',
                'classpath' => 'mod/sepl/externallib.php',
                'description' => 'Reveal the identities for a blind marking seplment',
                'type' => 'write'
        ),

        'mod_sepl_view_grading_table' => array(
                'classname'     => 'mod_sepl_external',
                'methodname'    => 'view_grading_table',
                'classpath'     => 'mod/sepl/externallib.php',
                'description'   => 'Trigger the grading_table_viewed event.',
                'type'          => 'write',
                'capabilities'  => 'mod/sepl:view, mod/sepl:viewgrades'
        ),

);
