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
 * Language file.
 *
 * @package    local_bs_badge_pool
 * @copyright  2015 onwards Matthias Schwabe {@link http://matthiasschwa.be}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Badge Pool';
$string['settings'] = 'Badge Pool Settings';
$string['manage'] = 'Manage Badge Pool';
$string['managecategories'] = 'Manage Badge Pool Categories';
$string['addbadge'] = '[...]';
$string['addbadgedesc'] = '[...]';
$string['addcategory'] = 'Add a new badge category';
$string['addnewbadge'] = 'Add a new badge to pool';
$string['category'] = 'Category';
$string['category_help'] = '[...]';
$string['createcategory'] = 'Create category';
$string['categoryselect'] = 'Select category';
$string['nocategories'] = 'No categories found. There has to be at least one category.';
$string['nobadges'] = 'No available pool badges found.';
$string['deletebadge'] = 'Delete badge "{$a}" from pool?';
$string['deletecategory'] = 'Delete category "{$a}"?';
$string['confirmdeletebadge'] = 'Delete pool badge';
$string['confirmdeletecategory'] = 'Delete category';
$string['messagedetails'] = 'Message details';
$string['emailoptions'] = 'Email options';
$string['usebadge'] = 'Use this Badge';
$string['importbadge'] = 'Import pool badges';
$string['exportbadge'] = 'Export pool badges';
$string['currentcategory'] = 'Current category:';
$string['categorynotempty'] = 'You can not delete this category. There are {$a} pool badges using it.';
$string['importfileencoding'] = 'Encoding';
$string['importcategory'] = 'Pool badge category';
$string['importhead'] = 'Import pool badges from a XML file';
$string['exporthead'] = 'Export pool badges to a XML file';
$string['import'] = 'Import';
$string['export'] = 'Export';
$string['xmlfile'] = 'XML file';
$string['impimportfailed'] = 'Import of XML file failed.';
$string['badgessaved'] = 'Import pool badges from a XML file';
$string['nobadgessaved'] = 'Import pool badges from a XML file';
$string['keepcategories'] = '- keep and create origin categories -';
$string['badgeadded'] = 'Added pool badge "{$a->badge}" to category "{$a->category}". (ID {$a->id})';
$string['badgesadded'] = '{$a} badges were added to badge pool.';
$string['nobadgesadded'] = 'No badges were added to pool.';
$string['exportselect'] = 'Select export:';
$string['exportbadges'] = 'Export selected badges';
$string['exportcategories'] = 'Export whole categories';
$string['xmlfilenameprefix'] = 'badgepool_export_{$a}';
$string['bs_badge_pool:badgepoolexport'] = 'Export pool badges to a XML file';
$string['bs_badge_pool:badgepoolimport'] = 'Import pool badges from a XML file';
$string['bs_badge_pool:managebadgepool'] = 'Manage badge pool';
$string['bs_badge_pool:managecategories'] = 'Manage badge pool categories';
$string['bs_badge_pool:usebadgepool'] = 'Use the badge pool in a course';
$string['about'] = 'About';
$string['donationtext'] = 'If you like this plugin and want to support my work please feel free to use this Paypal donation button:';
$string['abouttext'] = 'This plugin has been developed by Matthias Schwabe and is part of a Moodle badges related set of plugins.<br />Other plugins of this set are {$a->badgeenrol}, {$a->badgeladder} and {$a->recentbadges}.';
$string['aboutfeedbacktext'] = 'If you have any feedback or great ideas for new features, do not hesitate to leave a post on the {$a->aboutlink} or send me an e-mail to {$a->aboutmail}.<br /><br />';
$string['plugindirectory'] = 'Moodle plugin directory page';
$string['configuration'] = 'Configuration';
$string['enable_category'] = 'Enable badge pool for this category';
$string['enable_categories_head'] = 'Select the course categories in which the badge pool should be available. You can only select top level categories. All sub categories will inherit this choice.';
$string['badgepool_disbaled'] = 'Badge pool is disabled for this course category.';
