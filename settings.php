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
 * Global settings for badge pool plugin.
 *
 * @package    local_bs_badge_pool
 * @copyright  2015 onwards Matthias Schwabe {@link http://matthiasschwa.be}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if (has_capability('local/bs_badge_pool:managebadgepool', context_system::instance())) {

    // Badge Pool management.
    $ADMIN->add('badges', new admin_category('local_bs_badge_pool_manage_folder',
                get_string('pluginname', 'local_bs_badge_pool')));

    $ADMIN->add('local_bs_badge_pool_manage_folder', new admin_externalpage('local_bs_badge_pool_manage',
                get_string('manage', 'local_bs_badge_pool'),
                new moodle_url('/local/bs_badge_pool/manage.php'), 'local/bs_badge_pool:managebadgepool'));

    $ADMIN->add('local_bs_badge_pool_manage_folder', new admin_externalpage('local_bs_badge_pool_managecat',
                get_string('managecategories', 'local_bs_badge_pool'),
                new moodle_url('/local/bs_badge_pool/managecat.php'), 'local/bs_badge_pool:managecategories'));

    // Admin settings.
    $ADMIN->add('localplugins', new admin_category('local_bs_badge_pool_admin_folder',
                get_string('pluginname', 'local_bs_badge_pool')));

    $settings = new admin_settingpage('local_bs_badge_pool', get_string('configuration', 'local_bs_badge_pool'));

    $settings->add(new admin_setting_heading('local_bs_badge_pool', '',
                   get_string('enable_categories_head', 'local_bs_badge_pool')));

    $categories = $DB->get_records('course_categories', array('parent' => 0), null, 'id, name');
    foreach ($categories as $category) {
        $settings->add(new admin_setting_configcheckbox('local_bs_badge_pool/enable_category_'.$category->id,
                       $category->name, get_string('enable_category', 'local_bs_badge_pool'), 0));
    }

    $ADMIN->add('local_bs_badge_pool_admin_folder', $settings);

    $ADMIN->add('local_bs_badge_pool_admin_folder', new admin_externalpage('local_bs_badge_pool_about',
                get_string('about', 'local_bs_badge_pool'), new moodle_url('/local/bs_badge_pool/about.php')));

    $settings = null;
}
