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
 * Adds a pool badge to the badge pool or edits an existing pool badge.
 *
 * @package local_bs_badge_pool
 * @author Matthias Schwabe <mail@matthiasschwabe.de>
 * @copyright 2015 Matthias Schwabe
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

require_once(dirname(__FILE__).'/lib.php');

$badgeid = required_param('id', PARAM_INT);
$context = context_system::instance();

require_login();
require_capability('local/bs_badge_pool:managebadgepool', $context);

$pageparams = array();
admin_externalpage_setup('local_bs_badge_pool', '', $pageparams);

$badge = $DB->get_record('local_badge_pool_badges', array('id' => $badgeid));
$badge->message = clean_text($badge->message, FORMAT_HTML);
$editoroptions = array(
    'subdirs' => 0,
    'maxbytes' => 0,
    'maxfiles' => 0,
    'changeformat' => 0,
    'context' => $context,
    'noclean' => false,
    'trusttext' => false
);

$badge = file_prepare_standard_editor($badge, 'message', $editoroptions, $context);

$form = new local_bs_badge_pool_badge_form(new moodle_url('/local/bs_badge_pool/edit.php', array('id' => $badgeid)),
    array('badge' => $badge, 'action' => 'edit', 'editoroptions' => $editoroptions));

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/bs_badge_pool/manage.php', array()));
} else if ($data = $form->get_data()) {

    // Editing pool badge.
    $poolbadge = new stdClass();
    $poolbadge->id = $badgeid;
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

    // Need to unset message_editor options to avoid errors on form edit.
    unset($badge->message_editor);

    $DB->update_record('local_badge_pool_badges', $poolbadge);

    if (!empty($CFG->gdversion)) {
        $badgeimage = $form->save_temp_file('image');
        process_new_icon(context_system::instance(), 'local_bs_badge_pool', 'badgepool', $badgeid, $badgeimage, true);
        //@unlink($badgeimage);

        // Clean up file draft area after badge image has been saved.
        //$context = context_user::instance($USER->id, MUST_EXIST);
        //$fs = get_file_storage();
        //$fs->delete_area_files($context->id, 'user', 'draft');
    }

    redirect(new moodle_url('/local/bs_badge_pool/manage.php'));
}

echo $OUTPUT->header();

if ($DB->get_records('local_badge_pool_categories')) {
    $form->display();
} else {
    echo $OUTPUT->box(get_string('nocategories', 'local_bs_badge_pool'), 'generalbox');
    echo $OUTPUT->box(html_writer::link($CFG->wwwroot . '/local/bs_badge_pool/manage.php', get_string('back')), 'generalbox');
}

echo $OUTPUT->footer();
