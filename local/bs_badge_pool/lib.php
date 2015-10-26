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
 * The library file for badge pool plugin.
 *
 * @package local_bs_badge_pool
 * @author Matthias Schwabe <mail@matthiasschwabe.de>
 * @copyright 2015 Matthias Schwabe
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

// Some file imports.
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/config.php');
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/badgeslib.php');
require_once($CFG->libdir.'/filelib.php');
require_once($CFG->libdir.'/filestorage/file_storage.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/gdlib.php');

// Show link to badge pool.
function local_bs_badge_pool_extend_settings_navigation (navigation_node $coursenode, $context) {
    global $PAGE, $CFG;

    if ($PAGE->course->id
        and $PAGE->course->id != SITEID
        and has_capability('local/bs_badge_pool:usebadgepool', $PAGE->context)
        and $CFG->badges_allowcoursebadges) {

        $url = new moodle_url('/local/bs_badge_pool/index.php', array('id' => $PAGE->course->id));
        $coursenode->get('courseadmin')->get('coursebadges')->add(get_string('pluginname', 'local_bs_badge_pool'), $url,
            navigation_node::TYPE_SETTING, null, 'courseadmin');
    }
}

class local_bs_badge_pool_badge_form extends moodleform {

    public function definition() {
        global $CFG, $DB;

        $mform = $this->_form;
        $badge = (isset($this->_customdata['badge'])) ? $this->_customdata['badge'] : false;
        $edit = optional_param('id', 0, PARAM_INT);

        $editoroptions = array(
            'subdirs' => 0,
            'maxbytes' => 0,
            'maxfiles' => 0,
            'changeformat' => 0,
            'context' => context_system::instance(),
            'noclean' => false,
            'trusttext' => false
        );

        $mform->addElement('header', 'badgedetails', get_string('badgedetails', 'badges'));

        $mform->addElement('text', 'name', get_string('name'), array('size' => '70'));
        // Using PARAM_FILE to avoid problems later when downloading badge files.
        $mform->setType('name', PARAM_FILE);
        $mform->addRule('name', null, 'required');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('textarea', 'description', get_string('description', 'badges'), 'wrap="virtual" rows="8" cols="70"');
        $mform->setType('description', PARAM_NOTAGS);
        $mform->addRule('description', null, 'required');

        $options = array();
        $categories = $DB->get_records('local_badge_pool_categories');
        foreach ($categories as $category) {
            $options[$category->id] = $category->name;
        }
        $mform->addElement('select', 'category', get_string('category', 'local_bs_badge_pool'), $options);
        $mform->addHelpButton('category', 'category', 'local_bs_badge_pool');
        $mform->addRule('category', null, 'required');

        $str = $edit == 0 ? get_string('badgeimage', 'badges') : get_string('newimage', 'badges');
        $imageoptions = array('maxbytes' => 262144, 'accepted_types' => array('web_image'));
        $mform->addElement('filepicker', 'image', $str, null, $imageoptions);

        if (!$edit) {
            $mform->addRule('image', null, 'required');
        } else {
            $currentimage = $mform->createElement('static', 'currentimage', get_string('currentimage', 'badges'));
            $mform->insertElementBefore($currentimage, 'image');
        }
        $mform->addHelpButton('image', 'badgeimage', 'badges');

        $mform->addElement('header', 'issuerdetails', get_string('issuerdetails', 'badges'));

        $mform->addElement('text', 'issuername', get_string('name'), array('size' => '70'));
        $mform->setType('issuername', PARAM_NOTAGS);
        $mform->addRule('issuername', null, 'required');
        if (isset($CFG->badges_defaultissuername)) {
            $mform->setDefault('issuername', $CFG->badges_defaultissuername);
        }
        $mform->addHelpButton('issuername', 'issuername', 'badges');

        $mform->addElement('text', 'issuercontact', get_string('contact', 'badges'), array('size' => '70'));
        if (isset($CFG->badges_defaultissuercontact)) {
            $mform->setDefault('issuercontact', $CFG->badges_defaultissuercontact);
        }
        $mform->setType('issuercontact', PARAM_RAW);
        $mform->addHelpButton('issuercontact', 'contact', 'badges');

        $mform->addElement('header', 'issuancedetails', get_string('issuancedetails', 'badges'));

        $issuancedetails = array();
        $issuancedetails[] =& $mform->createElement('radio', 'expiry', '', get_string('never', 'badges'), 0);
        $issuancedetails[] =& $mform->createElement('static', 'none_break', null, '<br/>');
        $issuancedetails[] =& $mform->createElement('radio', 'expiry', '', get_string('fixed', 'badges'), 1);
        $issuancedetails[] =& $mform->createElement('date_selector', 'expiredate', '');
        $issuancedetails[] =& $mform->createElement('static', 'expirydate_break', null, '<br/>');
        $issuancedetails[] =& $mform->createElement('radio', 'expiry', '', get_string('relative', 'badges'), 2);
        $issuancedetails[] =& $mform->createElement('duration', 'expireperiod', '',
            array('defaultunit' => 86400, 'optional' => false));
        $issuancedetails[] =& $mform->createElement('static', 'expiryperiods_break', null, get_string('after', 'badges'));

        $mform->addGroup($issuancedetails, 'expirydategr', get_string('expirydate', 'badges'), array(' '), false);
        $mform->addHelpButton('expirydategr', 'expirydate', 'badges');
        $mform->setDefault('expiry', 0);
        $mform->setDefault('expiredate', strtotime('+1 year'));
        $mform->disabledIf('expiredate[day]', 'expiry', 'neq', 1);
        $mform->disabledIf('expiredate[month]', 'expiry', 'neq', 1);
        $mform->disabledIf('expiredate[year]', 'expiry', 'neq', 1);
        $mform->disabledIf('expireperiod[number]', 'expiry', 'neq', 2);
        $mform->disabledIf('expireperiod[timeunit]', 'expiry', 'neq', 2);

        // Set issuer URL.
        // Have to parse URL because badge issuer origin cannot be a subfolder in wwwroot.
        $url = parse_url($CFG->wwwroot);
        $mform->addElement('hidden', 'issuerurl', $url['scheme'] . '://' . $url['host']);
        $mform->setType('issuerurl', PARAM_URL);

        $mform->addElement('header', 'badgemessage', get_string('configuremessage', 'badges'));
        $mform->addHelpButton('badgemessage', 'variablesubstitution', 'badges');

        $mform->addElement('text', 'messagesubject', get_string('subject', 'badges'), array('size' => '70'));
        $mform->setType('messagesubject', PARAM_TEXT);
        $mform->setDefault('messagesubject', get_string('messagesubject', 'badges'));
        $mform->addRule('messagesubject', null, 'required');
        $mform->addRule('messagesubject', get_string('maximumchars', '', 255), 'maxlength', 255);

        $mform->addElement('editor', 'message_editor', get_string('message', 'badges'), null, $editoroptions);
        $mform->setType('message_editor', PARAM_RAW);
        $mform->addRule('message_editor', null, 'required');

        $mform->addElement('advcheckbox', 'attachment', get_string('attachment', 'badges'), '', null, array(0, 1));
        $mform->addHelpButton('attachment', 'attachment', 'badges');
        if (empty($CFG->allowattachments)) {
            $mform->freeze('attachment');
        }

        $options = array(
            BADGE_MESSAGE_NEVER   => get_string('never'),
            BADGE_MESSAGE_ALWAYS  => get_string('notifyevery', 'badges'),
            BADGE_MESSAGE_DAILY   => get_string('notifydaily', 'badges'),
            BADGE_MESSAGE_WEEKLY  => get_string('notifyweekly', 'badges'),
            BADGE_MESSAGE_MONTHLY => get_string('notifymonthly', 'badges'),
        );
        $mform->addElement('select', 'notification', get_string('notification', 'badges'), $options);
        $mform->addHelpButton('notification', 'notification', 'badges');

        if (!$edit) {
            $this->add_action_buttons(true, get_string('createbutton', 'badges'));
        } else {
            $mform->addElement('hidden', 'id', $badge->id);
            $mform->setType('id', PARAM_INT);

            $this->add_action_buttons();
            $this->set_data($badge);
        }
    }

