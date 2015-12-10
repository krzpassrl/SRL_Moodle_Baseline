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
 * This file contains the moodle hooks for the sepl module.
 *
 * It delegates most functions to the seplment class.
 *
 * @package   mod_sepl
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Adds an seplment instance
 *
 * This is done by calling the add_instance() method of the seplment type class
 * @param stdClass $data
 * @param mod_sepl_mod_form $form
 * @return int The instance id of the new seplment
 */
function sepl_add_instance(stdClass $data, mod_sepl_mod_form $form = null) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/sepl/locallib.php');

    $seplment = new sepl(context_module::instance($data->coursemodule), null, null);
    return $seplment->add_instance($data, true);
}

/**
 * delete an seplment instance
 * @param int $id
 * @return bool
 */
function sepl_delete_instance($id) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/sepl/locallib.php');
    $cm = get_coursemodule_from_instance('sepl', $id, 0, false, MUST_EXIST);
    $context = context_module::instance($cm->id);

    $seplment = new sepl($context, null, null);
    return $seplment->delete_instance();
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * This function will remove all seplment submissions and feedbacks in the database
 * and clean up any related data.
 *
 * @param stdClass $data the data submitted from the reset course.
 * @return array
 */
function sepl_reset_userdata($data) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/mod/sepl/locallib.php');

    $status = array();
    $params = array('courseid'=>$data->courseid);
    $sql = "SELECT a.id FROM {sepl} a WHERE a.course=:courseid";
    $course = $DB->get_record('course', array('id'=>$data->courseid), '*', MUST_EXIST);
    if ($sepls = $DB->get_records_sql($sql, $params)) {
        foreach ($sepls as $sepl) {
            $cm = get_coursemodule_from_instance('sepl',
                                                 $sepl->id,
                                                 $data->courseid,
                                                 false,
                                                 MUST_EXIST);
            $context = context_module::instance($cm->id);
            $seplment = new sepl($context, $cm, $course);
            $status = array_merge($status, $seplment->reset_userdata($data));
        }
    }
    return $status;
}

/**
 * This standard function will check all instances of this module
 * and make sure there are up-to-date events created for each of them.
 * If courseid = 0, then every seplment event in the site is checked, else
 * only seplment events belonging to the course specified are checked.
 *
 * @param int $courseid
 * @return bool
 */
function sepl_refresh_events($courseid = 0) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/mod/sepl/locallib.php');

    if ($courseid) {
        // Make sure that the course id is numeric.
        if (!is_numeric($courseid)) {
            return false;
        }
        if (!$sepls = $DB->get_records('sepl', array('course' => $courseid))) {
            return false;
        }
        // Get course from courseid parameter.
        if (!$course = $DB->get_record('course', array('id' => $courseid), '*')) {
            return false;
        }
    } else {
        if (!$sepls = $DB->get_records('sepl')) {
            return false;
        }
    }
    foreach ($sepls as $sepl) {
        // Use seplment's course column if courseid parameter is not given.
        if (!$courseid) {
            $courseid = $sepl->course;
            if (!$course = $DB->get_record('course', array('id' => $courseid), '*')) {
                continue;
            }
        }
        if (!$cm = get_coursemodule_from_instance('sepl', $sepl->id, $courseid, false)) {
            continue;
        }
        $context = context_module::instance($cm->id);
        $seplment = new sepl($context, $cm, $course);
        $seplment->update_calendar($cm->id);
    }

    return true;
}

/**
 * Removes all grades from gradebook
 *
 * @param int $courseid The ID of the course to reset
 * @param string $type Optional type of seplment to limit the reset to a particular seplment type
 */
function sepl_reset_gradebook($courseid, $type='') {
    global $CFG, $DB;

    $params = array('moduletype'=>'sepl', 'courseid'=>$courseid);
    $sql = 'SELECT a.*, cm.idnumber as cmidnumber, a.course as courseid
            FROM {sepl} a, {course_modules} cm, {modules} m
            WHERE m.name=:moduletype AND m.id=cm.module AND cm.instance=a.id AND a.course=:courseid';

    if ($seplments = $DB->get_records_sql($sql, $params)) {
        foreach ($seplments as $seplment) {
            sepl_grade_item_update($seplment, 'reset');
        }
    }
}

/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the seplment.
 * @param moodleform $mform form passed by reference
 */
function sepl_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'seplheader', get_string('modulenameplural', 'sepl'));
    $name = get_string('deleteallsubmissions', 'sepl');
    $mform->addElement('advcheckbox', 'reset_sepl_submissions', $name);
}

/**
 * Course reset form defaults.
 * @param  object $course
 * @return array
 */
function sepl_reset_course_form_defaults($course) {
    return array('reset_sepl_submissions'=>1);
}

/**
 * Update an seplment instance
 *
 * This is done by calling the update_instance() method of the seplment type class
 * @param stdClass $data
 * @param stdClass $form - unused
 * @return object
 */
function sepl_update_instance(stdClass $data, $form) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/sepl/locallib.php');
    $context = context_module::instance($data->coursemodule);
    $seplment = new sepl($context, null, null);
    return $seplment->update_instance($data);
}

