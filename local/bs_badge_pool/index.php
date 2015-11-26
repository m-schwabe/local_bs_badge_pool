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
 * Using the badge pool.
 *
 * @package local_bs_badge_pool
 * @author Matthias Schwabe <mail@matthiasschwabe.de>
 * @copyright 2015 Matthias Schwabe
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

require_once(dirname(__FILE__).'/lib.php');

$courseid = required_param('id', PARAM_INT);
$categoryid = optional_param('cat', 0, PARAM_INT);
$dir = optional_param('dir', 'ASC', PARAM_TEXT);
$page = optional_param('page', 0, PARAM_INT);
$usebadge = optional_param('use', 0, PARAM_INT);

$context = context_course::instance($courseid);
$course = $DB->get_record('course', array('id' => $courseid));

require_login($course);
require_capability('local/bs_badge_pool:usebadgepool', $context);

$pageparams = array('id' => $courseid, 'cat' => $categoryid);

$PAGE->set_url('/local/bs_badge_pool/index.php', $pageparams);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title($course->fullname);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add(get_string('pluginname', 'local_bs_badge_pool'));

if ($usebadge) {

    require_sesskey();
    require_once($CFG->libdir. '/gdlib.php');

    $poolbadge = $DB->get_record('local_bs_badge_pool_badges', array('id' => $usebadge), '*', MUST_EXIST);

    // Additional fields for creating badge.
    $poolbadge->timecreated = time();
    $poolbadge->timemodified = time();
    $poolbadge->usercreated = $USER->id;
    $poolbadge->usermodified = $USER->id;
    $poolbadge->courseid = $courseid;
    $poolbadge->status = 0;

    $newbadgeid = $DB->insert_record('badge', $poolbadge, true);
    $newbadge = new badge($newbadgeid);

    $fs = get_file_storage();
    if ($file = $fs->get_file(SYSCONTEXTID, 'local_bs_badge_pool', 'badgepool', $usebadge, '/', 'f3.png')) {
        if ($imagefile = $file->copy_content_to_temp()) {
            badges_process_badge_image($newbadge, $imagefile);
        }
    }

    // If a user can configure badge criteria, they will be redirected to the criteria page.
    if (has_capability('moodle/badges:configurecriteria', $PAGE->context)) {
        redirect(new moodle_url('/badges/criteria.php', array('id' => $newbadgeid)));
    }
    redirect(new moodle_url('/badges/overview.php', array('id' => $newbadgeid)));
}

echo $OUTPUT->header();

if (!$categories = $DB->get_records('local_bs_badge_pool_cat')) {
    echo $OUTPUT->box(get_string('nobadges', 'local_bs_badge_pool'), 'generalbox');
    echo $OUTPUT->footer();
    die;
}

$url = new moodle_url("$CFG->wwwroot/local/bs_badge_pool/index.php?id=$courseid");
$options = array();

foreach ($categories as $category) {
    // Do not show empty categories.
    if ($count = $DB->count_records('local_bs_badge_pool_badges', array('categoryid' => $category->id, 'status' => 1))) {
        $options[$category->id] = $category->name.' ('.$count.')';
    }
}

$div = '<label for="catform_jump">'.get_string('currentcategory', 'local_bs_badge_pool').'&nbsp;</label>'.
    $OUTPUT->single_select($url, 'cat', $options, $categoryid,
        array(0 => get_string('categoryselect', 'local_bs_badge_pool')), 'categoryselect');
echo html_writer::div($div, 'catform');

if ($categoryid) {
    $perpage = 12;
    $start = $page == 0 ? 0 : $page * $perpage;

    $badges = $DB->get_recordset('local_bs_badge_pool_badges', array('categoryid' => $categoryid, 'status' => 1),
        '', '*', $start, $perpage);
    $badgescount = $DB->count_records('local_bs_badge_pool_badges', array('categoryid' => $categoryid, 'status' => 1));

    // True = links for prev, next, first & last page.
    $paging = new paging_bar($badgescount, $page, $perpage, $PAGE->url, 'page', true, true, true, true);
    $htmlpagingbar = $OUTPUT->render($paging);

    $table = new html_table();
    $table->attributes['class'] = 'collection';

    $sortbyname = local_bs_badge_pool_helper_sortable_heading(get_string('name'), 'name', 'name', $dir, $OUTPUT);
    $table->head = array(
        $sortbyname,
        get_string('description'),
        null
    );

    $table->colclasses = array('name', 'description', 'usepoolbadge');

    foreach ($badges as $badge) {

        $linktext = local_bs_badge_pool_print_badge_image($badge, context_system::instance()). ' '.
            html_writer::start_tag('span').$badge->name.html_writer::end_tag('span');
        $name = html_writer::link(new moodle_url('/local/bs_badge_pool/viewbadge.php',
            array('id' => $badge->id, 'cid' => $courseid, 'cat' => $badge->categoryid)), $linktext);
        $description = html_writer::div($badge->description, 'description');
        $action = html_writer::link(new moodle_url('/local/bs_badge_pool/index.php',
            array('id' => $courseid, 'use' => $badge->id, 'sesskey' => sesskey())), get_string('usebadge', 'local_bs_badge_pool'));
        $row = array($name, $description, $action);
        $table->data[] = $row;
    }

    $htmltable = html_writer::table($table);
    echo $OUTPUT->box($htmlpagingbar.$htmltable.$htmlpagingbar, 'generalbox');
}

echo $OUTPUT->footer();