    public function set_data($badge) {

        $defaultvalues = array();
        parent::set_data($badge);

        if (!empty($badge->expiredate)) {
            $defaultvalues['expiry'] = 1;
            $defaultvalues['expiredate'] = $badge->expiredate;
        } else if (!empty($badge->expireperiod)) {
            $defaultvalues['expiry'] = 2;
            $defaultvalues['expireperiod'] = $badge->expireperiod;
        }
        $defaultvalues['currentimage'] = local_bs_badge_pool_print_badge_image($badge, context_system::instance());
        $defaultvalues['message'] = $badge->message_editor['text'];
        parent::set_data($defaultvalues);
    }

    /**
     * Form tweaks that depend on current data.
     */
    public function definition_after_data() {
        global $CFG;

        parent::definition_after_data();

        $edit = optional_param('id', 0, PARAM_INT);
        if (!$edit) {
            $mform = &$this->_form;
            $message = $mform->getElementValue('message_editor');
            $message['text'] = get_string('messagebody', 'badges',
            html_writer::link($CFG->wwwroot.'/badges/mybadges.php', get_string('managebadges', 'badges')));
            $mform->getElement('message_editor')->setValue($message);
        }
    }

    /**
     * Validates form data
     */
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);

        if (!empty($data['issuercontact']) && !validate_email($data['issuercontact'])) {
            $errors['issuercontact'] = get_string('invalidemail');
        }

        if ($data['expiry'] == 2 && $data['expireperiod'] <= 0) {
            $errors['expirydategr'] = get_string('error:invalidexpireperiod', 'badges');
        }

        if ($data['expiry'] == 1 && $data['expiredate'] <= time()) {
            $errors['expirydategr'] = get_string('error:invalidexpiredate', 'badges');
        }

        return $errors;
    }
}

