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
 * Displays a pool badge.
 *
 * @package local_bs_badge_pool
 * @author Matthias Schwabe <mail@matthiasschwabe.de>
 * @copyright 2015 Matthias Schwabe
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

require_once(dirname(__FILE__).'/lib.php');

$badgeid = required_param('id', PARAM_INT);
$courseid = optional_param('cid', 0, PARAM_INT);
$categoryid = optional_param('cat', 0, PARAM_INT);

$badge = $DB->get_record('local_badge_pool_badges', array('id' => $badgeid));
$pageparams = array('id' => $badgeid);

if ($courseid) {

    require_login($courseid);
    $context = context_course::instance($courseid);
    require_capability('local/bs_badge_pool:usebadgepool', $context);
    $PAGE->set_pagelayout('incourse');
    $PAGE->set_title($COURSE->fullname);
    $PAGE->set_heading($COURSE->fullname);
    $pageparams['cid'] = $courseid;

} else {

    require_login();
    $context = context_system::instance();
    require_capability('local/bs_badge_pool:managebadgepool', $context);
    admin_externalpage_setup('local_bs_badge_pool', '', $pageparams);
}

$siteurl = new moodle_url('/local/bs_badge_pool/viewbadge.php', $pageparams);
$PAGE->set_url($siteurl);
if ($courseid) {
    $returnurl = new moodle_url('/local/bs_badge_pool/index.php', array('id' => $courseid, 'cat' => $categoryid));
    $PAGE->navbar->add(get_string('pluginname', 'local_bs_badge_pool'), $returnurl);
} else {
    $returnurl = new moodle_url('/local/bs_badge_pool/manage.php');
}

$PAGE->navbar->add($badge->name);

echo $OUTPUT->header();
echo $OUTPUT->heading(local_bs_badge_pool_print_badge_image($badge, context_system::instance(), 'f1.png') . ' ' . $badge->name);

$OUTPUT = $PAGE->get_renderer('core', 'badges');
echo local_bs_badge_pool_view_badge($badge, $context, $OUTPUT);

echo html_writer::div($OUTPUT->action_link($returnurl, get_string('back')), 'backurl');

echo $OUTPUT->footer();
