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
 * Provides support for the conversion of moodle1 backup to the moodle2 format
 * Based off of a template @ http://docs.moodle.org/dev/Backup_1.9_conversion_for_developers
 *
 * @package    format
 * @subpackage page
 * @copyright  2011 Valery Fremaux <valery.fremaux@gmail.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * format page conversion handler
 * there is NO structure change in data storage from 1.9 so parsing flow is linear.
 */
class moodle1_format_page_handler extends moodle1_format_handler {

    /** @var moodle1_file_manager */
    protected $fileman = null;

    // Old display modes.
    const M19_DISP_PUBLISH = 1;  // publish page (show when editing turned off)
    const M19_DISP_MENU = 4;     // menu (show page in menus)

    // New display modes.
    const FORMAT_PAGE_DISP_HIDDEN = 0;  // publish page (show when editing turned off)
    const FORMAT_PAGE_DISP_PUBLISHED = 1;  // publish page (show when editing turned off)
    const FORMAT_PAGE_DISP_PROTECTED = 2;  // protected page (only for capability enabled people)
    const FORMAT_PAGE_DISP_PUBLIC = 3;  // publish page (show when editing turned off)
    
    /**
     * Declare the paths in moodle.xml we are able to convert
     *
     * The method returns list of {@link convert_path} instances.
     * For each path returned, the corresponding conversion method must be
     * defined.
     *
     * Note that the path /MOODLE_BACKUP/COURSE/MODULES/MOD/PAGEMENU does not
     * actually exist in the file. The last element with the module name was
     * appended by the moodle1_converter class.
     *
     * @return array of {@link convert_path} instances
     */
    public function get_paths() {
        return array(
            new convert_path(
                'pages', '/MOODLE_BACKUP/COURSE/FORMATDATA/PAGES',
                array(
                )
            ),
            new convert_path(
                'format_page', '/MOODLE_BACKUP/COURSE/FORMATDATA/PAGES/PAGE',
                 array(
                    'newfields' => array(
                        'displaymenu' => 1,
                        'cmid' => 0,
                        'lockingcmid' => 0,
                        'lockingscore' => 0,
                        'datefrom' => 0,
                        'dateto' => 0,
                        'relativeweek' => 0,
                        'globaltemplate' => 0,
                    ),
                )
            ),
            new convert_path(
                'format_pageitems', '/MOODLE_BACKUP/COURSE/FORMATDATA/PAGES/PAGE/ITEMS',
                array(
                )
            ),
            new convert_path(
                'format_pageitem', '/MOODLE_BACKUP/COURSE/FORMATDATA/PAGES/PAGE/ITEMS/ITEM',
                array(
                )
            ),
       );
    }
    
    public function after_execute(){
        $fragment = $this->get_buffer_content();
        // here we need open course/course.xml and inject xml in prepared placeholder
        $coursefile = $this->converter->get_workdir_path().'/course/course.xml';
        if (file_exists($coursefile)) {
            $filebuffer = implode("\n", file($coursefile));
            $filebuffer = str_replace('<plugin_format_page_course></plugin_format_page_course>', $fragment, $filebuffer);
            // write back appended content
            $COURSEXML = fopen($coursefile, 'w');
            fputs($COURSEXML, $filebuffer);
            fclose($COURSEXML);
        }
    }

    public function on_pages_start(){
        $this->open_xml_writer($withprologue = false);
        $this->xmlwriter->begin_tag('plugin_format_page_course');
        $this->xmlwriter->begin_tag('pages');
    }

    public function on_pages_end(){
        $this->xmlwriter->end_tag('pages');
        $this->xmlwriter->end_tag('plugin_format_page_course');
        
        // now we have all format related info. Close buffer input definitively
        $this->close_xml_writer();
    }
    
    public function on_format_page_start(){
        $this->xmlwriter->begin_tag('page');
    }

    public function on_format_page_end(){
        $this->xmlwriter->end_tag('page');
    }

    public function process_format_page($data){
        global $currentpageid;
        
        $currentpageid = $data['id'];

        // convert display data encoding
        $data['displaymenu'] = ($data['display'] & self::M19_DISP_MENU) ? 1 : 0 ;
        $data['display'] = ($data['display'] & self::M19_DISP_PUBLISH) ? self::FORMAT_PAGE_DISP_PUBLISHED : self::FORMAT_PAGE_DISP_HIDDEN ;

        foreach($data as $field => $value){
            $this->xmlwriter->full_tag($field, $value);
        }
    }

    public function on_format_pageitems_start(){
        $this->xmlwriter->begin_tag('items');
    }

    public function on_format_pageitems_end(){
        $this->xmlwriter->end_tag('items');
    }
    