/**
 * Return the list if Moodle features this module supports
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function sepl_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_GRADE_OUTCOMES:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_ADVANCED_GRADING:
            return true;
        case FEATURE_PLAGIARISM:
            return true;

        default:
            return null;
    }
}

/**
 * Lists all gradable areas for the advanced grading methods gramework
 *
 * @return array('string'=>'string') An array with area names as keys and descriptions as values
 */
function sepl_grading_areas_list() {
    return array('submissions'=>get_string('submissions', 'sepl'));
}


/**
 * extend an assigment navigation settings
 *
 * @param settings_navigation $settings
 * @param navigation_node $navref
 * @return void
 */
function sepl_extend_settings_navigation(settings_navigation $settings, navigation_node $navref) {
    global $PAGE, $DB;

    $cm = $PAGE->cm;
    if (!$cm) {
        return;
    }

    $context = $cm->context;
    $course = $PAGE->course;

    if (!$course) {
        return;
    }

    // Link to gradebook.
    if (has_capability('gradereport/grader:view', $cm->context) &&
            has_capability('moodle/grade:viewall', $cm->context)) {
        $link = new moodle_url('/grade/report/grader/index.php', array('id' => $course->id));
        $linkname = get_string('viewgradebook', 'sepl');
        $node = $navref->add($linkname, $link, navigation_node::TYPE_SETTING);
    }

    // Link to download all submissions.
    if (has_any_capability(array('mod/sepl:grade', 'mod/sepl:viewgrades'), $context)) {
        $link = new moodle_url('/mod/sepl/view.php', array('id' => $cm->id, 'action'=>'grading'));
        $node = $navref->add(get_string('viewgrading', 'sepl'), $link, navigation_node::TYPE_SETTING);

        $link = new moodle_url('/mod/sepl/view.php', array('id' => $cm->id, 'action'=>'downloadall'));
        $node = $navref->add(get_string('downloadall', 'sepl'), $link, navigation_node::TYPE_SETTING);
    }

    if (has_capability('mod/sepl:revealidentities', $context)) {
        $dbparams = array('id'=>$cm->instance);
        $seplment = $DB->get_record('sepl', $dbparams, 'blindmarking, revealidentities');

        if ($seplment && $seplment->blindmarking && !$seplment->revealidentities) {
            $urlparams = array('id' => $cm->id, 'action'=>'revealidentities');
            $url = new moodle_url('/mod/sepl/view.php', $urlparams);
            $linkname = get_string('revealidentities', 'sepl');
            $node = $navref->add($linkname, $url, navigation_node::TYPE_SETTING);
        }
    }
}

/**
 * Add a get_coursemodule_info function in case any seplment type wants to add 'extra' information
 * for the course (see resource).
 *
 * Given a course_module object, this function returns any "extra" information that may be needed
 * when printing this activity in a course listing.  See get_array_of_activities() in course/lib.php.
 *
 * @param stdClass $coursemodule The coursemodule object (record).
 * @return cached_cm_info An object on information that the courses
 *                        will know about (most noticeably, an icon).
 */
function sepl_get_coursemodule_info($coursemodule) {
    global $CFG, $DB;

    $dbparams = array('id'=>$coursemodule->instance);
    $fields = 'id, name, alwaysshowdescription, allowsubmissionsfromdate, intro, introformat';
    if (! $seplment = $DB->get_record('sepl', $dbparams, $fields)) {
        return false;
    }

    $result = new cached_cm_info();
    $result->name = $seplment->name;
    if ($coursemodule->showdescription) {
        if ($seplment->alwaysshowdescription || time() > $seplment->allowsubmissionsfromdate) {
            // Convert intro to html. Do not filter cached version, filters run at display time.
            $result->content = format_module_intro('sepl', $seplment, $coursemodule->id, false);
        }
    }
    return $result;
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function sepl_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $modulepagetype = array(
        'mod-sepl-*' => get_string('page-mod-sepl-x', 'sepl'),
        'mod-sepl-view' => get_string('page-mod-sepl-view', 'sepl'),
    );
    return $modulepagetype;
}

/**
 * Print an overview of all seplments
 * for the courses.
 *
 * @param mixed $courses The list of courses to print the overview for
 * @param array $htmlarray The array of html to return
 *
 * @return true
 */