class local_bs_badge_pool_category_form extends moodleform {

    public function definition() {
        global $CFG;

        $mform = $this->_form;
        $category = (isset($this->_customdata['category'])) ? $this->_customdata['category'] : false;
        $edit = optional_param('id', 0, PARAM_INT);

        $mform->addElement('text', 'name', get_string('name'), array('size' => '70'));
        // Using PARAM_FILE to avoid problems later when downloading badge files.
        $mform->setType('name', PARAM_FILE);
        $mform->addRule('name', null, 'required');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('textarea', 'description', get_string('description', 'badges'), 'wrap="virtual" rows="8" cols="70"');
        $mform->setType('description', PARAM_NOTAGS);

        if (!$edit) {
            $this->add_action_buttons(true, get_string('createcategory', 'local_bs_badge_pool'));
        } else {
            $mform->addElement('hidden', 'id', $category->id);
            $mform->setType('id', PARAM_INT);

            $this->add_action_buttons();
            $this->set_data($category);
        }
    }

    public function set_data($category) {
        parent::set_data($category);
    }

    /**
     * Validates form data
     */
    public function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);
        return $errors;
    }
}

function local_bs_badge_pool_delete_badge($badgeid) {
    global $DB;
    $DB->delete_records('local_badge_pool_badges', array('id' => $badgeid));
}

function local_bs_badge_pool_delete_category($catid) {
    global $DB;
    $DB->delete_records('local_badge_pool_categories', array('id' => $catid));
}

function local_bs_badge_pool_print_badge_image($badge, $context, $filename = 'f2.png') {

    $imageurl = moodle_url::make_pluginfile_url($context->id, 'local_bs_badge_pool', 'badgepool',
        $badge->id, '/', $filename, false);
    // Appending a random parameter to image link to force browser reload the image.
    $imageurl->param('refresh', rand(1, 10000));
    $attributes = array('src' => $imageurl, 'alt' => s($badge->name), 'class' => 'activatebadge');

    return html_writer::empty_tag('img', $attributes);
}

