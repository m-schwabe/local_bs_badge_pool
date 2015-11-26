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
 * Global management for pool badges.
 *
 * @package local_bs_badge_pool
 * @author Matthias Schwabe <mail@matthiasschwabe.de>
 * @copyright 2015 Matthias Schwabe
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

require_once(dirname(__FILE__).'/lib.php');

$dir = optional_param('dir', 'ASC', PARAM_TEXT);
$status = optional_param('status', false, PARAM_TEXT);
$sort = optional_param('sort', 'name', PARAM_TEXT);
$delete = optional_param('delete', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$badgeid = optional_param('id', 0, PARAM_INT);

require_login();
$context = context_system::instance();
require_capability('local/bs_badge_pool:managebadgepool', $context);

$pageparams = array();
admin_externalpage_setup('local_bs_badge_pool', '', $pageparams);
$siteurl = new moodle_url('/local/bs_badge_pool/manage.php', $pageparams);
$PAGE->set_url($siteurl);

if ($confirm and $delete) {
    require_sesskey();
    local_bs_badge_pool_delete_badge($delete);
    redirect($PAGE->url);
}

$output = $PAGE->get_renderer('core', 'badges');
echo $output->header();

if ($delete) {

    $PAGE->url->param('delete', $delete);
    $badgename = $DB->get_field('local_bs_badge_pool_badges', 'name', array('id' => $delete));
    echo $output->heading(get_string('deletebadge', 'local_bs_badge_pool', $badgename));
    $deletebutton = $output->single_button(
    new moodle_url($PAGE->url, array('delete' => $delete, 'confirm' => 1)),
        get_string('confirmdeletebadge', 'local_bs_badge_pool'));
    echo $output->box($deletebutton, 'generalbox');
    echo $output->action_link($PAGE->url, get_string('cancel'));
    echo $output->footer();
    die;
}

if ($status) {

    $PAGE->url->param('status', $status);
    $PAGE->url->param('id', $badgeid);
    require_sesskey();
    local_bs_badge_pool_set_badge_status($badgeid, $status);
}

$perpage = 12;
$start = $page == 0 ? 0 : $page * $perpage;

// Get pool badges in right sort order.
$sql = "SELECT *
          FROM {local_bs_badge_pool_badges}
      ORDER BY ".$sort." ".$dir;

$badges = $DB->get_recordset_sql($sql, null, $start, $perpage);
$badgescount = $DB->count_records('local_bs_badge_pool_badges');

// True = links for prev, next, first & last page.
$paging = new paging_bar($badgescount, $page, $perpage, $PAGE->url, 'page', true, true, true, true);
$htmlpagingbar = $output->render($paging);

$table = new html_table();
$table->attributes['class'] = 'collection';

$sortbyname = local_bs_badge_pool_helper_sortable_heading(get_string('name'), 'name', $sort, $dir, $output);
$sortbycategory = local_bs_badge_pool_helper_sortable_heading(get_string('category'), 'categoryid', $sort, $dir, $output);
$table->head = array(
    $sortbyname,
    get_string('description'),
    $sortbycategory,
    get_string('actions')
);

$table->colclasses = array('name', 'description', 'category', 'actions');

foreach ($badges as $badge) {

    $badgecategory = $DB->get_field('local_bs_badge_pool_cat', 'name', array('id' => $badge->categoryid));
    $style = !$badge->status ? array('class' => 'dimmed') : array();
    $linktext = local_bs_badge_pool_print_badge_image($badge, $context). ' '.
        html_writer::start_tag('span').$badge->name.html_writer::end_tag('span');
    $name = html_writer::link(new moodle_url('/local/bs_badge_pool/viewbadge.php',
        array('id' => $badge->id)), $linktext, $style);
    $description = html_writer::div($badge->description, 'description');
    $category = html_writer::div($badgecategory, 'category');
    $actions = local_bs_badge_pool_print_badge_table_actions($badge, $context, $output);
    $row = array($name, $description, $category, $actions);
    $table->data[] = $row;
}
// Free up some resources in the RDBMS after using $DB->get_recordset.
$badges->close();

$htmltable = html_writer::table($table);
echo $output->box($htmlpagingbar.$htmltable.$htmlpagingbar, 'generalbox');

if (has_capability('local/bs_badge_pool:managebadgepool', $context)) {
    $addbadgebutton = $output->single_button(
        new moodle_url('/local/bs_badge_pool/addbadge.php'), get_string('addnewbadge', 'local_bs_badge_pool'));
    echo $output->box($addbadgebutton, 'generalbox');
}
if (has_capability('local/bs_badge_pool:badgepoolimport', $context)) {
    $importbadgebutton = $output->single_button(
        new moodle_url('/local/bs_badge_pool/import.php'), get_string('importbadge', 'local_bs_badge_pool'));
    echo $output->box($importbadgebutton, 'generalbox');
}
if (has_capability('local/bs_badge_pool:badgepoolexport', $context)) {
    $exportbadgebutton = $output->single_button(
        new moodle_url('/local/bs_badge_pool/export.php'), get_string('exportbadge', 'local_bs_badge_pool'));
    echo $output->box($exportbadgebutton, 'generalbox');
}

echo $output->footer();
