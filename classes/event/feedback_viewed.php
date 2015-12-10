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
 * The mod_sepl feedback viewed event.
 *
 * @package    mod_sepl
 * @copyright  2014 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_sepl\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_sepl feedback viewed event class.
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      - int seplid: the id of the seplment.
 * }
 *
 * @package    mod_sepl
 * @since      Moodle 2.7
 * @copyright  2014 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class feedback_viewed extends base {
    /**
     * Create instance of event.
     *
     * @param \sepl $sepl
     * @param \stdClass $grade
     * @return feedback_viewed
     */
    public static function create_from_grade(\sepl $sepl, \stdClass $grade) {
        $data = array(
            'objectid' => $grade->id,
            'relateduserid' => $grade->userid,
            'context' => $sepl->get_context(),
            'other' => array(
                'seplid' => $sepl->get_instance()->id,
            ),
        );
        /** @var feedback_viewed $event */
        $event = self::create($data);
        $event->set_sepl($sepl);
        $event->add_record_snapshot('sepl_grades', $grade);
        return $event;
    }

    /**
     * Init method.
     */
    protected function init() {
        $this->data['objecttable'] = 'sepl_grades';
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventfeedbackviewed', 'mod_sepl');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' viewed the feedback for the user with id '$this->relateduserid' " .
            "for the seplment with course module id '$this->contextinstanceid'.";
    }

    /**
     * Return legacy data for add_to_log().
     *
     * @return array
     */
    protected function get_legacy_logdata() {
        $logmessage = get_string('viewfeedbackforuser', 'sepl', $this->relateduserid);
        $this->set_legacy_logdata('view feedback', $logmessage);
        return parent::get_legacy_logdata();
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->relateduserid)) {
            throw new \coding_exception('The \'relateduserid\' must be set.');
        }

        if (!isset($this->other['seplid'])) {
            throw new \coding_exception('The \'seplid\' value must be set in other.');
        }
    }

    public static function get_objectid_mapping() {
        return array('db' => 'sepl_grades', 'restore' => 'grade');
    }

    public static function get_other_mapping() {
        $othermapped = array();
        $othermapped['seplid'] = array('db' => 'sepl', 'restore' => 'sepl');

        return $othermapped;
    }
}