function local_bs_badge_pool_helper_sortable_heading($text, $sortid = null, $sortby = null, $sorthow = null, $output) {
    global $PAGE;

    $out = html_writer::tag('span', $text, array('class' => 'text'));

    if (!is_null($sortid)) {
        if ($sortby !== $sortid || $sorthow !== 'ASC') {
            $url = new moodle_url($PAGE->url);
            $url->params(array('sort' => $sortid, 'dir' => 'ASC'));
            $out .= $output->action_icon($url,
                new pix_icon('t/sort_asc', get_string('sortbyx', 'core', s($text)), null, array('class' => 'iconsort')));
        }

        if ($sortby !== $sortid || $sorthow !== 'DESC') {
            $url = new moodle_url($PAGE->url);
            $url->params(array('sort' => $sortid, 'dir' => 'DESC'));
            $out .= $output->action_icon($url,
                new pix_icon('t/sort_desc', get_string('sortbyxreverse', 'core', s($text)), null, array('class' => 'iconsort')));
        }
    }
    return $out;
}

/**
 * Prints badge table action icons.
 */
function local_bs_badge_pool_print_badge_table_actions($badge, $context, $output) {
    $actions = "";

    // Activate/deactivate pool badge.
    if ($badge->status == 0) {
        $url = new moodle_url('/local/bs_badge_pool/manage.php');
        $url->param('id', $badge->id);
        $url->param('status', 'unlock');
        $url->param('sesskey', sesskey());
        $actions .= $output->action_icon($url, new pix_icon('t/show', get_string('activate', 'badges'))) . " ";
    } else {
        $url = new moodle_url('/local/bs_badge_pool/manage.php');
        $url->param('id', $badge->id);
        $url->param('status', 'lock');
        $url->param('sesskey', sesskey());
        $actions .= $output->action_icon($url, new pix_icon('t/hide', get_string('deactivate', 'badges'))) . " ";
    }

    // Edit pool badge.
    $url = new moodle_url('/local/bs_badge_pool/edit.php', array('id' => $badge->id));
    $actions .= $output->action_icon($url, new pix_icon('t/edit', get_string('edit'))) . " ";

    // Delete badge.
    $url = new moodle_url('/local/bs_badge_pool/manage.php', array('delete' => $badge->id));
    $url->param('delete', $badge->id);
    $actions .= $output->action_icon($url, new pix_icon('t/delete', get_string('delete'))) . " ";

    return $actions;
}

/**
 * Prints badge category table action icons.
 */
function local_bs_badge_pool_print_category_table_actions($category, $context, $output) {
    $actions = "";

    // Edit pool badge category.
    $url = new moodle_url('/local/bs_badge_pool/editcategory.php', array('id' => $category->id));
    $actions .= $output->action_icon($url, new pix_icon('t/edit', get_string('edit'))) . " ";

    // Delete category.
    $url = new moodle_url('/local/bs_badge_pool/managecat.php', array('delete' => $category->id));
    $url->param('delete', $category->id);
    $actions .= $output->action_icon($url, new pix_icon('t/delete', get_string('delete'))) . " ";

    return $actions;
}

function local_bs_badge_pool_set_badge_status($badgeid, $status) {
    global $DB;

    $badge = $DB->get_record('local_badge_pool_badges', array('id' => $badgeid), '*', MUST_EXIST);
    $newstatus = $status == 'lock' ? 0 : 1;
    $badge->status = $newstatus;
    $DB->update_record('local_badge_pool_badges', $badge);
}

function local_bs_badge_pool_pluginfile($course, $birecord_or_cm, $context, $filearea, $args, $forcedownload,
    array $options=array()) {

    if ($context->contextlevel != CONTEXT_SYSTEM) {
        send_file_not_found();
    }

    if ($filearea !== 'badgepool') {
        send_file_not_found();
    }

    $fs = get_file_storage();

    $filename = array_pop($args);
    $filepath = $args ? '/'.implode('/', $args).'/' : '/';

    if (!$file = $fs->get_file($context->id, 'local_bs_badge_pool', 'badgepool', $args[0], '/', $filename)
        or $file->is_directory()) {

        send_file_not_found();
    }

    \core\session\manager::write_close();
    send_stored_file($file, null, 0, true, $options);
}

