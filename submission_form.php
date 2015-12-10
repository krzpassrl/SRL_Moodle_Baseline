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
 * This file contains the submission form used by the sepl module.
 *
 * @package   mod_sepl
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once('./sphereengine/autoload.php');

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/mod/sepl/locallib.php');

/**
 * Assign submission form
 *
 * @package   mod_sepl
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_sepl_submission_form extends moodleform {

    /**
     * Define this form - called by the parent constructor
     */
    public function definition() {
        $mform = $this->_form;

        list($sepl, $data) = $this->_customdata;

        $sepl->add_submission_form_elements($mform, $data);/*

$ch = curl_init();
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://api.compilers.sphere-engine.com/api/v3/languages?access_token=02994435062fb95b5f3e6938e5aa433e");
curl_setopt($ch, CURLOPT_POST, 1);
#curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$server_output = curl_exec ($ch);*/
       #var_dump($result);
        $client = (new SphereEngine\Api("02994435062fb95b5f3e6938e5aa433e", "v3", "languages"))->getProblemsClient();
        $se_compilers = new SphereEngine\Api("02994435062fb95b5f3e6938e5aa433e", "v3", "languages");
        $compilersClient = $se_compilers->getCompilersClient();
        $server_output = $compilersClient->compilers();
       # $result = json_decode($server_output, true);
      #  $jsonString = json_decode($server_output, true);
#curl_close ($ch);
#var_dump($jsonString);
     $mform->addElement('select', 'compiler', 'Compiler: ',$server_output);//, $state);        
     $this->add_action_buttons(false, 'Refresh');
    #$mform->addElement('select', 'compiler', 'Compiler: ',$server_output);//, $state);
        

        $this->add_action_buttons(true, get_string('savechanges', 'sepl'));
        if ($data) {
       # 	$compiler = $mform->g
#var_dump($data);
$ii =0;
foreach($data as $tes){
$ii =0;

foreach($tes as $tes2)
{
	var_dump($tes2);
	if($ii == 0)
		$textToSend = $tes2;//text from online editor - ugly way but it works...
	$ii++;
}

}

$myToken = "02994435062fb95b5f3e6938e5aa433e";
$client = (new SphereEngine\Api($myToken, "v3", "endpoint"))->getCompilersClient();
$res = $client->submissions->create("UT1952", $textToSend, 11);

$selectedItem  =& $mform->getElement('compiler')->getSelected();
var_dump($selectedItem);
#var_dump($mform->get_data('compiler'));
/*
 *  example of saving record in moodle db
 * $newrecord = new stdClass();
$newrecord->name = $data->dname;
$newrecord->id = $DB->insert_record('tbl_name', $newrecord);
 * 
 */
$mform->addElement('text', 'sphereReturn', $res, $attributes);

var_dump($res);
            $this->set_data($data);
        }
    }
}