function sepl_print_overview($courses, &$htmlarray) {
    global $CFG, $DB;

    if (empty($courses) || !is_array($courses) || count($courses) == 0) {
        return true;
    }

    if (!$seplments = get_all_instances_in_courses('sepl', $courses)) {
        return true;
    }

    $seplmentids = array();

    // Do seplment_base::isopen() here without loading the whole thing for speed.
    foreach ($seplments as $key => $seplment) {
        $time = time();
        $isopen = false;
        if ($seplment->duedate) {
            $duedate = false;
            if ($seplment->cutoffdate) {
                $duedate = $seplment->cutoffdate;
            }
            if ($duedate) {
                $isopen = ($seplment->allowsubmissionsfromdate <= $time && $time <= $duedate);
            } else {
                $isopen = ($seplment->allowsubmissionsfromdate <= $time);
            }
        }
        if ($isopen) {
            $seplmentids[] = $seplment->id;
        }
    }

    if (empty($seplmentids)) {
        // No seplments to look at - we're done.
        return true;
    }

    // Definitely something to print, now include the constants we need.
    require_once($CFG->dirroot . '/mod/sepl/locallib.php');

    $strduedate = get_string('duedate', 'sepl');
    $strcutoffdate = get_string('nosubmissionsacceptedafter', 'sepl');
    $strnolatesubmissions = get_string('nolatesubmissions', 'sepl');
    $strduedateno = get_string('duedateno', 'sepl');
    $strseplment = get_string('modulename', 'sepl');

    // We do all possible database work here *outside* of the loop to ensure this scales.
    list($sqlseplmentids, $seplmentidparams) = $DB->get_in_or_equal($seplmentids);

    $mysubmissions = null;
    $unmarkedsubmissions = null;

    foreach ($seplments as $seplment) {

        // Do not show seplments that are not open.
        if (!in_array($seplment->id, $seplmentids)) {
            continue;
        }

        $context = context_module::instance($seplment->coursemodule);

        // Does the submission status of the seplment require notification?
        if (has_capability('mod/sepl:submit', $context)) {
            // Does the submission status of the seplment require notification?
            $submitdetails = sepl_get_mysubmission_details_for_print_overview($mysubmissions, $sqlseplmentids,
                    $seplmentidparams, $seplment);
        } else {
            $submitdetails = false;
        }

        if (has_capability('mod/sepl:grade', $context)) {
            // Does the grading status of the seplment require notification ?
            $gradedetails = sepl_get_grade_details_for_print_overview($unmarkedsubmissions, $sqlseplmentids,
                    $seplmentidparams, $seplment, $context);
        } else {
            $gradedetails = false;
        }

        if (empty($submitdetails) && empty($gradedetails)) {
            // There is no need to display this seplment as there is nothing to notify.
            continue;
        }

        $dimmedclass = '';
        if (!$seplment->visible) {
            $dimmedclass = ' class="dimmed"';
        }
        $href = $CFG->wwwroot . '/mod/sepl/view.php?id=' . $seplment->coursemodule;
        $basestr = '<div class="sepl overview">' .
               '<div class="name">' .
               $strseplment . ': '.
               '<a ' . $dimmedclass .
                   'title="' . $strseplment . '" ' .
                   'href="' . $href . '">' .
               format_string($seplment->name) .
               '</a></div>';
        if ($seplment->duedate) {
            $userdate = userdate($seplment->duedate);
            $basestr .= '<div class="info">' . $strduedate . ': ' . $userdate . '</div>';
        } else {
            $basestr .= '<div class="info">' . $strduedateno . '</div>';
        }
        if ($seplment->cutoffdate) {
            if ($seplment->cutoffdate == $seplment->duedate) {
                $basestr .= '<div class="info">' . $strnolatesubmissions . '</div>';
            } else {
                $userdate = userdate($seplment->cutoffdate);
                $basestr .= '<div class="info">' . $strcutoffdate . ': ' . $userdate . '</div>';
            }
        }

        // Show only relevant information.
        if (!empty($submitdetails)) {
            $basestr .= $submitdetails;
        }

        if (!empty($gradedetails)) {
            $basestr .= $gradedetails;
        }
        $basestr .= '</div>';

        if (empty($htmlarray[$seplment->course]['sepl'])) {
            $htmlarray[$seplment->course]['sepl'] = $basestr;
        } else {
            $htmlarray[$seplment->course]['sepl'] .= $basestr;
        }
    }
    return true;
}

/**
 * This api generates html to be displayed to students in print overview section, related to their submission status of the given
 * seplment.
 *
 * @param array $mysubmissions list of submissions of current user indexed by seplment id.
 * @param string $sqlseplmentids sql clause used to filter open seplments.
 * @param array $seplmentidparams sql params used to filter open seplments.
 * @param stdClass $seplment current seplment
 *
 * @return bool|string html to display , false if nothing needs to be displayed.
 * @throws coding_exception
 */
