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
 * Adds a pool badge category.
 *
 * @package local_bs_badge_pool
 * @author Matthias Schwabe <mail@matthiasschwabe.de>
 * @copyright 2015 Matthias Schwabe
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

require_once(dirname(__FILE__).'/lib.php');

require_login();
require_capability('local/bs_badge_pool:managecategories', context_system::instance());

$pageparams = array();
admin_externalpage_setup('local_bs_badge_pool_cat', '', $pageparams);

$form = new local_bs_badge_pool_category_form(new moodle_url('/local/bs_badge_pool/addcategory.php'));

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/bs_badge_pool/managecat.php', array()));
} else if ($data = $form->get_data()) {

    // Creating a new badge pool category.
    $category = new stdClass();
    $category->name = $data->name;
    $category->description = $data->description;

    $categoryid = $DB->insert_record('local_badge_pool_categories', $category, true);

    redirect(new moodle_url('/local/bs_badge_pool/managecat.php', array('id' => $categoryid)));
}

echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();
