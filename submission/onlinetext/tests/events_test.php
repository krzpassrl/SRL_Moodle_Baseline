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
 * Contains the event tests for the plugin.
 *
 * @package   seplsubmission_onlinetext
 * @copyright 2013 Frédéric Massart
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/sepl/tests/base_test.php');

class seplsubmission_onlinetext_events_testcase extends advanced_testcase {

    /** @var stdClass $user A user to submit an seplment. */
    protected $user;

    /** @var stdClass $course New course created to hold the seplment activity. */
    protected $course;

    /** @var stdClass $cm A context module object. */
    protected $cm;

    /** @var stdClass $context Context of the seplment activity. */
    protected $context;

    /** @var stdClass $sepl The seplment object. */
    protected $sepl;

    /** @var stdClass $submission Submission information. */
    protected $submission;

    /** @var stdClass $data General data for the seplment submission. */
    protected $data;

    /**
     * Setup all the various parts of an seplment activity including creating an onlinetext submission.
     */
    protected function setUp() {
        $this->user = $this->getDataGenerator()->create_user();
        $this->course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_sepl');
        $params['course'] = $this->course->id;
        $instance = $generator->create_instance($params);
        $this->cm = get_coursemodule_from_instance('sepl', $instance->id);
        $this->context = context_module::instance($this->cm->id);
        $this->sepl = new testable_sepl($this->context, $this->cm, $this->course);

        $this->setUser($this->user->id);
        $this->submission = $this->sepl->get_user_submission($this->user->id, true);
        $this->data = new stdClass();
        $this->data->onlinetext_editor = array(
            'itemid' => file_get_unused_draft_itemid(),
            'text' => 'Submission text',
            'format' => FORMAT_PLAIN
        );
    }

    /**
     * Test that the assessable_uploaded event is fired when an online text submission is saved.
     */
    public function test_assessable_uploaded() {
        $this->resetAfterTest();

        $plugin = $this->sepl->get_submission_plugin_by_type('onlinetext');
        $sink = $this->redirectEvents();
        $plugin->save($this->submission, $this->data);
        $events = $sink->get_events();

        $this->assertCount(2, $events);
        $event = reset($events);
        $this->assertInstanceOf('\seplsubmission_onlinetext\event\assessable_uploaded', $event);
        $this->assertEquals($this->context->id, $event->contextid);
        $this->assertEquals($this->submission->id, $event->objectid);
        $this->assertEquals(array(), $event->other['pathnamehashes']);
        $this->assertEquals(FORMAT_PLAIN, $event->other['format']);
        $this->assertEquals('Submission text', $event->other['content']);
        $expected = new stdClass();
        $expected->modulename = 'sepl';
        $expected->cmid = $this->cm->id;
        $expected->itemid = $this->submission->id;
        $expected->courseid = $this->course->id;
        $expected->userid = $this->user->id;
        $expected->content = 'Submission text';
        $this->assertEventLegacyData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Test that the submission_created event is fired when an onlinetext submission is saved.
     */
    public function test_submission_created() {
        $this->resetAfterTest();

        $plugin = $this->sepl->get_submission_plugin_by_type('onlinetext');
        $sink = $this->redirectEvents();
        $plugin->save($this->submission, $this->data);
        $events = $sink->get_events();

        $this->assertCount(2, $events);
        $event = $events[1];
        $this->assertInstanceOf('\seplsubmission_onlinetext\event\submission_created', $event);
        $this->assertEquals($this->context->id, $event->contextid);
        $this->assertEquals($this->course->id, $event->courseid);
        $this->assertEquals($this->submission->id, $event->other['submissionid']);
        $this->assertEquals($this->submission->attemptnumber, $event->other['submissionattempt']);
        $this->assertEquals($this->submission->status, $event->other['submissionstatus']);
        $this->assertEquals($this->submission->userid, $event->relateduserid);
    }

    /**
     * Test that the submission_updated event is fired when an onlinetext
     * submission is saved and an existing submission already exists.
     */
    public function test_submission_updated() {
        $this->resetAfterTest();

        $plugin = $this->sepl->get_submission_plugin_by_type('onlinetext');
        $sink = $this->redirectEvents();
        // Create a submission.
        $plugin->save($this->submission, $this->data);
        // Update a submission.
        $plugin->save($this->submission, $this->data);
        $events = $sink->get_events();

        $this->assertCount(4, $events);
        $event = $events[3];
        $this->assertInstanceOf('\seplsubmission_onlinetext\event\submission_updated', $event);
        $this->assertEquals($this->context->id, $event->contextid);
        $this->assertEquals($this->course->id, $event->courseid);
        $this->assertEquals($this->submission->id, $event->other['submissionid']);
        $this->assertEquals($this->submission->attemptnumber, $event->other['submissionattempt']);
        $this->assertEquals($this->submission->status, $event->other['submissionstatus']);
        $this->assertEquals($this->submission->userid, $event->relateduserid);
    }
}
