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
 * Global management for pool badge categories.
 *
 * @package    local_bs_badge_pool
 * @copyright  2015 onwards Matthias Schwabe {@link http://matthiasschwa.be}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/lib.php');

$dir = optional_param('dir', 'ASC', PARAM_TEXT);
$delete = optional_param('delete', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);

require_login();
$context = context_system::instance();
require_capability('local/bs_badge_pool:managecategories', $context);

$pageparams = array();
admin_externalpage_setup('local_bs_badge_pool_cat', '', $pageparams);
$siteurl = new moodle_url('/local/bs_badge_pool/managecat.php', $pageparams);
$PAGE->set_url($siteurl);

if ($confirm and $delete) {
    require_sesskey();
    local_bs_badge_pool_delete_category($delete);
    redirect($PAGE->url);
}

$output = $PAGE->get_renderer('core', 'badges');
echo $output->header();

if ($delete) {

    $catname = $DB->get_field('local_bs_badge_pool_cat', 'name', array('id' => $delete));
    echo $output->heading(get_string('deletecategory', 'local_bs_badge_pool', $catname));

    // Make sure no pool badge uses this category.
    if ($count = $DB->count_records('local_bs_badge_pool_badges', array('categoryid' => $delete))) {
        echo $output->box(get_string('categorynotempty', 'local_bs_badge_pool', $count), 'generalbox');

    } else {

        $deletebutton = $output->single_button(
            new moodle_url($PAGE->url, array('delete' => $delete, 'confirm' => 1)),
                get_string('confirmdeletecategory', 'local_bs_badge_pool'));
        echo $output->box($deletebutton, 'generalbox');
    }

    echo $output->action_link($PAGE->url, get_string('cancel'));
    echo $output->footer();

} else {

    $perpage = 12;
    $start = $page == 0 ? 0 : $page * $perpage;

    // Get pool badge categories in right sort order.
    $sql = "SELECT *
              FROM {local_bs_badge_pool_cat}
          ORDER BY name ".$dir;

    $categories = $DB->get_recordset_sql($sql, null, $start, $perpage);
    $badgescount = $DB->count_records('local_bs_badge_pool_cat');

    // True = links for prev, next, first & last page.
    $paging = new paging_bar($badgescount, $page, $perpage, $PAGE->url, 'page', true, true, true, true);
    $htmlpagingbar = $output->render($paging);

    $table = new html_table();
    $table->attributes['class'] = 'collection';

    $sortbyname = local_bs_badge_pool_helper_sortable_heading(get_string('name'), 'name', 'name', $dir, $output);
    $table->head = array(
        $sortbyname,
        get_string('description'),
        get_string('actions')
    );

    $table->colclasses = array('name', 'description', 'actions');

    foreach ($categories as $category) {

        $name = html_writer::div($category->name, 'name');
        $description = html_writer::div($category->description, 'description');
        $actions = local_bs_badge_pool_print_category_table_actions($category, $context, $output);
        $row = array($name, $description, $actions);
        $table->data[] = $row;
    }
    // Free up some resources in the RDBMS after using $DB->get_recordset.
    $categories->close();

    $htmltable = html_writer::table($table);
    echo $output->box($htmlpagingbar.$htmltable.$htmlpagingbar, 'generalbox');

    if (has_capability('local/bs_badge_pool:managecategories', $context)) {
        $addcategorybutton = $output->single_button (
            new moodle_url('/local/bs_badge_pool/addcategory.php'), get_string('addcategory', 'local_bs_badge_pool'));
        echo $output->box($addcategorybutton, 'generalbox');
    }

    echo $output->footer();
}
