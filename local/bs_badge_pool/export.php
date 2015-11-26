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
 * Exports pool bages to a XML file.
 *
 * @package local_bs_badge_pool
 * @author Matthias Schwabe <mail@matthiasschwabe.de>
 * @copyright 2015 Matthias Schwabe
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

require_once(dirname(__FILE__).'/lib.php');

$export = optional_param('export', 0, PARAM_INT);
$dir = optional_param('dir', 'ASC', PARAM_TEXT);
$sort = optional_param('sort', 'name', PARAM_TEXT);
$bids = optional_param_array('bids', array(), PARAM_INT);
$cids = optional_param_array('cids', array(), PARAM_INT);

require_login();

$context = context_system::instance();
require_capability('local/bs_badge_pool:badgepoolexport', $context);

$pageparams = array();
if ($export) {
    $pageparams['export'] = $export;
}
admin_externalpage_setup('local_bs_badge_pool', '', $pageparams);
$siteurl = new moodle_url('/local/bs_badge_pool/export.php', $pageparams);
$PAGE->set_url($siteurl);
$PAGE->navbar->add(get_string('export', 'local_bs_badge_pool'), new moodle_url('/local/bs_badge_pool/export.php'));

if ($bids) {
    local_bs_badge_pool_build_xml($bids, 2);
}

if ($cids) {
    local_bs_badge_pool_build_xml($cids, 1);
}

$OUTPUT = $PAGE->get_renderer('core', 'badges');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('exporthead', 'local_bs_badge_pool'));

if (!$export and !$bids and !$cids) {

    $options = array(1 => get_string('exportcategories', 'local_bs_badge_pool'),
        2 => get_string('exportbadges', 'local_bs_badge_pool')
    );

    echo html_writer::div($OUTPUT->single_select($siteurl, 'export', $options, $export,
        array(0 => get_string('exportselect', 'local_bs_badge_pool')), 'exportselect'), 'exportform');
}

if ($export === 1) {

    $sortbyname = local_bs_badge_pool_helper_sortable_heading(get_string('name'), 'name', $sort, $dir, $OUTPUT);

    $table = new html_table();
    $table->attributes['class'] = 'collection';

    $table->head = array(
        '',
        $sortbyname
    );

    $table->colclasses = array('select', 'name');

    $sql = "SELECT id, name
			  FROM {local_bs_badge_pool_cat}
		  ORDER BY ".$sort." ".$dir;

    $categories = $DB->get_records_sql($sql);

    echo '<form action="export.php?export=1" method="post">';
    foreach ($categories as $category) {
        $badgescount = $DB->count_records('local_bs_badge_pool_badges', array('categoryid' => $category->id));
        $checkbox = '';
        if ($badgescount) {
            $checkbox = '<input type="checkbox" name="cids[]" value="'.$category->id.'">';
        }
        $nametext = html_writer::start_tag('span').$category->name.' ('.$badgescount.' '.get_string('badges').')'.
            html_writer::end_tag('span');
        $row = array($checkbox, $nametext);
        $table->data[] = $row;
    }

    $htmltable = html_writer::table($table);
    echo $OUTPUT->box($htmltable, 'generalbox');
    echo '<input type="submit" value="'.get_string('export', 'local_bs_badge_pool').'">';
    echo '</form>';
}

if ($export === 2) {

    $sortbyname = local_bs_badge_pool_helper_sortable_heading(get_string('name'), 'name', $sort, $dir, $OUTPUT);
    $sortbycategory = local_bs_badge_pool_helper_sortable_heading(get_string('category'), 'categoryid', $sort, $dir, $OUTPUT);

    $table = new html_table();
    $table->attributes['class'] = 'collection';

    $table->head = array(
        '',
        $sortbyname,
        $sortbycategory,
    );

    $table->colclasses = array('select', 'name', 'category');

    $sql = "SELECT *
			  FROM {local_bs_badge_pool_badges}
		  ORDER BY ".$sort." ".$dir;

    $badges = $DB->get_records_sql($sql);

    echo '<form action="export.php?export=2" method="post">';
    foreach ($badges as $badge) {
        $checkbox = '<input type="checkbox" name="bids[]" value="'.$badge->id.'">';
        $linktext = local_bs_badge_pool_print_badge_image($badge, $context). ' '.
        html_writer::start_tag('span').$badge->name.html_writer::end_tag('span');
        $name = html_writer::link(new moodle_url('/local/bs_badge_pool/viewbadge.php',
            array('id' => $badge->id)), $linktext, null);
        $badgecategory = $DB->get_field('local_bs_badge_pool_cat', 'name', array('id' => $badge->categoryid));
        $category = html_writer::div($badgecategory, 'category');
        $row = array($checkbox, $name, $category);
        $table->data[] = $row;
    }

    $htmltable = html_writer::table($table);
    echo $OUTPUT->box($htmltable, 'generalbox');
    echo '<input type="submit">';
    echo '</form>';
}

echo $OUTPUT->footer();
