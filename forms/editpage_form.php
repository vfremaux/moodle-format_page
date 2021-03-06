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
 * Page editing form
 *
 * @author Mark Nielsen
 * @author Valery Fremaux (valery.fremaux@gmail.com) for Moodle 2.x
 * @package format_page
 * @category format
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

class page_editpage_form extends moodleform {

    function definition() {
        global $COURSE, $PAGE;

        $mform =& $this->_form;

        $mform->addElement('hidden', 'action', 'editpage');
        $mform->setType('action', PARAM_ALPHA);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'page', 0);
        $mform->setType('page', PARAM_INT);

        $mform->addElement('hidden', 'returnaction');
        $mform->setType('returnaction', PARAM_ALPHA);

        if (!empty($this->_customdata['pageid']) && $this->_customdata['globaltemplates']) {
            $mform->addElement('header', 'editpagesettings', get_string('editpagesettings', 'format_page'));
            $mform->setExpanded('editpagesettings');
        } else {
            if (!empty($this->_customdata['globaltemplates'])) {
                $mform->addElement('header', 'newpagesettings', get_string('ornewpagesettings', 'format_page'));
            } else {
                $mform->addElement('header', 'newpagesettings', get_string('newpagesettings', 'format_page'));
            }
            $mform->setExpanded('newpagesettings');
        }

        $mform->addElement('text', 'nameone', get_string('pagenameone', 'format_page'), array('size' => '20'));
        $mform->setType('nameone', PARAM_CLEANHTML);

        $mform->addElement('text', 'nametwo', get_string('pagenametwo', 'format_page'), array('size' => '20'));
        $mform->setType('nametwo', PARAM_CLEANHTML);

        $config = get_config('format_page');
        if (!empty($config->protectidnumbers)) {
            $mform->addElement('hidden', 'idnumber');
            $mform->addElement('static', 'idnumberdisplay', get_string('idnumber', 'format_page'));
        } else {
            $mform->addElement('text', 'idnumber', get_string('idnumber', 'format_page'), array('size' => '10'));
        }
        $mform->setType('idnumber', PARAM_TEXT);

        $publishoptions = array(
            FORMAT_PAGE_DISP_HIDDEN => get_string('hidden', 'format_page'),
            FORMAT_PAGE_DISP_PROTECTED => get_string('protected', 'format_page'),
            FORMAT_PAGE_DISP_PUBLISHED => get_string('published', 'format_page'),
            FORMAT_PAGE_DISP_PUBLIC => get_string('public', 'format_page'),
        );
        $group00 = array();

        $group00[0] = & $mform->createElement('select', 'display', get_string('publish', 'format_page'), $publishoptions);
        $group00[1] = & $mform->createElement('checkbox', 'displayapplytoall', '');

        $mform->addGroup($group00, '', get_string('publish', 'format_page'), ' '.get_string('applytoallpages', 'format_page').':', false);

        $mform->setDefault('display', FORMAT_PAGE_DISP_PUBLISHED);
        $mform->setType('display', PARAM_INT);

        $options = array(get_string('no'), get_string('yes'));

        if (has_capability('format/page:editprotectedpages', context_course::instance($COURSE->id))) {
    
            $group02 = array();
    
            $group02[0] = & $mform->createElement('select', 'protected', get_string('editprotected', 'format_page'), $options);
            $group02[1] = & $mform->createElement('checkbox', 'protectedapplytoall', '');
    
            $mform->addGroup($group02, 'protectedgroup', get_string('editprotected', 'format_page'), ' '.get_string('applytoallpages', 'format_page').':', false);
            $mform->setDefault('protected', 0);
            $mform->setType('protected', PARAM_BOOL);
            $mform->addHelpButton('protectedgroup', 'protected', 'format_page');
        }

        $group01 = array();

        $group01[0] = & $mform->createElement('select', 'displaymenu', get_string('displaymenu', 'format_page'), $options);
        $group01[1] = & $mform->createElement('checkbox', 'displaymenuapplytoall', '');

        $mform->addGroup($group01, '', get_string('displaymenu', 'format_page'), ' '.get_string('applytoallpages', 'format_page').':', false);
        $mform->setDefault('dispmenu', 0);

        $group = array();
        $group[0] = & $mform->createElement('text', 'prefleftwidth', get_string('preferredleftcolumnwidth', 'format_page'), array('size'=>'6'));
        $group[1] = & $mform->createElement('checkbox', 'prefleftwidthapplytoall', '');
        $mform->addGroup($group, 'widthleftgroup', get_string('preferredleftcolumnwidth', 'format_page'), ' '.get_string('applytoallpages', 'format_page').':', false);
        $mform->addHelpButton('widthleftgroup', 'prefwidth', 'format_page');

