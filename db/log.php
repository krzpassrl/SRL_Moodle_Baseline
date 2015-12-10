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
 * Definition of log events
 *
 * @package   mod_sepl
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$logs = array(
    array('module'=>'sepl', 'action'=>'add', 'mtable'=>'sepl', 'field'=>'name'),
    array('module'=>'sepl', 'action'=>'delete mod', 'mtable'=>'sepl', 'field'=>'name'),
    array('module'=>'sepl', 'action'=>'download all submissions', 'mtable'=>'sepl', 'field'=>'name'),
    array('module'=>'sepl', 'action'=>'grade submission', 'mtable'=>'sepl', 'field'=>'name'),
    array('module'=>'sepl', 'action'=>'lock submission', 'mtable'=>'sepl', 'field'=>'name'),
    array('module'=>'sepl', 'action'=>'reveal identities', 'mtable'=>'sepl', 'field'=>'name'),
    array('module'=>'sepl', 'action'=>'revert submission to draft', 'mtable'=>'sepl', 'field'=>'name'),
    array('module'=>'sepl', 'action'=>'set marking workflow state', 'mtable'=>'sepl', 'field'=>'name'),
    array('module'=>'sepl', 'action'=>'submission statement accepted', 'mtable'=>'sepl', 'field'=>'name'),
    array('module'=>'sepl', 'action'=>'submit', 'mtable'=>'sepl', 'field'=>'name'),
    array('module'=>'sepl', 'action'=>'submit for grading', 'mtable'=>'sepl', 'field'=>'name'),
    array('module'=>'sepl', 'action'=>'unlock submission', 'mtable'=>'sepl', 'field'=>'name'),
    array('module'=>'sepl', 'action'=>'update', 'mtable'=>'sepl', 'field'=>'name'),
    array('module'=>'sepl', 'action'=>'upload', 'mtable'=>'sepl', 'field'=>'name'),
    array('module'=>'sepl', 'action'=>'view', 'mtable'=>'sepl', 'field'=>'name'),
    array('module'=>'sepl', 'action'=>'view all', 'mtable'=>'course', 'field'=>'fullname'),
    array('module'=>'sepl', 'action'=>'view confirm submit seplment form', 'mtable'=>'sepl', 'field'=>'name'),
    array('module'=>'sepl', 'action'=>'view grading form', 'mtable'=>'sepl', 'field'=>'name'),
    array('module'=>'sepl', 'action'=>'view submission', 'mtable'=>'sepl', 'field'=>'name'),
    array('module'=>'sepl', 'action'=>'view submission grading table', 'mtable'=>'sepl', 'field'=>'name'),
    array('module'=>'sepl', 'action'=>'view sphere', 'mtable'=>'sepl', 'field'=>'name'),
    array('module'=>'sepl', 'action'=>'view submit seplment form', 'mtable'=>'sepl', 'field'=>'name'),
    array('module'=>'sepl', 'action'=>'view feedback', 'mtable'=>'sepl', 'field'=>'name'),
    array('module'=>'sepl', 'action'=>'view batch set marking workflow state', 'mtable'=>'sepl', 'field'=>'name'),
);
