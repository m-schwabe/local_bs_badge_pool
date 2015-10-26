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
 * @package local_bs_badge_pool
 * @author Matthias Schwabe <mail@matthiasschwabe.de>
 * @copyright 2015 Matthias Schwabe
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

defined('MOODLE_INTERNAL') || die;

if (has_capability('local/bs_badge_pool:managebadgepool', context_system::instance())) {

    $ADMIN->add('badges', new admin_category('local_bs_badge_pool_folder',
                get_string('pluginname', 'local_bs_badge_pool')));

    $ADMIN->add('local_bs_badge_pool_folder', new admin_externalpage('local_bs_badge_pool',
                get_string('manage', 'local_bs_badge_pool'),
                new moodle_url('/local/bs_badge_pool/manage.php'), 'local/bs_badge_pool:managebadgepool'));

    $ADMIN->add('local_bs_badge_pool_folder', new admin_externalpage('local_bs_badge_pool_cat',
                get_string('managecategories', 'local_bs_badge_pool'),
                new moodle_url('/local/bs_badge_pool/managecat.php'), 'local/bs_badge_pool:managecategories'));
}