        $prefs = new StdClass();
        if (in_array('bootstrapbase', $PAGE->theme->parents) || in_array('clean', $PAGE->theme->parents) || preg_match('/bootstrap|essential/', $PAGE->theme->name)) {
            $prefs->left = 3;
            $prefs->center = 6;
            $prefs->right = 3;
        } else {
            $prefs->left = 200;
            $prefs->center = 600;
            $prefs->right = 200;
        }

        $mform->setType('prefleftwidth', PARAM_TEXT);
        $mform->setDefault('prefleftwidth', $prefs->left);
        $mform->setType('prefleftwidthapplytoall', PARAM_BOOL);

        $group1  = array();
        $group1[0] = $mform->createElement('text', 'prefcenterwidth', get_string('preferredcentercolumnwidth', 'format_page'), array('size'=>'6'));
        $group1[1] = & $mform->createElement('checkbox', 'prefcenterwidthapplytoall', '');
        $mform->addGroup($group1, 'widthcentergroup', get_string('preferredcentercolumnwidth', 'format_page'), ' '.get_string('applytoallpages', 'format_page').':', false);
        $mform->addHelpButton('widthcentergroup', 'prefwidth', 'format_page');

        $mform->setType('prefcenterwidth', PARAM_TEXT);
        $mform->setDefault('prefcenterwidth', $prefs->center);
        $mform->setType('prefcenterwidthapplytoall', PARAM_BOOL);

        $group2  = array();
        $group2[0] = $mform->createElement('text', 'prefrightwidth', get_string('preferredrightcolumnwidth', 'format_page'), array('size'=>'6'));
        $group2[1] = & $mform->createElement('checkbox', 'prefrightwidthapplytoall', '');
        $mform->addGroup($group2, 'widthrightgroup', get_string('preferredrightcolumnwidth', 'format_page'), ' '.get_string('applytoallpages', 'format_page').':', false);
        $mform->addHelpButton('widthrightgroup', 'prefwidth', 'format_page');

        $mform->setType('prefrightwidth', PARAM_TEXT);
        $mform->setDefault('prefrightwidth', $prefs->right);
        $mform->setType('prefcenterwidthapplytoall', PARAM_BOOL);

        $options = array(
            0 => get_string('noprevnextbuttons', 'format_page'),
            FORMAT_PAGE_BUTTON_PREV => get_string('prevonlybutton', 'format_page'),
            FORMAT_PAGE_BUTTON_NEXT => get_string('nextonlybutton', 'format_page'),
            FORMAT_PAGE_BUTTON_BOTH => get_string('bothbuttons', 'format_page'),
        );

        $group3 = array();
        $group3[0] = & $mform->createElement('select', 'showbuttons', get_string('showbuttons', 'format_page'), $options);
        $mform->setDefault('showbuttons', 0);
        $group3[1] = & $mform->createElement('checkbox', 'showbuttonsapplytoall', '');
        $mform->addGroup($group3, '', get_string('showbuttons', 'format_page'), ' '.get_string('applytoallpages', 'format_page').':', false);

        $mform->addElement('selectyesno', 'template', get_string('useasdefault', 'format_page'));
        $mform->setDefault('template', 0);

        $mform->addElement('selectyesno', 'globaltemplate', get_string('globaltemplate', 'format_page'));
        $mform->setDefault('globaltemplate', 0);
        $mform->addHelpButton('globaltemplate', 'globaltemplate', 'format_page');

        if (!empty($this->_customdata['parents'])) {
            $mform->addElement('select', 'parent', get_string('parent', 'format_page'), $this->_customdata['parents']);
            $mform->setDefault('parent', 0);
        } else {
            $mform->addElement('static', 'noparents', get_string('parent', 'format_page'), get_string('noparents', 'format_page'));
            $mform->addElement('hidden', 'parent', 0);
            $mform->setType('parent', PARAM_INT);
        }

        // Override activity.

