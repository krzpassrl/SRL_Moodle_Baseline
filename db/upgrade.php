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
 * Upgrade code for install
 *
 * @package   mod_sepl
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * upgrade this seplment instance - this function could be skipped but it will be needed later
 * @param int $oldversion The old version of the sepl module
 * @return bool
 */
function xmldb_sepl_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2012051700) {

        // Define field to be added to sepl.
        $table = new xmldb_table('sepl');
        $field = new xmldb_field('sendlatenotifications', XMLDB_TYPE_INTEGER, '2', null,
                                 XMLDB_NOTNULL, null, '0', 'sendnotifications');

        // Conditionally launch add field.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Assign savepoint reached.
        upgrade_mod_savepoint(true, 2012051700, 'sepl');
    }

    // Moodle v2.3.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2012071800) {

        // Define field requiresubmissionstatement to be added to sepl.
        $table = new xmldb_table('sepl');
        $field = new xmldb_field('requiresubmissionstatement', XMLDB_TYPE_INTEGER, '2', null,
                                 XMLDB_NOTNULL, null, '0', 'timemodified');

        // Conditionally launch add field requiresubmissionstatement.

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Assign savepoint reached.
        upgrade_mod_savepoint(true, 2012071800, 'sepl');
    }

    if ($oldversion < 2012081600) {

        // Define field to be added to sepl.
        $table = new xmldb_table('sepl');
        $field = new xmldb_field('completionsubmit', XMLDB_TYPE_INTEGER, '2', null,
                                 XMLDB_NOTNULL, null, '0', 'timemodified');

        // Conditionally launch add field.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Assign savepoint reached.
        upgrade_mod_savepoint(true, 2012081600, 'sepl');
    }

    // Individual extension dates support.
    if ($oldversion < 2012082100) {

        // Define field cutoffdate to be added to sepl.
        $table = new xmldb_table('sepl');
        $field = new xmldb_field('cutoffdate', XMLDB_TYPE_INTEGER, '10', null,
                                 XMLDB_NOTNULL, null, '0', 'completionsubmit');

        // Conditionally launch add field cutoffdate.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // If prevent late is on - set cutoffdate to due date.

        // Now remove the preventlatesubmissions column.
        $field = new xmldb_field('preventlatesubmissions', XMLDB_TYPE_INTEGER, '2', null,
                                 XMLDB_NOTNULL, null, '0', 'nosubmissions');
        if ($dbman->field_exists($table, $field)) {
            // Set the cutoffdate to the duedate if preventlatesubmissions was enabled.
            $sql = 'UPDATE {sepl} SET cutoffdate = duedate WHERE preventlatesubmissions = 1';
            $DB->execute($sql);

            $dbman->drop_field($table, $field);
        }

        // Define field extensionduedate to be added to sepl_grades.
        $table = new xmldb_table('sepl_grades');
        $field = new xmldb_field('extensionduedate', XMLDB_TYPE_INTEGER, '10', null,
                                 XMLDB_NOTNULL, null, '0', 'mailed');

        // Conditionally launch add field extensionduedate.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Assign savepoint reached.
        upgrade_mod_savepoint(true, 2012082100, 'sepl');
    }

    // Team seplment support.
    if ($oldversion < 2012082300) {

        // Define field to be added to sepl.
        $table = new xmldb_table('sepl');
        $field = new xmldb_field('teamsubmission', XMLDB_TYPE_INTEGER, '2', null,
                                 XMLDB_NOTNULL, null, '0', 'cutoffdate');

        // Conditionally launch add field.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('requireallteammemberssubmit', XMLDB_TYPE_INTEGER, '2', null,
                                 XMLDB_NOTNULL, null, '0', 'teamsubmission');
        // Conditionally launch add field.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('teamsubmissiongroupingid', XMLDB_TYPE_INTEGER, '10', null,
                                 XMLDB_NOTNULL, null, '0', 'requireallteammemberssubmit');
        // Conditionally launch add field.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $index = new xmldb_index('teamsubmissiongroupingid',
                                 XMLDB_INDEX_NOTUNIQUE,
                                 array('teamsubmissiongroupingid'));
        // Conditionally launch add index.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        $table = new xmldb_table('sepl_submission');
        $field = new xmldb_field('groupid', XMLDB_TYPE_INTEGER, '10', null,
                                 XMLDB_NOTNULL, null, '0', 'status');
        // Conditionally launch add field.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2012082300, 'sepl');
    }
    if ($oldversion < 2012082400) {

        // Define table sepl_user_mapping to be created.
        $table = new xmldb_table('sepl_user_mapping');

        // Adding fields to table sepl_user_mapping.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('seplment', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table sepl_user_mapping.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('seplment', XMLDB_KEY_FOREIGN, array('seplment'), 'sepl', array('id'));
        $table->add_key('user', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));

        // Conditionally launch create table for sepl_user_mapping.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

