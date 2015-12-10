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

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * External mod sepl functions unit tests
 *
 * @package mod_sepl
 * @category external
 * @copyright 2012 Paul Charsley
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_sepl_external_testcase extends externallib_advanced_testcase {

    /**
     * Tests set up
     */
    protected function setUp() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/sepl/externallib.php');
    }

    /**
     * Test get_grades
     */
    public function test_get_grades() {
        global $DB, $USER;

        $this->resetAfterTest(true);
        // Create a course and seplment.
        $coursedata['idnumber'] = 'idnumbercourse';
        $coursedata['fullname'] = 'Lightwork Course';
        $coursedata['summary'] = 'Lightwork Course description';
        $coursedata['summaryformat'] = FORMAT_MOODLE;
        $course = self::getDataGenerator()->create_course($coursedata);

        $sepldata['course'] = $course->id;
        $sepldata['name'] = 'lightwork seplment';

        $sepl = self::getDataGenerator()->create_module('sepl', $sepldata);

        // Create a manual enrolment record.
        $manualenroldata['enrol'] = 'manual';
        $manualenroldata['status'] = 0;
        $manualenroldata['courseid'] = $course->id;
        $enrolid = $DB->insert_record('enrol', $manualenroldata);

        // Create a teacher and give them capabilities.
        $context = context_course::instance($course->id);
        $roleid = $this->seplUserCapability('moodle/course:viewparticipants', $context->id, 3);
        $context = context_module::instance($sepl->cmid);
        $this->seplUserCapability('mod/sepl:grade', $context->id, $roleid);

        // Create the teacher's enrolment record.
        $userenrolmentdata['status'] = 0;
        $userenrolmentdata['enrolid'] = $enrolid;
        $userenrolmentdata['userid'] = $USER->id;
        $DB->insert_record('user_enrolments', $userenrolmentdata);

        // Create a student and give them 2 grades (for 2 attempts).
        $student = self::getDataGenerator()->create_user();

        $submission = new stdClass();
        $submission->seplment = $sepl->id;
        $submission->userid = $student->id;
        $submission->status = ASSIGN_SUBMISSION_STATUS_NEW;
        $submission->latest = 0;
        $submission->attemptnumber = 0;
        $submission->groupid = 0;
        $submission->timecreated = time();
        $submission->timemodified = time();
        $DB->insert_record('sepl_submission', $submission);

        $grade = new stdClass();
        $grade->seplment = $sepl->id;
        $grade->userid = $student->id;
        $grade->timecreated = time();
        $grade->timemodified = $grade->timecreated;
        $grade->grader = $USER->id;
        $grade->grade = 50;
        $grade->attemptnumber = 0;
        $DB->insert_record('sepl_grades', $grade);

        $submission = new stdClass();
        $submission->seplment = $sepl->id;
        $submission->userid = $student->id;
        $submission->status = ASSIGN_SUBMISSION_STATUS_NEW;
        $submission->latest = 1;
        $submission->attemptnumber = 1;
        $submission->groupid = 0;
        $submission->timecreated = time();
        $submission->timemodified = time();
        $DB->insert_record('sepl_submission', $submission);

        $grade = new stdClass();
        $grade->seplment = $sepl->id;
        $grade->userid = $student->id;
        $grade->timecreated = time();
        $grade->timemodified = $grade->timecreated;
        $grade->grader = $USER->id;
        $grade->grade = 75;
        $grade->attemptnumber = 1;
        $DB->insert_record('sepl_grades', $grade);

        $seplmentids[] = $sepl->id;
        $result = mod_sepl_external::get_grades($seplmentids);

        // We need to execute the return values cleaning process to simulate the web service server.
        $result = external_api::clean_returnvalue(mod_sepl_external::get_grades_returns(), $result);

        // Check that the correct grade information for the student is returned.
        $this->assertEquals(1, count($result['seplments']));
        $seplment = $result['seplments'][0];
        $this->assertEquals($sepl->id, $seplment['seplmentid']);
        // Should only get the last grade for this student.
        $this->assertEquals(1, count($seplment['grades']));
        $grade = $seplment['grades'][0];
        $this->assertEquals($student->id, $grade['userid']);
        // Should be the last grade (not the first).
        $this->assertEquals(75, $grade['grade']);
    }

    /**
     * Test get_seplments
     */
    public function test_get_seplments() {
        global $DB, $USER;

        $this->resetAfterTest(true);

        $category = self::getDataGenerator()->create_category(array(
            'name' => 'Test category'
        ));

        // Create a course.
        $course1 = self::getDataGenerator()->create_course(array(
            'idnumber' => 'idnumbercourse1',
            'fullname' => 'Lightwork Course 1',
            'summary' => 'Lightwork Course 1 description',
            'summaryformat' => FORMAT_MOODLE,
            'category' => $category->id
        ));

        // Create a second course, just for testing.
        $course2 = self::getDataGenerator()->create_course(array(
            'idnumber' => 'idnumbercourse2',
            'fullname' => 'Lightwork Course 2',
            'summary' => 'Lightwork Course 2 description',
            'summaryformat' => FORMAT_MOODLE,
            'category' => $category->id
        ));

        // Create the seplment module.
        $sepl1 = self::getDataGenerator()->create_module('sepl', array(
            'course' => $course1->id,
            'name' => 'lightwork seplment',
            'intro' => 'the seplment intro text here',
            'markingworkflow' => 1,
            'markingallocation' => 1
        ));

        // Create manual enrolment record.
        $enrolid = $DB->insert_record('enrol', (object)array(
            'enrol' => 'manual',
            'status' => 0,
            'courseid' => $course1->id
        ));

        // Create the user and give them capabilities.
        $context = context_course::instance($course1->id);
        $roleid = $this->seplUserCapability('moodle/course:view', $context->id);
        $context = context_module::instance($sepl1->cmid);
        $this->seplUserCapability('mod/sepl:view', $context->id, $roleid);

        // Create the user enrolment record.
        $DB->insert_record('user_enrolments', (object)array(
            'status' => 0,
            'enrolid' => $enrolid,
            'userid' => $USER->id
        ));

        // Add a file as seplment attachment.
        $filerecord = array('component' => 'mod_sepl', 'filearea' => ASSIGN_INTROATTACHMENT_FILEAREA,
                'contextid' => $context->id, 'itemid' => 0,
                'filename' => 'introattachment.txt', 'filepath' => '/');
        $fs = get_file_storage();
        $fs->create_file_from_string($filerecord, 'Test intro attachment file');

        $result = mod_sepl_external::get_seplments();

        // We need to execute the return values cleaning process to simulate the web service server.
        $result = external_api::clean_returnvalue(mod_sepl_external::get_seplments_returns(), $result);

        // Check the course and seplment are returned.
        $this->assertEquals(1, count($result['courses']));
        $course = $result['courses'][0];
        $this->assertEquals('Lightwork Course 1', $course['fullname']);
        $this->assertEquals(1, count($course['seplments']));
        $seplment = $course['seplments'][0];
        $this->assertEquals($sepl1->id, $seplment['id']);
        $this->assertEquals($course1->id, $seplment['course']);
        $this->assertEquals('lightwork seplment', $seplment['name']);
        $this->assertEquals('the seplment intro text here', $seplment['intro']);
        $this->assertEquals(1, $seplment['markingworkflow']);
        $this->assertEquals(1, $seplment['markingallocation']);

        $this->assertCount(1, $seplment['introattachments']);
        $this->assertEquals('introattachment.txt', $seplment['introattachments'][0]['filename']);

        // Now, hide the descritption until the submission from date.
        $DB->set_field('sepl', 'alwaysshowdescription', 0, array('id' => $sepl1->id));
        $DB->set_field('sepl', 'allowsubmissionsfromdate', time() + DAYSECS, array('id' => $sepl1->id));

        $result = mod_sepl_external::get_seplments(array($course1->id));

        // We need to execute the return values cleaning process to simulate the web service server.
        $result = external_api::clean_returnvalue(mod_sepl_external::get_seplments_returns(), $result);

        $this->assertEquals(1, count($result['courses']));
        $course = $result['courses'][0];
        $this->assertEquals('Lightwork Course 1', $course['fullname']);
        $this->assertEquals(1, count($course['seplments']));
        $seplment = $course['seplments'][0];
        $this->assertEquals($sepl1->id, $seplment['id']);
        $this->assertEquals($course1->id, $seplment['course']);
        $this->assertEquals('lightwork seplment', $seplment['name']);
        $this->assertArrayNotHasKey('intro', $seplment);
        $this->assertArrayNotHasKey('introattachments', $seplment);
        $this->assertEquals(1, $seplment['markingworkflow']);
        $this->assertEquals(1, $seplment['markingallocation']);

        $result = mod_sepl_external::get_seplments(array($course2->id));

        // We need to execute the return values cleaning process to simulate the web service server.
        $result = external_api::clean_returnvalue(mod_sepl_external::get_seplments_returns(), $result);

        $this->assertEquals(0, count($result['courses']));
        $this->assertEquals(1, count($result['warnings']));
    }

    /**
     * Test get_submissions
     */
    public function test_get_submissions() {
        global $DB, $USER;

        $this->resetAfterTest(true);
        // Create a course and seplment.
        $coursedata['idnumber'] = 'idnumbercourse1';
        $coursedata['fullname'] = 'Lightwork Course 1';
        $coursedata['summary'] = 'Lightwork Course 1 description';
        $coursedata['summaryformat'] = FORMAT_MOODLE;
        $course1 = self::getDataGenerator()->create_course($coursedata);

        $sepldata['course'] = $course1->id;
        $sepldata['name'] = 'lightwork seplment';

        $sepl1 = self::getDataGenerator()->create_module('sepl', $sepldata);

        // Create a student with an online text submission.
        // First attempt.
        $student = self::getDataGenerator()->create_user();
        $submission = new stdClass();
        $submission->seplment = $sepl1->id;
        $submission->userid = $student->id;
        $submission->timecreated = time();
        $submission->timemodified = $submission->timecreated;
        $submission->status = 'draft';
        $submission->attemptnumber = 0;
        $submission->latest = 0;
        $sid = $DB->insert_record('sepl_submission', $submission);

        // Second attempt.
        $submission = new stdClass();
        $submission->seplment = $sepl1->id;
        $submission->userid = $student->id;
        $submission->timecreated = time();
        $submission->timemodified = $submission->timecreated;
        $submission->status = 'submitted';
        $submission->attemptnumber = 1;
        $submission->latest = 1;
        $sid = $DB->insert_record('sepl_submission', $submission);
        $submission->id = $sid;

        $onlinetextsubmission = new stdClass();
        $onlinetextsubmission->onlinetext = "<p>online test text</p>";
        $onlinetextsubmission->onlineformat = 1;
        $onlinetextsubmission->submission = $submission->id;
        $onlinetextsubmission->seplment = $sepl1->id;
        $DB->insert_record('seplsubmission_onlinetext', $onlinetextsubmission);

        // Create manual enrolment record.
        $manualenroldata['enrol'] = 'manual';
        $manualenroldata['status'] = 0;
        $manualenroldata['courseid'] = $course1->id;
        $enrolid = $DB->insert_record('enrol', $manualenroldata);

        // Create a teacher and give them capabilities.
        $context = context_course::instance($course1->id);
        $roleid = $this->seplUserCapability('moodle/course:viewparticipants', $context->id, 3);
        $context = context_module::instance($sepl1->cmid);
        $this->seplUserCapability('mod/sepl:grade', $context->id, $roleid);

        // Create the teacher's enrolment record.
        $userenrolmentdata['status'] = 0;
        $userenrolmentdata['enrolid'] = $enrolid;
        $userenrolmentdata['userid'] = $USER->id;
        $DB->insert_record('user_enrolments', $userenrolmentdata);

        $seplmentids[] = $sepl1->id;
        $result = mod_sepl_external::get_submissions($seplmentids);
        $result = external_api::clean_returnvalue(mod_sepl_external::get_submissions_returns(), $result);

        // Check the online text submission is returned.
        $this->assertEquals(1, count($result['seplments']));
        $seplment = $result['seplments'][0];
        $this->assertEquals($sepl1->id, $seplment['seplmentid']);
        $this->assertEquals(1, count($seplment['submissions']));
        $submission = $seplment['submissions'][0];
        $this->assertEquals($sid, $submission['id']);
        $this->assertGreaterThanOrEqual(3, count($submission['plugins']));
        $plugins = $submission['plugins'];
        foreach ($plugins as $plugin) {
            $foundonlinetext = false;
            if ($plugin['type'] == 'onlinetext') {
                $foundonlinetext = true;
                break;
            }
        }
        $this->assertTrue($foundonlinetext);
    }

    /**
     * Test get_user_flags
     */
    public function test_get_user_flags() {
        global $DB, $USER;

        $this->resetAfterTest(true);
        // Create a course and seplment.
        $coursedata['idnumber'] = 'idnumbercourse';
        $coursedata['fullname'] = 'Lightwork Course';
        $coursedata['summary'] = 'Lightwork Course description';
        $coursedata['summaryformat'] = FORMAT_MOODLE;
        $course = self::getDataGenerator()->create_course($coursedata);

        $sepldata['course'] = $course->id;
        $sepldata['name'] = 'lightwork seplment';

        $sepl = self::getDataGenerator()->create_module('sepl', $sepldata);

        // Create a manual enrolment record.
        $manualenroldata['enrol'] = 'manual';
        $manualenroldata['status'] = 0;
        $manualenroldata['courseid'] = $course->id;
        $enrolid = $DB->insert_record('enrol', $manualenroldata);

        // Create a teacher and give them capabilities.
        $context = context_course::instance($course->id);
        $roleid = $this->seplUserCapability('moodle/course:viewparticipants', $context->id, 3);
        $context = context_module::instance($sepl->cmid);
        $this->seplUserCapability('mod/sepl:grade', $context->id, $roleid);

        // Create the teacher's enrolment record.
        $userenrolmentdata['status'] = 0;
        $userenrolmentdata['enrolid'] = $enrolid;
        $userenrolmentdata['userid'] = $USER->id;
        $DB->insert_record('user_enrolments', $userenrolmentdata);

        // Create a student and give them a user flag record.
        $student = self::getDataGenerator()->create_user();
        $userflag = new stdClass();
        $userflag->seplment = $sepl->id;
        $userflag->userid = $student->id;
        $userflag->locked = 0;
        $userflag->mailed = 0;
        $userflag->extensionduedate = 0;
        $userflag->workflowstate = 'inmarking';
        $userflag->allocatedmarker = $USER->id;

        $DB->insert_record('sepl_user_flags', $userflag);

        $seplmentids[] = $sepl->id;
        $result = mod_sepl_external::get_user_flags($seplmentids);

        // We need to execute the return values cleaning process to simulate the web service server.
        $result = external_api::clean_returnvalue(mod_sepl_external::get_user_flags_returns(), $result);

        // Check that the correct user flag information for the student is returned.
        $this->assertEquals(1, count($result['seplments']));
        $seplment = $result['seplments'][0];
        $this->assertEquals($sepl->id, $seplment['seplmentid']);
        // Should be one user flag record.
        $this->assertEquals(1, count($seplment['userflags']));
        $userflag = $seplment['userflags'][0];
        $this->assertEquals($student->id, $userflag['userid']);
        $this->assertEquals(0, $userflag['locked']);
        $this->assertEquals(0, $userflag['mailed']);
        $this->assertEquals(0, $userflag['extensionduedate']);
        $this->assertEquals('inmarking', $userflag['workflowstate']);
        $this->assertEquals($USER->id, $userflag['allocatedmarker']);
    }

    /**
     * Test get_user_mappings
     */
    public function test_get_user_mappings() {
        global $DB, $USER;

        $this->resetAfterTest(true);
        // Create a course and seplment.
        $coursedata['idnumber'] = 'idnumbercourse';
        $coursedata['fullname'] = 'Lightwork Course';
        $coursedata['summary'] = 'Lightwork Course description';
        $coursedata['summaryformat'] = FORMAT_MOODLE;
        $course = self::getDataGenerator()->create_course($coursedata);

        $sepldata['course'] = $course->id;
        $sepldata['name'] = 'lightwork seplment';

        $sepl = self::getDataGenerator()->create_module('sepl', $sepldata);

        // Create a manual enrolment record.
        $manualenroldata['enrol'] = 'manual';
        $manualenroldata['status'] = 0;
        $manualenroldata['courseid'] = $course->id;
        $enrolid = $DB->insert_record('enrol', $manualenroldata);

        // Create a teacher and give them capabilities.
        $context = context_course::instance($course->id);
        $roleid = $this->seplUserCapability('moodle/course:viewparticipants', $context->id, 3);
        $context = context_module::instance($sepl->cmid);
        $this->seplUserCapability('mod/sepl:revealidentities', $context->id, $roleid);

        // Create the teacher's enrolment record.
        $userenrolmentdata['status'] = 0;
        $userenrolmentdata['enrolid'] = $enrolid;
        $userenrolmentdata['userid'] = $USER->id;
        $DB->insert_record('user_enrolments', $userenrolmentdata);

        // Create a student and give them a user mapping record.
        $student = self::getDataGenerator()->create_user();
        $mapping = new stdClass();
        $mapping->seplment = $sepl->id;
        $mapping->userid = $student->id;

        $DB->insert_record('sepl_user_mapping', $mapping);

        $seplmentids[] = $sepl->id;
        $result = mod_sepl_external::get_user_mappings($seplmentids);

        // We need to execute the return values cleaning process to simulate the web service server.
        $result = external_api::clean_returnvalue(mod_sepl_external::get_user_mappings_returns(), $result);

        // Check that the correct user mapping information for the student is returned.
        $this->assertEquals(1, count($result['seplments']));
        $seplment = $result['seplments'][0];
        $this->assertEquals($sepl->id, $seplment['seplmentid']);
        // Should be one user mapping record.
        $this->assertEquals(1, count($seplment['mappings']));
        $mapping = $seplment['mappings'][0];
        $this->assertEquals($student->id, $mapping['userid']);
    }

    /**
     * Test lock_submissions
     */
    public function test_lock_submissions() {
        global $DB, $USER;

        $this->resetAfterTest(true);
        // Create a course and seplment and users.
        $course = self::getDataGenerator()->create_course();

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_sepl');
        $params['course'] = $course->id;
        $params['seplsubmission_onlinetext_enabled'] = 1;
        $instance = $generator->create_instance($params);
        $cm = get_coursemodule_from_instance('sepl', $instance->id);
        $context = context_module::instance($cm->id);

        $sepl = new sepl($context, $cm, $course);

        $student1 = self::getDataGenerator()->create_user();
        $student2 = self::getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname'=>'student'));
        $this->getDataGenerator()->enrol_user($student1->id,
                                              $course->id,
                                              $studentrole->id);
        $this->getDataGenerator()->enrol_user($student2->id,
                                              $course->id,
                                              $studentrole->id);
        $teacher = self::getDataGenerator()->create_user();
        $teacherrole = $DB->get_record('role', array('shortname'=>'teacher'));
        $this->getDataGenerator()->enrol_user($teacher->id,
                                              $course->id,
                                              $teacherrole->id);

        // Create a student1 with an online text submission.
        // Simulate a submission.
        $this->setUser($student1);
        $submission = $sepl->get_user_submission($student1->id, true);
        $data = new stdClass();
        $data->onlinetext_editor = array('itemid'=>file_get_unused_draft_itemid(),
                                         'text'=>'Submission text',
                                         'format'=>FORMAT_MOODLE);
        $plugin = $sepl->get_submission_plugin_by_type('onlinetext');
        $plugin->save($submission, $data);

        // Ready to test.
        $this->setUser($teacher);
        $students = array($student1->id, $student2->id);
        $result = mod_sepl_external::lock_submissions($instance->id, $students);
        $result = external_api::clean_returnvalue(mod_sepl_external::lock_submissions_returns(), $result);

        // Check for 0 warnings.
        $this->assertEquals(0, count($result));

        $this->setUser($student2);
        $submission = $sepl->get_user_submission($student2->id, true);
        $data = new stdClass();
        $data->onlinetext_editor = array('itemid'=>file_get_unused_draft_itemid(),
                                         'text'=>'Submission text',
                                         'format'=>FORMAT_MOODLE);
        $notices = array();
        $this->setExpectedException('moodle_exception');
        $sepl->save_submission($data, $notices);
    }

    /**
     * Test unlock_submissions
     */
    public function test_unlock_submissions() {
        global $DB, $USER;

        $this->resetAfterTest(true);
        // Create a course and seplment and users.
        $course = self::getDataGenerator()->create_course();

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_sepl');
        $params['course'] = $course->id;
        $params['seplsubmission_onlinetext_enabled'] = 1;
        $instance = $generator->create_instance($params);
        $cm = get_coursemodule_from_instance('sepl', $instance->id);
        $context = context_module::instance($cm->id);

        $sepl = new sepl($context, $cm, $course);

        $student1 = self::getDataGenerator()->create_user();
        $student2 = self::getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname'=>'student'));
        $this->getDataGenerator()->enrol_user($student1->id,
                                              $course->id,
                                              $studentrole->id);
        $this->getDataGenerator()->enrol_user($student2->id,
                                              $course->id,
                                              $studentrole->id);
        $teacher = self::getDataGenerator()->create_user();
        $teacherrole = $DB->get_record('role', array('shortname'=>'teacher'));
        $this->getDataGenerator()->enrol_user($teacher->id,
                                              $course->id,
                                              $teacherrole->id);

        // Create a student1 with an online text submission.
        // Simulate a submission.
        $this->setUser($student1);
        $submission = $sepl->get_user_submission($student1->id, true);
        $data = new stdClass();
        $data->onlinetext_editor = array('itemid'=>file_get_unused_draft_itemid(),
                                         'text'=>'Submission text',
                                         'format'=>FORMAT_MOODLE);
        $plugin = $sepl->get_submission_plugin_by_type('onlinetext');
        $plugin->save($submission, $data);

        // Ready to test.
        $this->setUser($teacher);
        $students = array($student1->id, $student2->id);
        $result = mod_sepl_external::lock_submissions($instance->id, $students);
        $result = external_api::clean_returnvalue(mod_sepl_external::lock_submissions_returns(), $result);

        // Check for 0 warnings.
        $this->assertEquals(0, count($result));

        $result = mod_sepl_external::unlock_submissions($instance->id, $students);
        $result = external_api::clean_returnvalue(mod_sepl_external::unlock_submissions_returns(), $result);

        // Check for 0 warnings.
        $this->assertEquals(0, count($result));

        $this->setUser($student2);
        $submission = $sepl->get_user_submission($student2->id, true);
        $data = new stdClass();
        $data->onlinetext_editor = array('itemid'=>file_get_unused_draft_itemid(),
                                         'text'=>'Submission text',
                                         'format'=>FORMAT_MOODLE);
        $notices = array();
        $sepl->save_submission($data, $notices);
    }

    /**
     * Test submit_for_grading
     */
    public function test_submit_for_grading() {
        global $DB, $USER;

        $this->resetAfterTest(true);
        // Create a course and seplment and users.
        $course = self::getDataGenerator()->create_course();

        set_config('submissionreceipts', 0, 'sepl');
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_sepl');
        $params['course'] = $course->id;
        $params['seplsubmission_onlinetext_enabled'] = 1;
        $params['submissiondrafts'] = 1;
        $params['sendnotifications'] = 0;
        $params['requiresubmissionstatement'] = 1;
        $instance = $generator->create_instance($params);
        $cm = get_coursemodule_from_instance('sepl', $instance->id);
        $context = context_module::instance($cm->id);

        $sepl = new sepl($context, $cm, $course);

        $student1 = self::getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname'=>'student'));
        $this->getDataGenerator()->enrol_user($student1->id,
                                              $course->id,
                                              $studentrole->id);

        // Create a student1 with an online text submission.
        // Simulate a submission.
        $this->setUser($student1);
        $submission = $sepl->get_user_submission($student1->id, true);
        $data = new stdClass();
        $data->onlinetext_editor = array('itemid'=>file_get_unused_draft_itemid(),
                                         'text'=>'Submission text',
                                         'format'=>FORMAT_MOODLE);
        $plugin = $sepl->get_submission_plugin_by_type('onlinetext');
        $plugin->save($submission, $data);

        $result = mod_sepl_external::submit_for_grading($instance->id, false);
        $result = external_api::clean_returnvalue(mod_sepl_external::submit_for_grading_returns(), $result);

        // Should be 1 fail because the submission statement was not aceptted.
        $this->assertEquals(1, count($result));

        $result = mod_sepl_external::submit_for_grading($instance->id, true);
        $result = external_api::clean_returnvalue(mod_sepl_external::submit_for_grading_returns(), $result);

        // Check for 0 warnings.
        $this->assertEquals(0, count($result));

        $submission = $sepl->get_user_submission($student1->id, false);

        $this->assertEquals(ASSIGN_SUBMISSION_STATUS_SUBMITTED, $submission->status);
    }

    /**
     * Test save_user_extensions
     */
    public function test_save_user_extensions() {
        global $DB, $USER;

        $this->resetAfterTest(true);
        // Create a course and seplment and users.
        $course = self::getDataGenerator()->create_course();

        $teacher = self::getDataGenerator()->create_user();
        $teacherrole = $DB->get_record('role', array('shortname'=>'teacher'));
        $this->getDataGenerator()->enrol_user($teacher->id,
                                              $course->id,
                                              $teacherrole->id);
        $this->setUser($teacher);

        $now = time();
        $yesterday = $now - 24*60*60;
        $tomorrow = $now + 24*60*60;
        set_config('submissionreceipts', 0, 'sepl');
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_sepl');
        $params['course'] = $course->id;
        $params['submissiondrafts'] = 1;
        $params['sendnotifications'] = 0;
        $params['duedate'] = $yesterday;
        $params['cutoffdate'] = $now - 10;
        $instance = $generator->create_instance($params);
        $cm = get_coursemodule_from_instance('sepl', $instance->id);
        $context = context_module::instance($cm->id);

        $sepl = new sepl($context, $cm, $course);

        $student1 = self::getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname'=>'student'));
        $this->getDataGenerator()->enrol_user($student1->id,
                                              $course->id,
                                              $studentrole->id);

        $this->setUser($student1);
        $result = mod_sepl_external::submit_for_grading($instance->id, true);
        $result = external_api::clean_returnvalue(mod_sepl_external::submit_for_grading_returns(), $result);

        // Check for 0 warnings.
        $this->assertEquals(1, count($result));

        $this->setUser($teacher);
        $result = mod_sepl_external::save_user_extensions($instance->id, array($student1->id), array($now, $tomorrow));
        $result = external_api::clean_returnvalue(mod_sepl_external::save_user_extensions_returns(), $result);
        $this->assertEquals(1, count($result));

        $this->setUser($teacher);
        $result = mod_sepl_external::save_user_extensions($instance->id, array($student1->id), array($yesterday - 10));
        $result = external_api::clean_returnvalue(mod_sepl_external::save_user_extensions_returns(), $result);
        $this->assertEquals(1, count($result));

        $this->setUser($teacher);
        $result = mod_sepl_external::save_user_extensions($instance->id, array($student1->id), array($tomorrow));
        $result = external_api::clean_returnvalue(mod_sepl_external::save_user_extensions_returns(), $result);
        $this->assertEquals(0, count($result));

        $this->setUser($student1);
        $result = mod_sepl_external::submit_for_grading($instance->id, true);
        $result = external_api::clean_returnvalue(mod_sepl_external::submit_for_grading_returns(), $result);
        $this->assertEquals(0, count($result));

        $this->setUser($student1);
        $result = mod_sepl_external::save_user_extensions($instance->id, array($student1->id), array($now, $tomorrow));
        $result = external_api::clean_returnvalue(mod_sepl_external::save_user_extensions_returns(), $result);

    }

    /**
     * Test reveal_identities
     */
    public function test_reveal_identities() {
        global $DB, $USER;

        $this->resetAfterTest(true);
        // Create a course and seplment and users.
        $course = self::getDataGenerator()->create_course();

        $teacher = self::getDataGenerator()->create_user();
        $teacherrole = $DB->get_record('role', array('shortname'=>'teacher'));
        $this->getDataGenerator()->enrol_user($teacher->id,
                                              $course->id,
                                              $teacherrole->id);
        $this->setUser($teacher);

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_sepl');
        $params['course'] = $course->id;
        $params['submissiondrafts'] = 1;
        $params['sendnotifications'] = 0;
        $params['blindmarking'] = 1;
        $instance = $generator->create_instance($params);
        $cm = get_coursemodule_from_instance('sepl', $instance->id);
        $context = context_module::instance($cm->id);

        $sepl = new sepl($context, $cm, $course);

        $student1 = self::getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname'=>'student'));
        $this->getDataGenerator()->enrol_user($student1->id,
                                              $course->id,
                                              $studentrole->id);

        $this->setUser($student1);
        $this->setExpectedException('required_capability_exception');
        $result = mod_sepl_external::reveal_identities($instance->id);
        $result = external_api::clean_returnvalue(mod_sepl_external::reveal_identities_returns(), $result);
        $this->assertEquals(1, count($result));
        $this->assertEquals(true, $sepl->is_blind_marking());

        $this->setUser($teacher);
        $result = mod_sepl_external::reveal_identities($instance->id);
        $result = external_api::clean_returnvalue(mod_sepl_external::reveal_identities_returns(), $result);
        $this->assertEquals(0, count($result));
        $this->assertEquals(false, $sepl->is_blind_marking());

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_sepl');
        $params['course'] = $course->id;
        $params['submissiondrafts'] = 1;
        $params['sendnotifications'] = 0;
        $params['blindmarking'] = 0;
        $instance = $generator->create_instance($params);
        $cm = get_coursemodule_from_instance('sepl', $instance->id);
        $context = context_module::instance($cm->id);

        $sepl = new sepl($context, $cm, $course);
        $result = mod_sepl_external::reveal_identities($instance->id);
        $result = external_api::clean_returnvalue(mod_sepl_external::reveal_identities_returns(), $result);
        $this->assertEquals(1, count($result));
        $this->assertEquals(false, $sepl->is_blind_marking());

    }

    /**
     * Test revert_submissions_to_draft
     */
    public function test_revert_submissions_to_draft() {
        global $DB, $USER;

        $this->resetAfterTest(true);
        set_config('submissionreceipts', 0, 'sepl');
        // Create a course and seplment and users.
        $course = self::getDataGenerator()->create_course();

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_sepl');
        $params['course'] = $course->id;
        $params['sendnotifications'] = 0;
        $params['submissiondrafts'] = 1;
        $instance = $generator->create_instance($params);
        $cm = get_coursemodule_from_instance('sepl', $instance->id);
        $context = context_module::instance($cm->id);

        $sepl = new sepl($context, $cm, $course);

        $student1 = self::getDataGenerator()->create_user();
        $student2 = self::getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname'=>'student'));
        $this->getDataGenerator()->enrol_user($student1->id,
                                              $course->id,
                                              $studentrole->id);
        $this->getDataGenerator()->enrol_user($student2->id,
                                              $course->id,
                                              $studentrole->id);
        $teacher = self::getDataGenerator()->create_user();
        $teacherrole = $DB->get_record('role', array('shortname'=>'teacher'));
        $this->getDataGenerator()->enrol_user($teacher->id,
                                              $course->id,
                                              $teacherrole->id);

        // Create a student1 with an online text submission.
        // Simulate a submission.
        $this->setUser($student1);
        $result = mod_sepl_external::submit_for_grading($instance->id, true);
        $result = external_api::clean_returnvalue(mod_sepl_external::submit_for_grading_returns(), $result);
        $this->assertEquals(0, count($result));

        // Ready to test.
        $this->setUser($teacher);
        $students = array($student1->id, $student2->id);
        $result = mod_sepl_external::revert_submissions_to_draft($instance->id, array($student1->id));
        $result = external_api::clean_returnvalue(mod_sepl_external::revert_submissions_to_draft_returns(), $result);

        // Check for 0 warnings.
        $this->assertEquals(0, count($result));

    }

    /**
     * Test save_submission
     */
    public function test_save_submission() {
        global $DB, $USER;

        $this->resetAfterTest(true);
        // Create a course and seplment and users.
        $course = self::getDataGenerator()->create_course();

        $teacher = self::getDataGenerator()->create_user();
        $teacherrole = $DB->get_record('role', array('shortname'=>'teacher'));
        $this->getDataGenerator()->enrol_user($teacher->id,
                                              $course->id,
                                              $teacherrole->id);
        $this->setUser($teacher);

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_sepl');
        $params['course'] = $course->id;
        $params['seplsubmission_onlinetext_enabled'] = 1;
        $params['seplsubmission_file_enabled'] = 1;
        $params['seplsubmission_file_maxfiles'] = 5;
        $params['seplsubmission_file_maxsizebytes'] = 1024*1024;
        $instance = $generator->create_instance($params);
        $cm = get_coursemodule_from_instance('sepl', $instance->id);
        $context = context_module::instance($cm->id);

        $sepl = new sepl($context, $cm, $course);

        $student1 = self::getDataGenerator()->create_user();
        $student2 = self::getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname'=>'student'));
        $this->getDataGenerator()->enrol_user($student1->id,
                                              $course->id,
                                              $studentrole->id);
        $this->getDataGenerator()->enrol_user($student2->id,
                                              $course->id,
                                              $studentrole->id);
        // Create a student1 with an online text submission.
        // Simulate a submission.
        $this->setUser($student1);

        // Create a file in a draft area.
        $draftidfile = file_get_unused_draft_itemid();

        $usercontext = context_user::instance($student1->id);
        $filerecord = array(
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea'  => 'draft',
            'itemid'    => $draftidfile,
            'filepath'  => '/',
            'filename'  => 'testtext.txt',
        );

        $fs = get_file_storage();
        $fs->create_file_from_string($filerecord, 'text contents');

        // Create another file in a different draft area.
        $draftidonlinetext = file_get_unused_draft_itemid();

        $filerecord = array(
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea'  => 'draft',
            'itemid'    => $draftidonlinetext,
            'filepath'  => '/',
            'filename'  => 'shouldbeanimage.txt',
        );

        $fs->create_file_from_string($filerecord, 'image contents (not really)');

        // Now try a submission.
        $submissionpluginparams = array();
        $submissionpluginparams['files_filemanager'] = $draftidfile;
        $onlinetexteditorparams = array('text'=>'Yeeha!',
                                        'format'=>1,
                                        'itemid'=>$draftidonlinetext);
        $submissionpluginparams['onlinetext_editor'] = $onlinetexteditorparams;
        $result = mod_sepl_external::save_submission($instance->id, $submissionpluginparams);
        $result = external_api::clean_returnvalue(mod_sepl_external::save_submission_returns(), $result);

        $this->assertEquals(0, count($result));

    }

    /**
     * Test save_grade
     */
    public function test_save_grade() {
        global $DB, $USER;

        $this->resetAfterTest(true);
        // Create a course and seplment and users.
        $course = self::getDataGenerator()->create_course();

        $teacher = self::getDataGenerator()->create_user();
        $teacherrole = $DB->get_record('role', array('shortname'=>'teacher'));
        $this->getDataGenerator()->enrol_user($teacher->id,
                                              $course->id,
                                              $teacherrole->id);
        $this->setUser($teacher);

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_sepl');
        $params['course'] = $course->id;
        $params['seplfeedback_file_enabled'] = 1;
        $params['seplfeedback_comments_enabled'] = 1;
        $instance = $generator->create_instance($params);
        $cm = get_coursemodule_from_instance('sepl', $instance->id);
        $context = context_module::instance($cm->id);

        $sepl = new sepl($context, $cm, $course);

        $student1 = self::getDataGenerator()->create_user();
        $student2 = self::getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($student1->id,
                                              $course->id,
                                              $studentrole->id);
        $this->getDataGenerator()->enrol_user($student2->id,
                                              $course->id,
                                              $studentrole->id);
        // Simulate a grade.
        $this->setUser($teacher);

        // Create a file in a draft area.
        $draftidfile = file_get_unused_draft_itemid();

        $usercontext = context_user::instance($teacher->id);
        $filerecord = array(
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea'  => 'draft',
            'itemid'    => $draftidfile,
            'filepath'  => '/',
            'filename'  => 'testtext.txt',
        );

        $fs = get_file_storage();
        $fs->create_file_from_string($filerecord, 'text contents');

        // Now try a grade.
        $feedbackpluginparams = array();
        $feedbackpluginparams['files_filemanager'] = $draftidfile;
        $feedbackeditorparams = array('text' => 'Yeeha!',
                                        'format' => 1);
        $feedbackpluginparams['seplfeedbackcomments_editor'] = $feedbackeditorparams;
        $result = mod_sepl_external::save_grade($instance->id,
                                                  $student1->id,
                                                  50.0,
                                                  -1,
                                                  true,
                                                  'released',
                                                  false,
                                                  $feedbackpluginparams);
        // No warnings.
        $this->assertNull($result);

        $result = mod_sepl_external::get_grades(array($instance->id));
        $result = external_api::clean_returnvalue(mod_sepl_external::get_grades_returns(), $result);

        $this->assertEquals($result['seplments'][0]['grades'][0]['grade'], '50.0');
    }

    /**
     * Test save grades with advanced grading data
     */
    public function test_save_grades_with_advanced_grading() {
        global $DB, $USER;

        $this->resetAfterTest(true);
        // Create a course and seplment and users.
        $course = self::getDataGenerator()->create_course();

        $teacher = self::getDataGenerator()->create_user();
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $this->getDataGenerator()->enrol_user($teacher->id,
                                              $course->id,
                                              $teacherrole->id);

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_sepl');
        $params['course'] = $course->id;
        $params['seplfeedback_file_enabled'] = 0;
        $params['seplfeedback_comments_enabled'] = 0;
        $instance = $generator->create_instance($params);
        $cm = get_coursemodule_from_instance('sepl', $instance->id);
        $context = context_module::instance($cm->id);

        $sepl = new sepl($context, $cm, $course);

        $student1 = self::getDataGenerator()->create_user();
        $student2 = self::getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($student1->id,
                                              $course->id,
                                              $studentrole->id);
        $this->getDataGenerator()->enrol_user($student2->id,
                                              $course->id,
                                              $studentrole->id);

        $this->setUser($teacher);

        $feedbackpluginparams = array();
        $feedbackpluginparams['files_filemanager'] = 0;
        $feedbackeditorparams = array('text' => '', 'format' => 1);
        $feedbackpluginparams['seplfeedbackcomments_editor'] = $feedbackeditorparams;

        // Create advanced grading data.
        // Create grading area.
        $gradingarea = array(
            'contextid' => $context->id,
            'component' => 'mod_sepl',
            'areaname' => 'submissions',
            'activemethod' => 'rubric'
        );
        $areaid = $DB->insert_record('grading_areas', $gradingarea);

        // Create a rubric grading definition.
        $rubricdefinition = array (
            'areaid' => $areaid,
            'method' => 'rubric',
            'name' => 'test',
            'status' => 20,
            'copiedfromid' => 1,
            'timecreated' => 1,
            'usercreated' => $teacher->id,
            'timemodified' => 1,
            'usermodified' => $teacher->id,
            'timecopied' => 0
        );
        $definitionid = $DB->insert_record('grading_definitions', $rubricdefinition);

        // Create a criterion with a level.
        $rubriccriteria = array (
             'definitionid' => $definitionid,
             'sortorder' => 1,
             'description' => 'Demonstrate an understanding of disease control',
             'descriptionformat' => 0
        );
        $criterionid = $DB->insert_record('gradingform_rubric_criteria', $rubriccriteria);
        $rubriclevel1 = array (
            'criterionid' => $criterionid,
            'score' => 50,
            'definition' => 'pass',
            'definitionformat' => 0
        );
        $rubriclevel2 = array (
            'criterionid' => $criterionid,
            'score' => 100,
            'definition' => 'excellent',
            'definitionformat' => 0
        );
        $rubriclevel3 = array (
            'criterionid' => $criterionid,
            'score' => 0,
            'definition' => 'fail',
            'definitionformat' => 0
        );
        $levelid1 = $DB->insert_record('gradingform_rubric_levels', $rubriclevel1);
        $levelid2 = $DB->insert_record('gradingform_rubric_levels', $rubriclevel2);
        $levelid3 = $DB->insert_record('gradingform_rubric_levels', $rubriclevel3);

        // Create the filling.
        $student1filling = array (
            'criterionid' => $criterionid,
            'levelid' => $levelid1,
            'remark' => 'well done you passed',
            'remarkformat' => 0
        );

        $student2filling = array (
            'criterionid' => $criterionid,
            'levelid' => $levelid2,
            'remark' => 'Excellent work',
            'remarkformat' => 0
        );

        $student1criteria = array(array('criterionid' => $criterionid, 'fillings' => array($student1filling)));
        $student1advancedgradingdata = array('rubric' => array('criteria' => $student1criteria));

        $student2criteria = array(array('criterionid' => $criterionid, 'fillings' => array($student2filling)));
        $student2advancedgradingdata = array('rubric' => array('criteria' => $student2criteria));

        $grades = array();
        $student1gradeinfo = array();
        $student1gradeinfo['userid'] = $student1->id;
        $student1gradeinfo['grade'] = 0; // Ignored since advanced grading is being used.
        $student1gradeinfo['attemptnumber'] = -1;
        $student1gradeinfo['addattempt'] = true;
        $student1gradeinfo['workflowstate'] = 'released';
        $student1gradeinfo['plugindata'] = $feedbackpluginparams;
        $student1gradeinfo['advancedgradingdata'] = $student1advancedgradingdata;
        $grades[] = $student1gradeinfo;

        $student2gradeinfo = array();
        $student2gradeinfo['userid'] = $student2->id;
        $student2gradeinfo['grade'] = 0; // Ignored since advanced grading is being used.
        $student2gradeinfo['attemptnumber'] = -1;
        $student2gradeinfo['addattempt'] = true;
        $student2gradeinfo['workflowstate'] = 'released';
        $student2gradeinfo['plugindata'] = $feedbackpluginparams;
        $student2gradeinfo['advancedgradingdata'] = $student2advancedgradingdata;
        $grades[] = $student2gradeinfo;

        $result = mod_sepl_external::save_grades($instance->id, false, $grades);
        $this->assertNull($result);

        $student1grade = $DB->get_record('sepl_grades',
                                         array('userid' => $student1->id, 'seplment' => $instance->id),
                                         '*',
                                         MUST_EXIST);
        $this->assertEquals($student1grade->grade, '50.0');

        $student2grade = $DB->get_record('sepl_grades',
                                         array('userid' => $student2->id, 'seplment' => $instance->id),
                                         '*',
                                         MUST_EXIST);
        $this->assertEquals($student2grade->grade, '100.0');
    }

    /**
     * Test save grades for a team submission
     */
    public function test_save_grades_with_group_submission() {
        global $DB, $USER, $CFG;
        require_once($CFG->dirroot . '/group/lib.php');

        $this->resetAfterTest(true);
        // Create a course and seplment and users.
        $course = self::getDataGenerator()->create_course();

        $teacher = self::getDataGenerator()->create_user();
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $this->getDataGenerator()->enrol_user($teacher->id,
                                              $course->id,
                                              $teacherrole->id);

        $groupingdata = array();
        $groupingdata['courseid'] = $course->id;
        $groupingdata['name'] = 'Group seplment grouping';

        $grouping = self::getDataGenerator()->create_grouping($groupingdata);

        $group1data = array();
        $group1data['courseid'] = $course->id;
        $group1data['name'] = 'Team 1';
        $group2data = array();
        $group2data['courseid'] = $course->id;
        $group2data['name'] = 'Team 2';

        $group1 = self::getDataGenerator()->create_group($group1data);
        $group2 = self::getDataGenerator()->create_group($group2data);

        groups_sepl_grouping($grouping->id, $group1->id);
        groups_sepl_grouping($grouping->id, $group2->id);

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_sepl');
        $params['course'] = $course->id;
        $params['teamsubmission'] = 1;
        $params['teamsubmissiongroupingid'] = $grouping->id;
        $instance = $generator->create_instance($params);
        $cm = get_coursemodule_from_instance('sepl', $instance->id);
        $context = context_module::instance($cm->id);

        $sepl = new sepl($context, $cm, $course);

        $student1 = self::getDataGenerator()->create_user();
        $student2 = self::getDataGenerator()->create_user();
        $student3 = self::getDataGenerator()->create_user();
        $student4 = self::getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($student1->id,
                                              $course->id,
                                              $studentrole->id);
        $this->getDataGenerator()->enrol_user($student2->id,
                                              $course->id,
                                              $studentrole->id);
        $this->getDataGenerator()->enrol_user($student3->id,
                                              $course->id,
                                              $studentrole->id);
        $this->getDataGenerator()->enrol_user($student4->id,
                                              $course->id,
                                              $studentrole->id);

        groups_add_member($group1->id, $student1->id);
        groups_add_member($group1->id, $student2->id);
        groups_add_member($group1->id, $student3->id);
        groups_add_member($group2->id, $student4->id);
        $this->setUser($teacher);

        $feedbackpluginparams = array();
        $feedbackpluginparams['files_filemanager'] = 0;
        $feedbackeditorparams = array('text' => '', 'format' => 1);
        $feedbackpluginparams['seplfeedbackcomments_editor'] = $feedbackeditorparams;

        $grades1 = array();
        $student1gradeinfo = array();
        $student1gradeinfo['userid'] = $student1->id;
        $student1gradeinfo['grade'] = 50;
        $student1gradeinfo['attemptnumber'] = -1;
        $student1gradeinfo['addattempt'] = true;
        $student1gradeinfo['workflowstate'] = 'released';
        $student1gradeinfo['plugindata'] = $feedbackpluginparams;
        $grades1[] = $student1gradeinfo;

        $student2gradeinfo = array();
        $student2gradeinfo['userid'] = $student2->id;
        $student2gradeinfo['grade'] = 75;
        $student2gradeinfo['attemptnumber'] = -1;
        $student2gradeinfo['addattempt'] = true;
        $student2gradeinfo['workflowstate'] = 'released';
        $student2gradeinfo['plugindata'] = $feedbackpluginparams;
        $grades1[] = $student2gradeinfo;

        $this->setExpectedException('invalid_parameter_exception');
        // Expect an exception since 2 grades have been submitted for the same team.
        $result = mod_sepl_external::save_grades($instance->id, true, $grades1);
        $result = external_api::clean_returnvalue(mod_sepl_external::save_grades_returns(), $result);

        $grades2 = array();
        $student3gradeinfo = array();
        $student3gradeinfo['userid'] = $student3->id;
        $student3gradeinfo['grade'] = 50;
        $student3gradeinfo['attemptnumber'] = -1;
        $student3gradeinfo['addattempt'] = true;
        $student3gradeinfo['workflowstate'] = 'released';
        $student3gradeinfo['plugindata'] = $feedbackpluginparams;
        $grades2[] = $student3gradeinfo;

        $student4gradeinfo = array();
        $student4gradeinfo['userid'] = $student4->id;
        $student4gradeinfo['grade'] = 75;
        $student4gradeinfo['attemptnumber'] = -1;
        $student4gradeinfo['addattempt'] = true;
        $student4gradeinfo['workflowstate'] = 'released';
        $student4gradeinfo['plugindata'] = $feedbackpluginparams;
        $grades2[] = $student4gradeinfo;
        $result = mod_sepl_external::save_grades($instance->id, true, $grades2);
        $result = external_api::clean_returnvalue(mod_sepl_external::save_grades_returns(), $result);
        // There should be no warnings.
        $this->assertEquals(0, count($result));

        $student3grade = $DB->get_record('sepl_grades',
                                         array('userid' => $student3->id, 'seplment' => $instance->id),
                                         '*',
                                         MUST_EXIST);
        $this->assertEquals($student3grade->grade, '50.0');

        $student4grade = $DB->get_record('sepl_grades',
                                         array('userid' => $student4->id, 'seplment' => $instance->id),
                                         '*',
                                         MUST_EXIST);
        $this->assertEquals($student4grade->grade, '75.0');
    }

    /**
     * Test copy_previous_attempt
     */
    public function test_copy_previous_attempt() {
        global $DB, $USER;

        $this->resetAfterTest(true);
        // Create a course and seplment and users.
        $course = self::getDataGenerator()->create_course();

        $teacher = self::getDataGenerator()->create_user();
        $teacherrole = $DB->get_record('role', array('shortname'=>'teacher'));
        $this->getDataGenerator()->enrol_user($teacher->id,
                                              $course->id,
                                              $teacherrole->id);
        $this->setUser($teacher);

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_sepl');
        $params['course'] = $course->id;
        $params['seplsubmission_onlinetext_enabled'] = 1;
        $params['seplsubmission_file_enabled'] = 0;
        $params['seplfeedback_file_enabled'] = 0;
        $params['attemptreopenmethod'] = 'manual';
        $params['maxattempts'] = 5;
        $instance = $generator->create_instance($params);
        $cm = get_coursemodule_from_instance('sepl', $instance->id);
        $context = context_module::instance($cm->id);

        $sepl = new sepl($context, $cm, $course);

        $student1 = self::getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname'=>'student'));
        $this->getDataGenerator()->enrol_user($student1->id,
                                              $course->id,
                                              $studentrole->id);
        // Now try a submission.
        $this->setUser($student1);
        $draftidonlinetext = file_get_unused_draft_itemid();
        $submissionpluginparams = array();
        $onlinetexteditorparams = array('text'=>'Yeeha!',
                                        'format'=>1,
                                        'itemid'=>$draftidonlinetext);
        $submissionpluginparams['onlinetext_editor'] = $onlinetexteditorparams;
        $submissionpluginparams['files_filemanager'] = file_get_unused_draft_itemid();
        $result = mod_sepl_external::save_submission($instance->id, $submissionpluginparams);
        $result = external_api::clean_returnvalue(mod_sepl_external::save_submission_returns(), $result);

        $this->setUser($teacher);
        // Add a grade and reopen the attempt.
        // Now try a grade.
        $feedbackpluginparams = array();
        $feedbackpluginparams['files_filemanager'] = file_get_unused_draft_itemid();
        $feedbackeditorparams = array('text'=>'Yeeha!',
                                        'format'=>1);
        $feedbackpluginparams['seplfeedbackcomments_editor'] = $feedbackeditorparams;
        $result = mod_sepl_external::save_grade($instance->id,
                                                  $student1->id,
                                                  50.0,
                                                  -1,
                                                  true,
                                                  'released',
                                                  false,
                                                  $feedbackpluginparams);
        $this->assertNull($result);

        $this->setUser($student1);
        // Now copy the previous attempt.
        $result = mod_sepl_external::copy_previous_attempt($instance->id);
        $result = external_api::clean_returnvalue(mod_sepl_external::copy_previous_attempt_returns(), $result);
        // No warnings.
        $this->assertEquals(0, count($result));

        $this->setUser($teacher);
        $result = mod_sepl_external::get_submissions(array($instance->id));
        $result = external_api::clean_returnvalue(mod_sepl_external::get_submissions_returns(), $result);

        // Check we are now on the second attempt.
        $this->assertEquals($result['seplments'][0]['submissions'][0]['attemptnumber'], 1);
        // Check the plugins data is not empty.
        $this->assertNotEmpty($result['seplments'][0]['submissions'][0]['plugins']);

    }

    /**
     * Test set_user_flags
     */
    public function test_set_user_flags() {
        global $DB, $USER;

        $this->resetAfterTest(true);
        // Create a course and seplment.
        $coursedata['idnumber'] = 'idnumbercourse';
        $coursedata['fullname'] = 'Lightwork Course';
        $coursedata['summary'] = 'Lightwork Course description';
        $coursedata['summaryformat'] = FORMAT_MOODLE;
        $course = self::getDataGenerator()->create_course($coursedata);

        $sepldata['course'] = $course->id;
        $sepldata['name'] = 'lightwork seplment';

        $sepl = self::getDataGenerator()->create_module('sepl', $sepldata);

        // Create a manual enrolment record.
        $manualenroldata['enrol'] = 'manual';
        $manualenroldata['status'] = 0;
        $manualenroldata['courseid'] = $course->id;
        $enrolid = $DB->insert_record('enrol', $manualenroldata);

        // Create a teacher and give them capabilities.
        $context = context_course::instance($course->id);
        $roleid = $this->seplUserCapability('moodle/course:viewparticipants', $context->id, 3);
        $context = context_module::instance($sepl->cmid);
        $this->seplUserCapability('mod/sepl:grade', $context->id, $roleid);

        // Create the teacher's enrolment record.
        $userenrolmentdata['status'] = 0;
        $userenrolmentdata['enrolid'] = $enrolid;
        $userenrolmentdata['userid'] = $USER->id;
        $DB->insert_record('user_enrolments', $userenrolmentdata);

        // Create a student.
        $student = self::getDataGenerator()->create_user();

        // Create test user flags record.
        $userflags = array();
        $userflag['userid'] = $student->id;
        $userflag['workflowstate'] = 'inmarking';
        $userflag['allocatedmarker'] = $USER->id;
        $userflags = array($userflag);

        $createduserflags = mod_sepl_external::set_user_flags($sepl->id, $userflags);
        // We need to execute the return values cleaning process to simulate the web service server.
        $createduserflags = external_api::clean_returnvalue(mod_sepl_external::set_user_flags_returns(), $createduserflags);

        $this->assertEquals($student->id, $createduserflags[0]['userid']);
        $createduserflag = $DB->get_record('sepl_user_flags', array('id' => $createduserflags[0]['id']));

        // Confirm that all data was inserted correctly.
        $this->assertEquals($student->id,  $createduserflag->userid);
        $this->assertEquals($sepl->id, $createduserflag->seplment);
        $this->assertEquals(0, $createduserflag->locked);
        $this->assertEquals(2, $createduserflag->mailed);
        $this->assertEquals(0, $createduserflag->extensionduedate);
        $this->assertEquals('inmarking', $createduserflag->workflowstate);
        $this->assertEquals($USER->id, $createduserflag->allocatedmarker);

        // Create update data.
        $userflags = array();
        $userflag['userid'] = $createduserflag->userid;
        $userflag['workflowstate'] = 'readyforreview';
        $userflags = array($userflag);

        $updateduserflags = mod_sepl_external::set_user_flags($sepl->id, $userflags);
        // We need to execute the return values cleaning process to simulate the web service server.
        $updateduserflags = external_api::clean_returnvalue(mod_sepl_external::set_user_flags_returns(), $updateduserflags);

        $this->assertEquals($student->id, $updateduserflags[0]['userid']);
        $updateduserflag = $DB->get_record('sepl_user_flags', array('id' => $updateduserflags[0]['id']));

        // Confirm that all data was updated correctly.
        $this->assertEquals($student->id,  $updateduserflag->userid);
        $this->assertEquals($sepl->id, $updateduserflag->seplment);
        $this->assertEquals(0, $updateduserflag->locked);
        $this->assertEquals(2, $updateduserflag->mailed);
        $this->assertEquals(0, $updateduserflag->extensionduedate);
        $this->assertEquals('readyforreview', $updateduserflag->workflowstate);
        $this->assertEquals($USER->id, $updateduserflag->allocatedmarker);
    }

    /**
     * Test view_grading_table
     */
    public function test_view_grading_table() {
        global $DB;

        $this->resetAfterTest(true);

        $this->setAdminUser();
        // Setup test data.
        $course = $this->getDataGenerator()->create_course();
        $sepl = $this->getDataGenerator()->create_module('sepl', array('course' => $course->id));
        $context = context_module::instance($sepl->cmid);
        $cm = get_coursemodule_from_instance('sepl', $sepl->id);

        // Test invalid instance id.
        try {
            mod_sepl_external::view_grading_table(0);
            $this->fail('Exception expected due to invalid mod_sepl instance id.');
        } catch (moodle_exception $e) {
            $this->assertEquals('invalidrecord', $e->errorcode);
        }

        // Test not-enrolled user.
        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);
        try {
            mod_sepl_external::view_grading_table($sepl->id);
            $this->fail('Exception expected due to not enrolled user.');
        } catch (moodle_exception $e) {
            $this->assertEquals('requireloginerror', $e->errorcode);
        }

        // Test user with full capabilities.
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $teacherrole->id);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();

        $result = mod_sepl_external::view_grading_table($sepl->id);
        $result = external_api::clean_returnvalue(mod_sepl_external::view_grading_table_returns(), $result);

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = array_shift($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_sepl\event\grading_table_viewed', $event);
        $this->assertEquals($context, $event->get_context());
        $moodleurl = new \moodle_url('/mod/sepl/view.php', array('id' => $cm->id));
        $this->assertEquals($moodleurl, $event->get_url());
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());

        // Test user with no capabilities.
        // We need a explicit prohibit since this capability is only defined in authenticated user and guest roles.
        sepl_capability('mod/sepl:view', CAP_PROHIBIT, $teacherrole->id, $context->id);
        accesslib_clear_all_caches_for_unit_testing();

        try {
            mod_sepl_external::view_grading_table($sepl->id);
            $this->fail('Exception expected due to missing capability.');
        } catch (moodle_exception $e) {
            $this->assertEquals('nopermissions', $e->errorcode);
        }

    }

}