        $mform->addElement('header', 'h1', get_string('activityoverride', 'format_page'));
        $mform->addHelpButton('h1', 'activityoverride', 'format_page');
        if ($modules = course_page::get_modules('name+IDNumber')) {
            // From our modules object we can build an existing module menu using separators.
            $options = array();
            $options[0] = get_string('nooverride', 'format_page');
            foreach ($modules as $modplural => $instances) {
                asort($instances);
                foreach ($instances as $cmid => $name) {
                    $options[$cmid] = $name;
                }
            }

            $mform->addElement('select', 'cmid', get_string('override', 'format_page'), $options);

            $mform->addElement('header', 'h1', get_string('activitylock', 'format_page'));

            $options[0] = get_string('nolock', 'format_page');
            $mform->addElement('select', 'lockingcmid', get_string('locking', 'format_page'), $options);

            $gradeoptions = array('-1' => get_string('disabled', 'format_page'),
                                  '0' => '0%',
                                  '10' => '10%',
                                  '20' => '20%',
                                  '30' => '30%',
                                  '40' => '40%',
                                  '50' => '50%',
                                  '60' => '60%',
                                  '70' => '70%',
                                  '80' => '80%',
                                  '90' => '90%',
                                  '100' => '100%');
            $mform->addElement('select', 'lockingscore', get_string('lockingscore', 'format_page'), $gradeoptions);
            $mform->addElement('select', 'lockingscoreinf', get_string('lockingscoreinf', 'format_page'), $gradeoptions);

            $mform->addElement('header', 'h2', get_string('timelock', 'format_page'));

            $mform->addElement('date_time_selector', 'datefrom', get_string('from'), array('optional' => true));
            $mform->disabledIf('datefrom', 'relativeweek', 'neq', 0);
            $mform->addElement('date_time_selector', 'dateto', get_string('to'), array('optional' => true));
            $mform->disabledIf('dateto', 'relativeweek', 'neq', 0);

            $relativeoptions[0] = get_string('disabled', 'format_page');

            for ($i = 1; $i < 30; $i++) {
                $relativeoptions[$i] = '+'.$i.' '.get_string('weeks');
            }
            $relativeoptions[1] = '+1 '.get_string('week');

            $mform->addElement('select', 'relativeweek', get_string('relativeweek', 'format_page'), $relativeoptions);

        } else {
            $mform->addElement('static', 'nomodules', get_string('nomodules', 'format_page'), '');
        }

        if (empty($this->_customdata['pageid']) && $this->_customdata['globaltemplates']) {
            // Only when creating a page.

            $mform->addElement('header', 'choosetemplate', get_string('choosetemplate', 'format_page'));
            $mform->setExpanded('choosetemplate');

            $mform->addElement('selectgroups', 'usetemplate', get_string('template', 'format_page'), $this->_customdata['globaltemplates']);

            if (!empty($this->_customdata['parents'])) {
                $mform->addElement('select', 'templateinparent', get_string('parent', 'format_page'), $this->_customdata['parents']);
                $mform->setDefault('templateinparent', 0);
            } else {
                $mform->addElement('static', 'noparents', get_string('parent', 'format_page'), get_string('noparents', 'format_page'));
                $mform->addElement('hidden', 'templateinparent', 0);
                $mform->setType('templateinparent', PARAM_INT);
            }

            $mform->addElement('text', 'extnameone', get_string('pagenameone', 'format_page'), array('size'=>'20'));
            $mform->setType('extnameone', PARAM_CLEANHTML);

            $mform->addElement('text', 'extnametwo', get_string('pagenametwo', 'format_page'), array('size'=>'20'));
            $mform->setType('extnametwo', PARAM_CLEANHTML);

            $config = get_config('format_page');
            if (!empty($config->protectidnumbers)) {
                $mform->addElement('hidden', 'idnumber');
                $mform->addElement('static', 'idnumberdisplay', get_string('idnumber', 'format_page'));
            } else {
                $mform->addElement('text', 'idnumber', get_string('idnumber', 'format_page'), array('size'=>'10'));
                $mform->setType('idnumber', PARAM_TEXT);
            }

            $mform->addElement('checkbox', 'recurse', get_string('recurse', 'format_page'));
            $mform->setType('recurse', PARAM_BOOL);

            $mform->addElement('submit', 'addtemplate', get_string('addtemplate', 'format_page'));
        }

        $this->add_action_buttons();
    }

    public function validation($data, $files = array()) {

        $errors = array();

        if (!empty($data->usetemplate)) {

            if (empty($data['extnameone']) && empty($data['addtemplate'])) {
                $errors['extnameone'] = get_string('errornameneeded', 'format_page');
            }

            if (empty($data['extnametwo']) && !empty($data['submitbutton'])) {
                $errors['extnametwo'] = get_string('errornameneeded', 'format_page');
            }
        } else {
            if (empty($data['nameone']) && empty($data['addtemplate'])) {
                $errors['nameone'] = get_string('errornameneeded', 'format_page');
            }

            if (empty($data['nametwo']) && !empty($data['submitbutton'])) {
                $errors['nametwo'] = get_string('errornameneeded', 'format_page');
            }
        }

        return $errors;
    }
}