//define table user_sphere (moodle user - sphere token mapping)
        // Define table sepl_user_mapping to be created.
        $table = new xmldb_table('sepl_user_sphere');

        // Adding fields to table sepl_user_mapping.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('token', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table sepl_user_mapping.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('seplment', XMLDB_KEY_FOREIGN, array('seplment'), 'sepl', array('id'));
        $table->add_key('user', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));

        // Conditionally launch create table for sepl_user_mapping.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }







// Define table sepl_sphere to  be created
        $table = new xmldb_table('sepl_sphere');

        // Adding fields to table sepl_sphere.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('seplment', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table sepl_sphere.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('seplment', XMLDB_KEY_FOREIGN, array('seplment'), 'sepl', array('id'));
        $table->add_key('user', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));

        // Conditionally launch create table for sepl_sphere.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define field blindmarking to be added to sepl.
        $table = new xmldb_table('sepl');
        $field = new xmldb_field('blindmarking', XMLDB_TYPE_INTEGER, '2', null,
                                 XMLDB_NOTNULL, null, '0', 'teamsubmissiongroupingid');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field revealidentities to be added to sepl.
        $table = new xmldb_table('sepl');
        $field = new xmldb_field('revealidentities', XMLDB_TYPE_INTEGER, '2', null,
                                 XMLDB_NOTNULL, null, '0', 'blindmarking');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Assignment savepoint reached.
        upgrade_mod_savepoint(true, 2012082400, 'sepl');
    }

    // Moodle v2.4.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2013030600) {
        upgrade_set_timeout(60*20);

        // Some seplments (upgraded from 2.2 seplment) have duplicate entries in the sepl_submission
        // and sepl_grades tables for a single user. This needs to be cleaned up before we can add the unique indexes
        // below.

        // Only do this cleanup if the attempt number field has not been added to the table yet.
        $table = new xmldb_table('sepl_submission');
        $field = new xmldb_field('attemptnumber', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'groupid');
        if (!$dbman->field_exists($table, $field)) {
            // OK safe to cleanup duplicates here.

            $sql = 'SELECT seplment, userid, groupid from {sepl_submission} ' .
                   'GROUP BY seplment, userid, groupid HAVING (count(id) > 1)';
            $badrecords = $DB->get_recordset_sql($sql);

            foreach ($badrecords as $badrecord) {
                $params = array('userid'=>$badrecord->userid,
                                'groupid'=>$badrecord->groupid,
                                'seplment'=>$badrecord->seplment);
                $duplicates = $DB->get_records('sepl_submission', $params, 'timemodified DESC', 'id, timemodified');
                if ($duplicates) {
                    // Take the first (last updated) entry out of the list so it doesn't get deleted.
                    $valid = array_shift($duplicates);
                    $deleteids = array();
                    foreach ($duplicates as $duplicate) {
                        $deleteids[] = $duplicate->id;
                    }

                    list($sqlids, $sqlidparams) = $DB->get_in_or_equal($deleteids);
                    $DB->delete_records_select('sepl_submission', 'id ' . $sqlids, $sqlidparams);
                }
            }

            $badrecords->close();
        }

        // Same cleanup required for sepl_grades
        // Only do this cleanup if the attempt number field has not been added to the table yet.
        $table = new xmldb_table('sepl_grades');
        $field = new xmldb_field('attemptnumber', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'grade');
        if (!$dbman->field_exists($table, $field)) {
            // OK safe to cleanup duplicates here.

            $sql = 'SELECT seplment, userid from {sepl_grades} GROUP BY seplment, userid HAVING (count(id) > 1)';
            $badrecords = $DB->get_recordset_sql($sql);

            foreach ($badrecords as $badrecord) {
                $params = array('userid'=>$badrecord->userid,
                                'seplment'=>$badrecord->seplment);
                $duplicates = $DB->get_records('sepl_grades', $params, 'timemodified DESC', 'id, timemodified');
                if ($duplicates) {
                    // Take the first (last updated) entry out of the list so it doesn't get deleted.
                    $valid = array_shift($duplicates);
                    $deleteids = array();
                    foreach ($duplicates as $duplicate) {
                        $deleteids[] = $duplicate->id;
                    }

                    list($sqlids, $sqlidparams) = $DB->get_in_or_equal($deleteids);
                    $DB->delete_records_select('sepl_grades', 'id ' . $sqlids, $sqlidparams);
                }
            }

            $badrecords->close();
        }

        // Define table sepl_user_flags to be created.
        $table = new xmldb_table('sepl_user_flags');

        // Adding fields to table sepl_user_flags.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('seplment', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('locked', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('mailed', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('extensionduedate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table sepl_user_flags.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        $table->add_key('seplment', XMLDB_KEY_FOREIGN, array('seplment'), 'sepl', array('id'));

        // Adding indexes to table sepl_user_flags.
        $table->add_index('mailed', XMLDB_INDEX_NOTUNIQUE, array('mailed'));

        // Conditionally launch create table for sepl_user_flags.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);

            // Copy the flags from the old table to the new one.
            $sql = 'INSERT INTO {sepl_user_flags}
                        (seplment, userid, locked, mailed, extensionduedate)
                    SELECT seplment, userid, locked, mailed, extensionduedate
                    FROM {sepl_grades}';
            $DB->execute($sql);
        }

        // And delete the old columns.
        // Define index mailed (not unique) to be dropped form sepl_grades.
        $table = new xmldb_table('sepl_grades');
        $index = new xmldb_index('mailed', XMLDB_INDEX_NOTUNIQUE, array('mailed'));

        // Conditionally launch drop index mailed.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define field locked to be dropped from sepl_grades.
        $table = new xmldb_table('sepl_grades');
        $field = new xmldb_field('locked');

        // Conditionally launch drop field locked.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field mailed to be dropped from sepl_grades.
        $table = new xmldb_table('sepl_grades');
        $field = new xmldb_field('mailed');

        // Conditionally launch drop field mailed.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field extensionduedate to be dropped from sepl_grades.
        $table = new xmldb_table('sepl_grades');
        $field = new xmldb_field('extensionduedate');

        // Conditionally launch drop field extensionduedate.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field attemptreopenmethod to be added to sepl.
        $table = new xmldb_table('sepl');
        $field = new xmldb_field('attemptreopenmethod', XMLDB_TYPE_CHAR, '10', null,
                                 XMLDB_NOTNULL, null, 'none', 'revealidentities');

        // Conditionally launch add field attemptreopenmethod.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field maxattempts to be added to sepl.
        $table = new xmldb_table('sepl');
        $field = new xmldb_field('maxattempts', XMLDB_TYPE_INTEGER, '6', null, XMLDB_NOTNULL, null, '-1', 'attemptreopenmethod');

        // Conditionally launch add field maxattempts.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field attemptnumber to be added to sepl_submission.
        $table = new xmldb_table('sepl_submission');
        $field = new xmldb_field('attemptnumber', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'groupid');

        // Conditionally launch add field attemptnumber.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define index attemptnumber (not unique) to be added to sepl_submission.
        $table = new xmldb_table('sepl_submission');
        $index = new xmldb_index('attemptnumber', XMLDB_INDEX_NOTUNIQUE, array('attemptnumber'));
        // Conditionally launch add index attemptnumber.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define field attemptnumber to be added to sepl_grades.
        $table = new xmldb_table('sepl_grades');
        $field = new xmldb_field('attemptnumber', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'grade');

        // Conditionally launch add field attemptnumber.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define index attemptnumber (not unique) to be added to sepl_grades.
        $table = new xmldb_table('sepl_grades');
        $index = new xmldb_index('attemptnumber', XMLDB_INDEX_NOTUNIQUE, array('attemptnumber'));

        // Conditionally launch add index attemptnumber.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index uniqueattemptsubmission (unique) to be added to sepl_submission.
        $table = new xmldb_table('sepl_submission');
        $index = new xmldb_index('uniqueattemptsubmission',
                                 XMLDB_INDEX_UNIQUE,
                                 array('seplment', 'userid', 'groupid', 'attemptnumber'));

        // Conditionally launch add index uniqueattempt.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index uniqueattemptgrade (unique) to be added to sepl_grades.
        $table = new xmldb_table('sepl_grades');
        $index = new xmldb_index('uniqueattemptgrade', XMLDB_INDEX_UNIQUE, array('seplment', 'userid', 'attemptnumber'));

        // Conditionally launch add index uniqueattempt.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Module sepl savepoint reached.
        upgrade_mod_savepoint(true, 2013030600, 'sepl');
    }

    // Moodle v2.5.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2013061101) {
        // Define field markingworkflow to be added to sepl.
        $table = new xmldb_table('sepl');
        $field = new xmldb_field('markingworkflow', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'maxattempts');

        // Conditionally launch add field markingworkflow.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field markingallocation to be added to sepl.
        $field = new xmldb_field('markingallocation', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'markingworkflow');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field workflowstate to be added to sepl_grades.
        $table = new xmldb_table('sepl_user_flags');
        $field = new xmldb_field('workflowstate', XMLDB_TYPE_CHAR, '20', null, null, null, null, 'extensionduedate');

        // Conditionally launch add field workflowstate.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field allocatedmarker to be added to sepl_grades.
        $field = new xmldb_field('allocatedmarker', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'workflowstate');
        // Conditionally launch add field workflowstate.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2013061101, 'sepl');
    }

    // Moodle v2.6.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2014010801) {

        // Define field sendstudentnotifications to be added to sepl.
        $table = new xmldb_table('sepl');
        $field = new xmldb_field('sendstudentnotifications',
                                 XMLDB_TYPE_INTEGER,
                                 '2',
                                 null,
                                 XMLDB_NOTNULL,
                                 null,
                                 '1',
                                 'markingallocation');

        // Conditionally launch add field sendstudentnotifications.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Assign savepoint reached.
        upgrade_mod_savepoint(true, 2014010801, 'sepl');
    }

    // Moodle v2.7.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2014051201) {

        // Cleanup bad database records where seplid is missing.

        $DB->delete_records('sepl_user_mapping', array('seplment'=>0));
        // Assign savepoint reached.
        upgrade_mod_savepoint(true, 2014051201, 'sepl');
    }
    if ($oldversion < 2014072400) {

        // Add "latest" column to submissions table to mark the latest attempt.
        $table = new xmldb_table('sepl_submission');
        $field = new xmldb_field('latest', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'attemptnumber');

        // Conditionally launch add field latest.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Assign savepoint reached.
        upgrade_mod_savepoint(true, 2014072400, 'sepl');
    }
    if ($oldversion < 2014072401) {

         // Define index latestattempt (not unique) to be added to sepl_submission.
        $table = new xmldb_table('sepl_submission');
        $index = new xmldb_index('latestattempt', XMLDB_INDEX_NOTUNIQUE, array('seplment', 'userid', 'groupid', 'latest'));

        // Conditionally launch add index latestattempt.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Assign savepoint reached.
        upgrade_mod_savepoint(true, 2014072401, 'sepl');
    }
    if ($oldversion < 2014072405) {

        // Prevent running this multiple times.

        $countsql = 'SELECT COUNT(id) FROM {sepl_submission} WHERE latest = ?';

        $count = $DB->count_records_sql($countsql, array(1));
        if ($count == 0) {

            // Mark the latest attempt for every submission in mod_sepl.
            $maxattemptsql = 'SELECT seplment, userid, groupid, max(attemptnumber) AS maxattempt
                                FROM {sepl_submission}
                            GROUP BY seplment, groupid, userid';

            $maxattemptidssql = 'SELECT souter.id
                                   FROM {sepl_submission} souter
                                   JOIN (' . $maxattemptsql . ') sinner
                                     ON souter.seplment = sinner.seplment
                                    AND souter.userid = sinner.userid
                                    AND souter.groupid = sinner.groupid
                                    AND souter.attemptnumber = sinner.maxattempt';

            // We need to avoid using "WHERE ... IN(SELECT ...)" clause with MySQL for performance reason.
            // TODO MDL-29589 Remove this dbfamily exception when implemented.
            if ($DB->get_dbfamily() === 'mysql') {
                $params = array('latest' => 1);
                $sql = 'UPDATE {sepl_submission}
                    INNER JOIN (' . $maxattemptidssql . ') souterouter ON souterouter.id = {sepl_submission}.id
                           SET latest = :latest';
                $DB->execute($sql, $params);
            } else {
                $select = 'id IN(' . $maxattemptidssql . ')';
                $DB->set_field_select('sepl_submission', 'latest', 1, $select);
            }

            // Look for grade records with no submission record.
            // This is when a teacher has marked a student before they submitted anything.
            $records = $DB->get_records_sql('SELECT g.id, g.seplment, g.userid
                                               FROM {sepl_grades} g
                                          LEFT JOIN {sepl_submission} s
                                                 ON s.seplment = g.seplment
                                                AND s.userid = g.userid
                                              WHERE s.id IS NULL');
            $submissions = array();
            foreach ($records as $record) {
                $submission = new stdClass();
                $submission->seplment = $record->seplment;
                $submission->userid = $record->userid;
                $submission->status = 'new';
                $submission->groupid = 0;
                $submission->latest = 1;
                $submission->timecreated = time();
                $submission->timemodified = time();
                array_push($submissions, $submission);
            }

            $DB->insert_records('sepl_submission', $submissions);
        }

        // Assign savepoint reached.
        upgrade_mod_savepoint(true, 2014072405, 'sepl');
    }

    // Moodle v2.8.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2014122600) {
        // Delete any entries from the sepl_user_flags and sepl_user_mapping that are no longer required.
        if ($DB->get_dbfamily() === 'mysql') {
            $sql1 = "DELETE {sepl_user_flags}
                       FROM {sepl_user_flags}
                  LEFT JOIN {sepl}
                         ON {sepl_user_flags}.seplment = {sepl}.id
                      WHERE {sepl}.id IS NULL";

            $sql2 = "DELETE {sepl_user_mapping}
                       FROM {sepl_user_mapping}
                  LEFT JOIN {sepl}
                         ON {sepl_user_mapping}.seplment = {sepl}.id
                      WHERE {sepl}.id IS NULL";
        } else {
            $sql1 = "DELETE FROM {sepl_user_flags}
                WHERE NOT EXISTS (
                          SELECT 'x' FROM {sepl}
                           WHERE {sepl_user_flags}.seplment = {sepl}.id)";

            $sql2 = "DELETE FROM {sepl_user_mapping}
                WHERE NOT EXISTS (
                          SELECT 'x' FROM {sepl}
                           WHERE {sepl_user_mapping}.seplment = {sepl}.id)";
        }

        $DB->execute($sql1);
        $DB->execute($sql2);

        upgrade_mod_savepoint(true, 2014122600, 'sepl');
    }

    if ($oldversion < 2015022300) {

        // Define field preventsubmissionnotingroup to be added to sepl.
        $table = new xmldb_table('sepl');
        $field = new xmldb_field('preventsubmissionnotingroup',
            XMLDB_TYPE_INTEGER,
            '2',
            null,
            XMLDB_NOTNULL,
            null,
            '0',
            'sendstudentnotifications');

        // Conditionally launch add field preventsubmissionnotingroup.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Assign savepoint reached.
        upgrade_mod_savepoint(true, 2015022300, 'sepl');
    }

    // Moodle v2.9.0 release upgrade line.
    // Put any upgrade step following this.

    return true;
}
