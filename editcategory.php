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
 * Adds a pool badge category or edits an existing pool badge category.
 *
 * @package    local_bs_badge_pool
 * @copyright  2015 onwards Matthias Schwabe {@link http://matthiasschwa.be}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/lib.php');

$categoryid = required_param('id', PARAM_INT);
$context = context_system::instance();

require_login();
require_capability('local/bs_badge_pool:managecategories', $context);

$pageparams = array();
admin_externalpage_setup('local_bs_badge_pool_cat', '', $pageparams);

$category = $DB->get_record('local_bs_badge_pool_cat', array('id' => $categoryid));

$form = new local_bs_badge_pool_category_form(new moodle_url('/local/bs_badge_pool/editcategory.php', array('id' => $categoryid)),
    array('category' => $category, 'action' => 'edit'));

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/bs_badge_pool/managecat.php', array()));
} else if ($data = $form->get_data()) {

    // Editing pool badge category.
    $category = new stdClass();
    $category->id = $categoryid;
    $category->name = $data->name;
    $category->description = $data->description;

    $DB->update_record('local_bs_badge_pool_cat', $category);

    redirect(new moodle_url('/local/bs_badge_pool/managecat.php'));
}

echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();