function definition_list(array $items, array $attributes = array()) {
    $output = html_writer::start_tag('dl', $attributes);
    foreach ($items as $label => $value) {
        $output .= html_writer::tag('dt', $label);
        $output .= html_writer::tag('dd', $value);
    }
    $output .= html_writer::end_tag('dl');
    return $output;
}

/**
 * Prints badge overview infomation.
 */
function local_bs_badge_pool_view_badge($badge, $context, $output) {
    global $DB;

    $display = "";

    // Badge details.
    $display .= $output->heading(get_string('badgedetails', 'badges'), 3);
    $category = $DB->get_field('local_badge_pool_categories', 'name', array('id' => $badge->categoryid));
    $dl = array();
    $dl[get_string('category')] = $category;
    $dl[get_string('description', 'badges')] = $badge->description;
    $display .= definition_list($dl);

    // Issuer details.
    $display .= $output->heading(get_string('issuerdetails', 'badges'), 3);
    $dl = array();
    $dl[get_string('issuername', 'badges')] = $badge->issuername;
    $dl[get_string('contact', 'badges')] = html_writer::tag('a', $badge->issuercontact,
        array('href' => 'mailto:'.$badge->issuercontact));
    $display .= definition_list($dl);

    // Email options.
    $display .= $output->heading(get_string('emailoptions', 'local_bs_badge_pool'), 3);
    $badgeattachment = $badge->attachment == 0 ? get_string('no') : get_string('yes');
    $options = array(
        get_string('never'),
        get_string('notifyevery', 'badges'),
        get_string('notifydaily', 'badges'),
        get_string('notifyweekly', 'badges'),
        get_string('notifymonthly', 'badges'),
    );
    $dl = array();
    $dl[get_string('attachment', 'badges')] = $badgeattachment;
    $dl[get_string('notification', 'badges')] = $options[$badge->notification];
    $display .= definition_list($dl);

    // Award Message.
    $display .= $output->heading(get_string('messagedetails', 'local_bs_badge_pool'), 3);
    $dl = array();
    $dl[get_string('subject', 'badges')] = $badge->messagesubject;
    $dl[get_string('message', 'badges')] = $badge->message;
    $display .= definition_list($dl);

    // Issuance details if any.
    $display .= $output->heading(get_string('issuancedetails', 'badges'), 3);

    if ($badge->expiredate) {
        $display .= get_string('expiredate', 'badges', userdate($badge->expiredate));
    } else if ($badge->expireperiod) {
        if ($badge->expireperiod < 60) {
            $display .= get_string('expireperiods', 'badges', round($badge->expireperiod, 2));
        } else if ($badge->expireperiod < 60 * 60) {
            $display .= get_string('expireperiodm', 'badges', round($badge->expireperiod / 60, 2));
        } else if ($badge->expireperiod < 60 * 60 * 24) {
            $display .= get_string('expireperiodh', 'badges', round($badge->expireperiod / 60 / 60, 2));
        } else {
            $display .= get_string('expireperiod', 'badges', round($badge->expireperiod / 60 / 60 / 24, 2));
        }
    } else {
        $display .= get_string('noexpiry', 'badges');
    }

    return html_writer::div($display, null, array('id' => 'badge-overview'));
}

class local_bs_badge_pool_import_form extends moodleform {

    protected function definition() {
        global $DB;

        $mform =& $this->_form;
        $cmid = $this->_customdata['id'];

        $mform->addElement('filepicker', 'xmlfile', get_string('xmlfile', 'local_bs_badge_pool'));

        //$options = core_text::get_encodings();
        //$mform->addElement('select', 'encoding', get_string('importfileencoding', 'local_bs_badge_pool'), $options);
        //$mform->setDefault('encoding', 'UTF-8');

        $categories = $DB->get_records('local_badge_pool_categories', null, 'name', 'id,name');
        $options = array('0' => get_string('keepcategories', 'local_bs_badge_pool'));
        foreach ($categories as $cat) {
            $options[$cat->id] = $cat->name;
        }
        $mform->addElement('select', 'category', get_string('importcategory', 'local_bs_badge_pool'), $options);
        $mform->setDefault('category', '0');

        $this->add_action_buttons(false, get_string('submit'));
    }
}