function sepl_get_mysubmission_details_for_print_overview(&$mysubmissions, $sqlseplmentids, $seplmentidparams,
                                                            $seplment) {
    global $USER, $DB;

    if ($seplment->nosubmissions) {
        // Offline seplment. No need to display alerts for offline seplments.
        return false;
    }

    $strnotsubmittedyet = get_string('notsubmittedyet', 'sepl');

    if (!isset($mysubmissions)) {

        // Get all user submissions, indexed by seplment id.
        $dbparams = array_merge(array($USER->id), $seplmentidparams, array($USER->id));
        $mysubmissions = $DB->get_records_sql('SELECT a.id AS seplment,
                                                      a.nosubmissions AS nosubmissions,
                                                      g.timemodified AS timemarked,
                                                      g.grader AS grader,
                                                      g.grade AS grade,
                                                      s.status AS status
                                                 FROM {sepl} a, {sepl_submission} s
                                            LEFT JOIN {sepl_grades} g ON
                                                      g.seplment = s.seplment AND
                                                      g.userid = ? AND
                                                      g.attemptnumber = s.attemptnumber
                                                WHERE a.id ' . $sqlseplmentids . ' AND
                                                      s.latest = 1 AND
                                                      s.seplment = a.id AND
                                                      s.userid = ?', $dbparams);
    }

    $submitdetails = '';
    $submitdetails .= '<div class="details">';
    $submitdetails .= get_string('mysubmission', 'sepl');
    $submission = false;

    if (isset($mysubmissions[$seplment->id])) {
        $submission = $mysubmissions[$seplment->id];
    }

    if ($submission && $submission->status == ASSIGN_SUBMISSION_STATUS_SUBMITTED) {
        // A valid submission already exists, no need to notify students about this.
        return false;
    }

    // We need to show details only if a valid submission doesn't exist.
    if (!$submission ||
        !$submission->status ||
        $submission->status == ASSIGN_SUBMISSION_STATUS_DRAFT ||
        $submission->status == ASSIGN_SUBMISSION_STATUS_NEW
    ) {
        $submitdetails .= $strnotsubmittedyet;
    } else {
        $submitdetails .= get_string('submissionstatus_' . $submission->status, 'sepl');
    }
    if ($seplment->markingworkflow) {
        $workflowstate = $DB->get_field('sepl_user_flags', 'workflowstate', array('seplment' =>
                $seplment->id, 'userid' => $USER->id));
        if ($workflowstate) {
            $gradingstatus = 'markingworkflowstate' . $workflowstate;
        } else {
            $gradingstatus = 'markingworkflowstate' . ASSIGN_MARKING_WORKFLOW_STATE_NOTMARKED;
        }
    } else if (!empty($submission->grade) && $submission->grade !== null && $submission->grade >= 0) {
        $gradingstatus = ASSIGN_GRADING_STATUS_GRADED;
    } else {
        $gradingstatus = ASSIGN_GRADING_STATUS_NOT_GRADED;
    }
    $submitdetails .= ', ' . get_string($gradingstatus, 'sepl');
    $submitdetails .= '</div>';
    return $submitdetails;
}

/**
 * This api generates html to be displayed to teachers in print overview section, related to the grading status of the given
 * seplment's submissions.
 *
 * @param array $unmarkedsubmissions list of submissions of that are currently unmarked indexed by seplment id.
 * @param string $sqlseplmentids sql clause used to filter open seplments.
 * @param array $seplmentidparams sql params used to filter open seplments.
 * @param stdClass $seplment current seplment
 * @param context $context context of the seplment.
 *
 * @return bool|string html to display , false if nothing needs to be displayed.
 * @throws coding_exception
 */
function sepl_get_grade_details_for_print_overview(&$unmarkedsubmissions, $sqlseplmentids, $seplmentidparams,
                                                     $seplment, $context) {
    global $DB;
    if (!isset($unmarkedsubmissions)) {
        // Build up and array of unmarked submissions indexed by seplment id/ userid
        // for use where the user has grading rights on seplment.
        $dbparams = array_merge(array(ASSIGN_SUBMISSION_STATUS_SUBMITTED), $seplmentidparams);
        $rs = $DB->get_recordset_sql('SELECT s.seplment as seplment,
                                             s.userid as userid,
                                             s.id as id,
                                             s.status as status,
                                             g.timemodified as timegraded
                                        FROM {sepl_submission} s
                                   LEFT JOIN {sepl_grades} g ON
                                             s.userid = g.userid AND
                                             s.seplment = g.seplment AND
                                             g.attemptnumber = s.attemptnumber
                                       WHERE
                                             ( g.timemodified is NULL OR
                                             s.timemodified > g.timemodified OR
                                             g.grade IS NULL ) AND
                                             s.timemodified IS NOT NULL AND
                                             s.status = ? AND
                                             s.latest = 1 AND
                                             s.seplment ' . $sqlseplmentids, $dbparams);

        $unmarkedsubmissions = array();
        foreach ($rs as $rd) {
            $unmarkedsubmissions[$rd->seplment][$rd->userid] = $rd->id;
        }
        $rs->close();
    }

    // Count how many people can submit.
    $submissions = 0;
    if ($students = get_enrolled_users($context, 'mod/sepl:view', 0, 'u.id')) {
        foreach ($students as $student) {
            if (isset($unmarkedsubmissions[$seplment->id][$student->id])) {
                $submissions++;
            }
        }
    }

    if ($submissions) {
        $urlparams = array('id' => $seplment->coursemodule, 'action' => 'grading');
        $url = new moodle_url('/mod/sepl/view.php', $urlparams);
        $gradedetails = '<div class="details">' .
                '<a href="' . $url . '">' .
                get_string('submissionsnotgraded', 'sepl', $submissions) .
                '</a></div>';
        return $gradedetails;
    } else {
        return false;
    }

}

/**
 * Print recent activity from all seplments in a given course
 *
 * This is used by the recent activity block
 * @param mixed $course the course to print activity for
 * @param bool $viewfullnames boolean to determine whether to show full names or not
 * @param int $timestart the time the rendering started
 * @return bool true if activity was printed, false otherwise.
 */
function sepl_print_recent_activity($course, $viewfullnames, $timestart) {
    global $CFG, $USER, $DB, $OUTPUT;
    require_once($CFG->dirroot . '/mod/sepl/locallib.php');

    // Do not use log table if possible, it may be huge.

    $dbparams = array($timestart, $course->id, 'sepl', ASSIGN_SUBMISSION_STATUS_SUBMITTED);
    $namefields = user_picture::fields('u', null, 'userid');
    if (!$submissions = $DB->get_records_sql("SELECT asb.id, asb.timemodified, cm.id AS cmid,
                                                     $namefields
                                                FROM {sepl_submission} asb
                                                     JOIN {sepl} a      ON a.id = asb.seplment
                                                     JOIN {course_modules} cm ON cm.instance = a.id
                                                     JOIN {modules} md        ON md.id = cm.module
                                                     JOIN {user} u            ON u.id = asb.userid
                                               WHERE asb.timemodified > ? AND
                                                     asb.latest = 1 AND
                                                     a.course = ? AND
                                                     md.name = ? AND
                                                     asb.status = ?
                                            ORDER BY asb.timemodified ASC", $dbparams)) {
         return false;
    }

    $modinfo = get_fast_modinfo($course);
    $show    = array();
    $grader  = array();

    $showrecentsubmissions = get_config('sepl', 'showrecentsubmissions');

    foreach ($submissions as $submission) {
        if (!array_key_exists($submission->cmid, $modinfo->get_cms())) {
            continue;
        }
        $cm = $modinfo->get_cm($submission->cmid);
        if (!$cm->uservisible) {
            continue;
        }
        if ($submission->userid == $USER->id) {
            $show[] = $submission;
            continue;
        }

        $context = context_module::instance($submission->cmid);
        // The act of submitting of seplment may be considered private -
        // only graders will see it if specified.
        if (empty($showrecentsubmissions)) {
            if (!array_key_exists($cm->id, $grader)) {
                $grader[$cm->id] = has_capability('moodle/grade:viewall', $context);
            }
            if (!$grader[$cm->id]) {
                continue;
            }
        }

        $groupmode = groups_get_activity_groupmode($cm, $course);

        if ($groupmode == SEPARATEGROUPS &&
                !has_capability('moodle/site:accessallgroups',  $context)) {
            if (isguestuser()) {
                // Shortcut - guest user does not belong into any group.
                continue;
            }

            // This will be slow - show only users that share group with me in this cm.
            if (!$modinfo->get_groups($cm->groupingid)) {
                continue;
            }
            $usersgroups =  groups_get_all_groups($course->id, $submission->userid, $cm->groupingid);
            if (is_array($usersgroups)) {
                $usersgroups = array_keys($usersgroups);
                $intersect = array_intersect($usersgroups, $modinfo->get_groups($cm->groupingid));
                if (empty($intersect)) {
                    continue;
                }
            }
        }
        $show[] = $submission;
    }

    if (empty($show)) {
        return false;
    }

    echo $OUTPUT->heading(get_string('newsubmissions', 'sepl').':', 3);

    foreach ($show as $submission) {
        $cm = $modinfo->get_cm($submission->cmid);
        $context = context_module::instance($submission->cmid);
        $sepl = new sepl($context, $cm, $cm->course);
        $link = $CFG->wwwroot.'/mod/sepl/view.php?id='.$cm->id;
        // Obscure first and last name if blind marking enabled.
        if ($sepl->is_blind_marking()) {
            $submission->firstname = get_string('participant', 'mod_sepl');
            $submission->lastname = $sepl->get_uniqueid_for_user($submission->userid);
        }
        print_recent_activity_note($submission->timemodified,
                                   $submission,
                                   $cm->name,
                                   $link,
                                   false,
                                   $viewfullnames);
    }

    return true;
}

/**
 * Returns all seplments since a given time.
 *
 * @param array $activities The activity information is returned in this array
 * @param int $index The current index in the activities array
 * @param int $timestart The earliest activity to show
 * @param int $courseid Limit the search to this course
 * @param int $cmid The course module id
 * @param int $userid Optional user id
 * @param int $groupid Optional group id
 * @return void
 */
function sepl_get_recent_mod_activity(&$activities,
                                        &$index,
                                        $timestart,
                                        $courseid,
                                        $cmid,
                                        $userid=0,
                                        $groupid=0) {
    global $CFG, $COURSE, $USER, $DB;

    require_once($CFG->dirroot . '/mod/sepl/locallib.php');

    if ($COURSE->id == $courseid) {
        $course = $COURSE;
    } else {
        $course = $DB->get_record('course', array('id'=>$courseid));
    }

    $modinfo = get_fast_modinfo($course);

    $cm = $modinfo->get_cm($cmid);
    $params = array();
    if ($userid) {
        $userselect = 'AND u.id = :userid';
        $params['userid'] = $userid;
    } else {
        $userselect = '';
    }

    if ($groupid) {
        $groupselect = 'AND gm.groupid = :groupid';
        $groupjoin   = 'JOIN {groups_members} gm ON  gm.userid=u.id';
        $params['groupid'] = $groupid;
    } else {
        $groupselect = '';
        $groupjoin   = '';
    }

    $params['cminstance'] = $cm->instance;
    $params['timestart'] = $timestart;
    $params['submitted'] = ASSIGN_SUBMISSION_STATUS_SUBMITTED;

    $userfields = user_picture::fields('u', null, 'userid');

    if (!$submissions = $DB->get_records_sql('SELECT asb.id, asb.timemodified, ' .
                                                     $userfields .
                                             '  FROM {sepl_submission} asb
                                                JOIN {sepl} a ON a.id = asb.seplment
                                                JOIN {user} u ON u.id = asb.userid ' .
                                          $groupjoin .
                                            '  WHERE asb.timemodified > :timestart AND
                                                     asb.status = :submitted AND
                                                     a.id = :cminstance
                                                     ' . $userselect . ' ' . $groupselect .
                                            ' ORDER BY asb.timemodified ASC', $params)) {
         return;
    }

    $groupmode       = groups_get_activity_groupmode($cm, $course);
    $cmcontext      = context_module::instance($cm->id);
    $grader          = has_capability('moodle/grade:viewall', $cmcontext);
    $accessallgroups = has_capability('moodle/site:accessallgroups', $cmcontext);
    $viewfullnames   = has_capability('moodle/site:viewfullnames', $cmcontext);


    $showrecentsubmissions = get_config('sepl', 'showrecentsubmissions');
    $show = array();
    foreach ($submissions as $submission) {
        if ($submission->userid == $USER->id) {
            $show[] = $submission;
            continue;
        }
        // The act of submitting of seplment may be considered private -
        // only graders will see it if specified.
        if (empty($showrecentsubmissions)) {
            if (!$grader) {
                continue;
            }
        }

        if ($groupmode == SEPARATEGROUPS and !$accessallgroups) {
            if (isguestuser()) {
                // Shortcut - guest user does not belong into any group.
                continue;
            }

            // This will be slow - show only users that share group with me in this cm.
            if (!$modinfo->get_groups($cm->groupingid)) {
                continue;
            }
            $usersgroups =  groups_get_all_groups($course->id, $submission->userid, $cm->groupingid);
            if (is_array($usersgroups)) {
                $usersgroups = array_keys($usersgroups);
                $intersect = array_intersect($usersgroups, $modinfo->get_groups($cm->groupingid));
                if (empty($intersect)) {
                    continue;
                }
            }
        }
        $show[] = $submission;
    }

    if (empty($show)) {
        return;
    }

    if ($grader) {
        require_once($CFG->libdir.'/gradelib.php');
        $userids = array();
        foreach ($show as $id => $submission) {
            $userids[] = $submission->userid;
        }
        $grades = grade_get_grades($courseid, 'mod', 'sepl', $cm->instance, $userids);
    }

    $aname = format_string($cm->name, true);
    foreach ($show as $submission) {
        $activity = new stdClass();

        $activity->type         = 'sepl';
        $activity->cmid         = $cm->id;
        $activity->name         = $aname;
        $activity->sectionnum   = $cm->sectionnum;
        $activity->timestamp    = $submission->timemodified;
        $activity->user         = new stdClass();
        if ($grader) {
            $activity->grade = $grades->items[0]->grades[$submission->userid]->str_long_grade;
        }

        $userfields = explode(',', user_picture::fields());
        foreach ($userfields as $userfield) {
            if ($userfield == 'id') {
                // Aliased in SQL above.
                $activity->user->{$userfield} = $submission->userid;
            } else {
                $activity->user->{$userfield} = $submission->{$userfield};
            }
        }
        $activity->user->fullname = fullname($submission, $viewfullnames);

        $activities[$index++] = $activity;
    }

    return;
}

/**
 * Print recent activity from all seplments in a given course
 *
 * This is used by course/recent.php
 * @param stdClass $activity
 * @param int $courseid
 * @param bool $detail
 * @param array $modnames
 */
function sepl_print_recent_mod_activity($activity, $courseid, $detail, $modnames) {
    global $CFG, $OUTPUT;

    echo '<table border="0" cellpadding="3" cellspacing="0" class="seplment-recent">';

    echo '<tr><td class="userpicture" valign="top">';
    echo $OUTPUT->user_picture($activity->user);
    echo '</td><td>';

    if ($detail) {
        $modname = $modnames[$activity->type];
        echo '<div class="title">';
        echo '<img src="' . $OUTPUT->pix_url('icon', 'sepl') . '" '.
             'class="icon" alt="' . $modname . '">';
        echo '<a href="' . $CFG->wwwroot . '/mod/sepl/view.php?id=' . $activity->cmid . '">';
        echo $activity->name;
        echo '</a>';
        echo '</div>';
    }

    if (isset($activity->grade)) {
        echo '<div class="grade">';
        echo get_string('grade').': ';
        echo $activity->grade;
        echo '</div>';
    }

    echo '<div class="user">';
    echo "<a href=\"$CFG->wwwroot/user/view.php?id={$activity->user->id}&amp;course=$courseid\">";
    echo "{$activity->user->fullname}</a>  - " . userdate($activity->timestamp);
    echo '</div>';

    echo '</td></tr></table>';
}

/**
 * Checks if a scale is being used by an seplment.
 *
 * This is used by the backup code to decide whether to back up a scale
 * @param int $seplmentid
 * @param int $scaleid
 * @return boolean True if the scale is used by the seplment
 */
function sepl_scale_used($seplmentid, $scaleid) {
    global $DB;

    $return = false;
    $rec = $DB->get_record('sepl', array('id'=>$seplmentid, 'grade'=>-$scaleid));

    if (!empty($rec) && !empty($scaleid)) {
        $return = true;
    }

    return $return;
}

/**
 * Checks if scale is being used by any instance of seplment
 *
 * This is used to find out if scale used anywhere
 * @param int $scaleid
 * @return boolean True if the scale is used by any seplment
 */
function sepl_scale_used_anywhere($scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('sepl', array('grade'=>-$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function sepl_get_view_actions() {
    return array('view submission', 'view feedback');
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function sepl_get_post_actions() {
    return array('upload', 'submit', 'submit for grading');
}

/**
 * Call cron on the sepl module.
 */
function sepl_cron() {
    global $CFG;

    require_once($CFG->dirroot . '/mod/sepl/locallib.php');
    sepl::cron();

    $plugins = core_component::get_plugin_list('seplsubmission');

    foreach ($plugins as $name => $plugin) {
        $disabled = get_config('seplsubmission_' . $name, 'disabled');
        if (!$disabled) {
            $class = 'sepl_submission_' . $name;
            require_once($CFG->dirroot . '/mod/sepl/submission/' . $name . '/locallib.php');
            $class::cron();
        }
    }
    $plugins = core_component::get_plugin_list('seplfeedback');

    foreach ($plugins as $name => $plugin) {
        $disabled = get_config('seplfeedback_' . $name, 'disabled');
        if (!$disabled) {
            $class = 'sepl_feedback_' . $name;
            require_once($CFG->dirroot . '/mod/sepl/feedback/' . $name . '/locallib.php');
            $class::cron();
        }
    }

    return true;
}

/**
 * Returns all other capabilities used by this module.
 * @return array Array of capability strings
 */
function sepl_get_extra_capabilities() {
    return array('gradereport/grader:view',
                 'moodle/grade:viewall',
                 'moodle/site:viewfullnames',
                 'moodle/site:config');
}

/**
 * Create grade item for given seplment.
 *
 * @param stdClass $sepl record with extra cmidnumber
 * @param array $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function sepl_grade_item_update($sepl, $grades=null) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    if (!isset($sepl->courseid)) {
        $sepl->courseid = $sepl->course;
    }

    $params = array('itemname'=>$sepl->name, 'idnumber'=>$sepl->cmidnumber);

    // Check if feedback plugin for gradebook is enabled, if yes then
    // gradetype = GRADE_TYPE_TEXT else GRADE_TYPE_NONE.
    $gradefeedbackenabled = false;

    if (isset($sepl->gradefeedbackenabled)) {
        $gradefeedbackenabled = $sepl->gradefeedbackenabled;
    } else if ($sepl->grade == 0) { // Grade feedback is needed only when grade == 0.
        require_once($CFG->dirroot . '/mod/sepl/locallib.php');
        $mod = get_coursemodule_from_instance('sepl', $sepl->id, $sepl->courseid);
        $cm = context_module::instance($mod->id);
        $seplment = new sepl($cm, null, null);
        $gradefeedbackenabled = $seplment->is_gradebook_feedback_enabled();
    }

    if ($sepl->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $sepl->grade;
        $params['grademin']  = 0;

    } else if ($sepl->grade < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid']   = -$sepl->grade;

    } else if ($gradefeedbackenabled) {
        // $sepl->grade == 0 and feedback enabled.
        $params['gradetype'] = GRADE_TYPE_TEXT;
    } else {
        // $sepl->grade == 0 and no feedback enabled.
        $params['gradetype'] = GRADE_TYPE_NONE;
    }

    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/sepl',
                        $sepl->courseid,
                        'mod',
                        'sepl',
                        $sepl->id,
                        0,
                        $grades,
                        $params);
}

/**
 * Return grade for given user or all users.
 *
 * @param stdClass $sepl record of sepl with an additional cmidnumber
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function sepl_get_user_grades($sepl, $userid=0) {
    global $CFG;

    require_once($CFG->dirroot . '/mod/sepl/locallib.php');

    $cm = get_coursemodule_from_instance('sepl', $sepl->id, 0, false, MUST_EXIST);
    $context = context_module::instance($cm->id);
    $seplment = new sepl($context, null, null);
    $seplment->set_instance($sepl);
    return $seplment->get_user_grades_for_gradebook($userid);
}

/**
 * Update activity grades.
 *
 * @param stdClass $sepl database record
 * @param int $userid specific user only, 0 means all
 * @param bool $nullifnone - not used
 */
function sepl_update_grades($sepl, $userid=0, $nullifnone=true) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    if ($sepl->grade == 0) {
        sepl_grade_item_update($sepl);

    } else if ($grades = sepl_get_user_grades($sepl, $userid)) {
        foreach ($grades as $k => $v) {
            if ($v->rawgrade == -1) {
                $grades[$k]->rawgrade = null;
            }
        }
        sepl_grade_item_update($sepl, $grades);

    } else {
        sepl_grade_item_update($sepl);
    }
}

/**
 * List the file areas that can be browsed.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array
 */
function sepl_get_file_areas($course, $cm, $context) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/sepl/locallib.php');

    $areas = array(ASSIGN_INTROATTACHMENT_FILEAREA => get_string('introattachments', 'mod_sepl'));

    $seplment = new sepl($context, $cm, $course);
    foreach ($seplment->get_submission_plugins() as $plugin) {
        if ($plugin->is_visible()) {
            $pluginareas = $plugin->get_file_areas();

            if ($pluginareas) {
                $areas = array_merge($areas, $pluginareas);
            }
        }
    }
    foreach ($seplment->get_feedback_plugins() as $plugin) {
        if ($plugin->is_visible()) {
            $pluginareas = $plugin->get_file_areas();

            if ($pluginareas) {
                $areas = array_merge($areas, $pluginareas);
            }
        }
    }

    return $areas;
}

/**
 * File browsing support for sepl module.
 *
 * @param file_browser $browser
 * @param object $areas
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return object file_info instance or null if not found
 */
function sepl_get_file_info($browser,
                              $areas,
                              $course,
                              $cm,
                              $context,
                              $filearea,
                              $itemid,
                              $filepath,
                              $filename) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/sepl/locallib.php');

    if ($context->contextlevel != CONTEXT_MODULE) {
        return null;
    }

    $urlbase = $CFG->wwwroot.'/pluginfile.php';
    $fs = get_file_storage();
    $filepath = is_null($filepath) ? '/' : $filepath;
    $filename = is_null($filename) ? '.' : $filename;

    // Need to find where this belongs to.
    $seplment = new sepl($context, $cm, $course);
    if ($filearea === ASSIGN_INTROATTACHMENT_FILEAREA) {
        if (!has_capability('moodle/course:managefiles', $context)) {
            // Students can not peak here!
            return null;
        }
        if (!($storedfile = $fs->get_file($seplment->get_context()->id,
                                          'mod_sepl', $filearea, 0, $filepath, $filename))) {
            return null;
        }
        return new file_info_stored($browser,
                        $seplment->get_context(),
                        $storedfile,
                        $urlbase,
                        $filearea,
                        $itemid,
                        true,
                        true,
                        false);
    }

    $pluginowner = null;
    foreach ($seplment->get_submission_plugins() as $plugin) {
        if ($plugin->is_visible()) {
            $pluginareas = $plugin->get_file_areas();

            if (array_key_exists($filearea, $pluginareas)) {
                $pluginowner = $plugin;
                break;
            }
        }
    }
    if (!$pluginowner) {
        foreach ($seplment->get_feedback_plugins() as $plugin) {
            if ($plugin->is_visible()) {
                $pluginareas = $plugin->get_file_areas();

                if (array_key_exists($filearea, $pluginareas)) {
                    $pluginowner = $plugin;
                    break;
                }
            }
        }
    }

    if (!$pluginowner) {
        return null;
    }

    $result = $pluginowner->get_file_info($browser, $filearea, $itemid, $filepath, $filename);
    return $result;
}

/**
 * Prints the complete info about a user's interaction with an seplment.
 *
 * @param stdClass $course
 * @param stdClass $user
 * @param stdClass $coursemodule
 * @param stdClass $sepl the database sepl record
 *
 * This prints the submission summary and feedback summary for this student.
 */
function sepl_user_complete($course, $user, $coursemodule, $sepl) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/sepl/locallib.php');

    $context = context_module::instance($coursemodule->id);

    $seplment = new sepl($context, $coursemodule, $course);

    echo $seplment->view_student_summary($user, false);
}

/**
 * Print the grade information for the seplment for this user.
 *
 * @param stdClass $course
 * @param stdClass $user
 * @param stdClass $coursemodule
 * @param stdClass $seplment
 */
function sepl_user_outline($course, $user, $coursemodule, $seplment) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');
    require_once($CFG->dirroot.'/grade/grading/lib.php');

    $gradinginfo = grade_get_grades($course->id,
                                        'mod',
                                        'sepl',
                                        $seplment->id,
                                        $user->id);

    $gradingitem = $gradinginfo->items[0];
    $gradebookgrade = $gradingitem->grades[$user->id];

    if (empty($gradebookgrade->str_long_grade)) {
        return null;
    }
    $result = new stdClass();
    $result->info = get_string('outlinegrade', 'sepl', $gradebookgrade->str_long_grade);
    $result->time = $gradebookgrade->dategraded;

    return $result;
}

/**
 * Obtains the automatic completion state for this module based on any conditions
 * in sepl settings.
 *
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not, $type if conditions not set.
 */
function sepl_get_completion_state($course, $cm, $userid, $type) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/mod/sepl/locallib.php');

    $sepl = new sepl(null, $cm, $course);

    // If completion option is enabled, evaluate it and return true/false.
    if ($sepl->get_instance()->completionsubmit) {
        $submission = $sepl->get_user_submission($userid, false);
        return $submission && $submission->status == ASSIGN_SUBMISSION_STATUS_SUBMITTED;
    } else {
        // Completion option is not enabled so just return $type.
        return $type;
    }
}

/**
 * Serves intro attachment files.
 *
 * @param mixed $course course or id of the course
 * @param mixed $cm course module or id of the course module
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function sepl_pluginfile($course,
                $cm,
                context $context,
                $filearea,
                $args,
                $forcedownload,
                array $options=array()) {
    global $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, false, $cm);
    if (!has_capability('mod/sepl:view', $context)) {
        return false;
    }

    require_once($CFG->dirroot . '/mod/sepl/locallib.php');
    $sepl = new sepl($context, $cm, $course);

    if ($filearea !== ASSIGN_INTROATTACHMENT_FILEAREA) {
        return false;
    }
    if (!$sepl->show_intro()) {
        return false;
    }

    $itemid = (int)array_shift($args);
    if ($itemid != 0) {
        return false;
    }

    $relativepath = implode('/', $args);

    $fullpath = "/{$context->id}/mod_sepl/$filearea/$itemid/$relativepath";

    $fs = get_file_storage();
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }
    send_stored_file($file, 0, 0, $forcedownload, $options);
}
