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
 * Adds a pool badge to the badge pool.
 *
 * @package    local_bs_badge_pool
 * @copyright  2015 onwards Matthias Schwabe {@link http://matthiasschwa.be}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/lib.php');

require_login();
require_capability('local/bs_badge_pool:managebadgepool', context_system::instance());

$pageparams = array();
admin_externalpage_setup('local_bs_badge_pool', '', $pageparams);

$form = new local_bs_badge_pool_badge_form(new moodle_url('/local/bs_badge_pool/addbadge.php'));

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/bs_badge_pool/manage.php', array()));
} else if ($data = $form->get_data()) {

    // Creating a new pool badge.
    $poolbadge = new stdClass();
    $poolbadge->name = $data->name;
    $poolbadge->description = $data->description;
    $poolbadge->categoryid = $data->category;
    $poolbadge->issuername = $data->issuername;
    $poolbadge->issuerurl = $data->issuerurl;
    $poolbadge->issuercontact = $data->issuercontact;
    $poolbadge->expiredate = ($data->expiry == 1) ? $data->expiredate : null;
    $poolbadge->expireperiod = ($data->expiry == 2) ? $data->expireperiod : null;
    $poolbadge->type = 2;
    $poolbadge->message = clean_text($data->message_editor['text'], FORMAT_HTML);
    $poolbadge->messagesubject = $data->messagesubject;
    $poolbadge->attachment = $data->attachment;
    $poolbadge->notification = $data->notification;
    $poolbadge->status = 1;

    $badgepoolid = $DB->insert_record('local_bs_badge_pool_badges', $poolbadge, true);

    if (!empty($CFG->gdversion)) {
        $badgeimage = $form->save_temp_file('image');
        process_new_icon(context_system::instance(), 'local_bs_badge_pool', 'badgepool', $badgepoolid, $badgeimage, true);
        @unlink($badgeimage);

        // Clean up file draft area after pool badge image has been saved.
        $context = context_user::instance($USER->id, MUST_EXIST);
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'user', 'draft');
    }

    redirect(new moodle_url('/local/bs_badge_pool/manage.php', array('id' => $badgepoolid)));
}

echo $OUTPUT->header();

if ($DB->get_records('local_bs_badge_pool_cat')) {
    $form->display();
} else {
    echo $OUTPUT->box(get_string('nocategories', 'local_bs_badge_pool'), 'generalbox');
    echo $OUTPUT->box(html_writer::link($CFG->wwwroot . '/local/bs_badge_pool/manage.php', get_string('back')), 'generalbox');
}

echo $OUTPUT->footer();