function local_bs_badge_pool_build_xml($ids, $mode) {
    global $CFG, $DB, $USER;

    $count = 0;
    $list = join(',', $ids); // Array to list for sql.

    $xmlcontent = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
    $xmlcontent .= '<data>'."\n";

    if ($mode === 1) {

        $sql = "SELECT b.*,
                       c.id AS catid,
                       c.name AS catname,
                       c.description AS catdesc
                  FROM {local_badge_pool_categories} AS c
            INNER JOIN {local_badge_pool_badges} AS b ON c.id = b.categoryid
                 WHERE c.id IN (".$list.")
              ORDER BY catid DESC";
    }

    if ($mode === 2) {

        $sql = "SELECT b.*,
                       c.id AS catid,
                       c.name AS catname,
                       c.description AS catdesc
                  FROM {local_badge_pool_badges} AS b
            INNER JOIN {local_badge_pool_categories} AS c ON b.categoryid = c.id
                 WHERE b.id IN (".$list.")
              ORDER BY b.categoryid DESC";
    }

    $data = $DB->get_records_sql($sql);
    $context = context_system::instance();
    $categories = array();

    foreach ($data as $d) {

        $imgurl = moodle_url::make_pluginfile_url($context->id, 'local_bs_badge_pool', 'badgepool', $d->id, '/', 'f3.png', false);
        $imgcode = base64_encode(file_get_contents($imgurl));

        if (!in_array($d->catid, $categories)) {
            if ($count !== 0) {
                $xmlcontent .= '</category>'."\n";
            }
            $categories[] = $d->catid;
            $xmlcontent .= '<category name="'.$d->catname.'">'."\n";
            $xmlcontent .= '<catdescription>'.$d->catdesc.'</catdescription>'."\n";
        }

        $xmlcontent .= '<badge>'."\n";

        $xmlcontent .= '<name>'.$d->name.'</name>'."\n";
        $xmlcontent .= '<description>'.$d->description.'</description>'."\n";
        $xmlcontent .= '<issuername>'.$d->issuername.'</issuername>'."\n";
        $xmlcontent .= '<issuerurl>'.$d->issuerurl.'</issuerurl>'."\n";
        $xmlcontent .= '<issuercontact>'.$d->issuercontact.'</issuercontact>'."\n";
        $xmlcontent .= '<expiredate>'.$d->expiredate.'</expiredate>'."\n";
        $xmlcontent .= '<expireperiod>'.$d->expireperiod.'</expireperiod>'."\n";
        $xmlcontent .= '<type>'.$d->type.'</type>'."\n";
        $xmlcontent .= '<message><![CDATA['.$d->message.']]></message>'."\n";
        $xmlcontent .= '<messagesubject>'.$d->messagesubject.'</messagesubject>'."\n";
        $xmlcontent .= '<attachment>'.$d->attachment.'</attachment>'."\n";
        $xmlcontent .= '<notification>'.$d->notification.'</notification>'."\n";
        $xmlcontent .= '<image encoding="base64">'.$imgcode.'</image>'."\n";

        $xmlcontent .= '</badge>'."\n";
        $count++;
    }

    $xmlcontent .= '</category>'."\n";
    $xmlcontent .= '</data>';

    $date = date("Ymd_His");
    $xmlfilename = get_string('xmlfilenameprefix', 'local_bs_badge_pool', $date).'.xml';

    $itemid = mt_rand(0, 65535);
    $fileinfo = array(
        'contextid' => $context->id,
        'component' => 'local_bs_badge_pool',
        'filearea'  => 'badgepool',
        'itemid'    => $itemid,
        'filepath'  => '/',
        'filename'  => $xmlfilename,
        'mimetype'  => 'application/xml',
        'userid'    => $USER->id
    );

    $fs = get_file_storage();
    $fs->create_file_from_string($fileinfo, $xmlcontent);
    $fileurl = moodle_url::make_pluginfile_url($context->id, 'local_bs_badge_pool', 'badgepool', $itemid, '/', $xmlfilename, true);

    redirect($fileurl);
}