    public function process_format_pageitem($data){
        global $currentpage;
        
        // ensure we have pageid in item
        $data['pageid'] = $currentpageid;
        
        $this->write_xml('item', $data);
        
        $this->fix_block_instance_page_subcontext($data['blockname'], $data['id'], $currentpageid);
        
        // we are an activity module with no page_module to work with.
        if (!$data['blockinstance']){
            switch($data['position']){
                case 'l':
                    $region = 'side-pre';
                    break;
                case 'c':
                    $region = 'main';
                    break;
                case 'r':
                    $region = 'side-post';
                    break;
            }
            $this->build_missing_page_module_blockinstance($cmid, $pageid, $region, $data['sortorder']);
        }
    }
    
    /**
    * This converter reopens block descriptor files to remap the suppattern
    */
    protected function fix_block_instance_page_subcontext($blockname, $instanceid, $pageid){
        $blockxmlfile = $this->converter->get_workdir_path().'/course/blocks/'.$blockname.'_'.$instanceid.'/block.xml';
        $blockxml = implode("\n", file($blockxmlfile));
        $blockxml = preg_replace('//s', '<subpagepattern>page-'.$pageid.'</subpagepattern>', $blockxml);
        $BLOCKXMLOUT = fopen($blockxmlfile, 'w');
        fputs($BLOCKXMLOUT, $blockxml);
        fclose($BLOCKXMLOUT);
    }

    /**
    * this will build a fake storage for a page_module block attached to a moodle activity.
    * this will use a magic sequence of fake block instances with real low chances to collide
    * on existing block instance ids elsewhere in the backup.
    */
    protected function build_missing_page_module_blockinstance($cmid, $pageid, $region, $weight){
        static $magic_blockid = 9990000;

        $newblockpath = $this->converter->get_workdir_path().'/course/blocks/'.$blockname.'_'.$magic_blockid;
        
        $parentcontextid = $this->converter->get_stash('original_course_contextid');

        if (!is_dir($newblockpath)) {
            mkdir($newblockpath, 02777);
        }

        $newblockfile = $newblockpath.'/block.xml';
        $blockxmlwriter = new xml_writer(new file_xml_output($newblockfile), new moodle1_xml_transformer());
        $blockxmlwriter->start();
        $blockxmlwriter->begin_tag('block', array('id' => $magic_blockid, 'context' => $magic_blockid));
        $blockxmlwriter->full_tag('blockname', 'page_module');
        $blockxmlwriter->full_tag('parentcontextid', $parentcontextid);
        $blockxmlwriter->full_tag('showinsubcontexts', 0);
        $blockxmlwriter->full_tag('pagetypepattern', 'course-view-*');
        $blockxmlwriter->full_tag('subpagepattern', 'page-'.$pageid);
        $blockxmlwriter->full_tag('defaultregion', 'side-pre');
        $blockxmlwriter->full_tag('defaultweight', 1);
        $blockxmlwriter->full_tag('ocnfigdata', 'Tjs=');

        $blockxmlwriter->begin_tag('block_positions');
        $blockxmlwriter->begin_tag('block_position', array('id' => 1));
        $blockxmlwriter->full_tag('contextid', $parentcontextid); // This is the same context is than block owner.
        $blockxmlwriter->full_tag('pagetype', 'course-view');
        $blockxmlwriter->full_tag('subpage', '');
        $blockxmlwriter->full_tag('visible', 1);
        $blockxmlwriter->full_tag('region', $region);
        $blockxmlwriter->full_tag('weight', $weight);
        $blockxmlwriter->end_tag('block_position');
        $blockxmlwriter->end_tag('block_positions');

        $blockxmlwriter->end_tag('block');
        $blockxmlwriter->stop();

        $newinforeffile = $newblockpath.'/inforef.xml';
        $blockxmlwriter = new xml_writer(new file_xml_output($newinforeffile), new moodle1_xml_transformer());
        $blockxmlwriter->start();
        $blockxmlwriter->begin_tag('inforef');
        $blockxmlwriter->begin_tag('fileref');
        $blockxmlwriter->end_tag('fileref');
        $blockxmlwriter->end_tag('inforef');
        $blockxmlwriter->stop();

        $newrolesfile = $newblockpath.'/roles.xml';
        $blockxmlwriter = new xml_writer(new file_xml_output($newrolesfile), new moodle1_xml_transformer());
        $blockxmlwriter->start();
        $blockxmlwriter->begin_tag('roles');
        $blockxmlwriter->full_tag('role_overrides', '');
        $blockxmlwriter->full_tag('role_assignments', '');
        $blockxmlwriter->end_tag('roles');
        $blockxmlwriter->stop();

        $magic_blockid++;
    }
}