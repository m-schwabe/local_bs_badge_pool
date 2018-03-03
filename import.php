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
 * Imports pool badges from a XML file.
 *
 * @package    local_bs_badge_pool
 * @copyright  2015 onwards Matthias Schwabe {@link http://matthiasschwa.be}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/lib.php');

require_login();

$context = context_system::instance();
require_capability('local/bs_badge_pool:badgepoolimport', $context);

$pageparams = array();
admin_externalpage_setup('local_bs_badge_pool_manage', '', $pageparams);
$siteurl = new moodle_url('/local/bs_badge_pool/import.php', $pageparams);
$PAGE->set_url($siteurl);
$PAGE->navbar->add(get_string('import', 'local_bs_badge_pool'), $siteurl);

$form = new local_bs_badge_pool_import_form(new moodle_url($siteurl));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('importhead', 'local_bs_badge_pool'));

if (!$data = $form->get_data()) {
    echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
    $form->display();
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
    die;
} else {

    // Raise time and memory, as importing can be quite intensive.
    core_php_time_limit::raise();
    raise_memory_limit(MEMORY_EXTRA);

    $realfilename = $form->get_new_filename('xmlfile');

    $importfile = "{$CFG->tempdir}/badgepool/{$realfilename}";
    make_temp_directory('badgepool');
    if (!$result = $form->save_file('xmlfile', $importfile, true)) {
        throw new moodle_exception('uploadproblem');
    }

    $xmlfile = simplexml_load_file($importfile);
    $badgesadded = 0;

    if (empty($xmlfile)) {
        echo get_string('importfailed', 'local_bs_badge_pool');
        echo $OUTPUT->action_link($PAGE->url, get_string('back'));
    } else {

        $badgesadded = 0;
        $categoriesadded = 0;
        echo $OUTPUT->box_start('generalbox');
        foreach ($xmlfile->category as $category) {

            if ($data->category == 0) {

                // Creating a new badge pool category.
                $newcategory = new stdClass();
                $newcategory->name = (string)$category['name'];
                $newcategory->description = (string)$category->catdescription;

                $categoryid = $DB->insert_record('local_bs_badge_pool_cat', $newcategory, true);
                $categoriesadded++;

            } else {
                $categoryid = $data->category;
            }

            foreach ($category->badge as $badge) {

                $newbadge = new stdClass();
                $newbadge->name = (string)$badge->name;
                $newbadge->description = (string)$badge->description;
                $newbadge->categoryid = $categoryid;
                $newbadge->issuername = (string)$badge->issuername;
                $newbadge->issuerurl = (string)$badge->issuerurl;
                $newbadge->issuercontact = (string)$badge->issuercontact;
                $newbadge->expiredate = (int)$badge->expiredate;
                $newbadge->expireperiod = (int)$badge->expireperiod;
                $newbadge->type = (int)$badge->type;
                $newbadge->message = (string)$badge->message;
                $newbadge->messagesubject = (string)$badge->messagesubject;
                $newbadge->attachment = (int)$badge->attachment;
                $newbadge->notification = (int)$badge->notification;
                $newbadge->status = 1;

                $badgepoolid = $DB->insert_record('local_bs_badge_pool_badges', $newbadge, true);

                $tempfile = tempnam($CFG->tempdir.'/badgepool/', 'img');
                if (!$fp = fopen($tempfile, 'w+b')) {
                    echo ''; die;
                }
                fwrite($fp, base64_decode($badge->image));
                fclose($fp);
                process_new_icon($context, 'local_bs_badge_pool', 'badgepool', $badgepoolid, $tempfile, true);
                @unlink($tempfile);

                $badgesadded++;
                $categoryname = $DB->get_field('local_bs_badge_pool_cat', 'name', array('id' => $categoryid), MUST_EXIST);
                $params = array('badge' => $newbadge->name,
                                'category' => $categoryname,
                                'id' => $badgepoolid);
                echo get_string('badgeadded', 'local_bs_badge_pool', $params).'<br />';
            }
        }
        echo $OUTPUT->box_end();
    }

    echo $OUTPUT->box_start('generalbox');
    if ($badgesadded > 0) {
        echo $OUTPUT->notification(get_string('badgesadded', 'local_bs_badge_pool', $badgesadded), 'notifysuccess');
    } else {
        echo $OUTPUT->notification(get_string('nobadgesadded', 'local_bs_badge_pool'), '');
    }
    echo $OUTPUT->box_end();
}

echo $OUTPUT->continue_button('manage.php');
echo $OUTPUT->footer();
