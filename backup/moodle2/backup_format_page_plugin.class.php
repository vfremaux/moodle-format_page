<?php
/**
 * Flexpage
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://opensource.org/licenses/gpl-3.0.html.
 *
 * @copyright Copyright (c) 2009 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @package format_flexpage
 * @author Mark Nielsen
 */

/**
 * Flexpage backup plugin
 *
 * @author Valery Fremaux (valery.Fremaux@gmail.com)
 * @package format_page
 */
class backup_format_page_plugin extends backup_format_plugin {
    /**
     * Returns the format information to attach to course element
     */
    protected function define_course_plugin_structure() {

        // Define the virtual plugin element with the condition to fulfill
        $plugin = $this->get_plugin_element(null, '/course/format', 'page');

        // Create one standard named plugin element (the visible container)
        $pluginwrapper = new backup_nested_element($this->get_recommended_name());

        // Connect the visible container ASAP
        $plugin->add_child($pluginwrapper);

        // Now create the format specific structures
        $pages = new backup_nested_element('pages');
        $page  = new backup_nested_element('page', array('id'), array(
            'nameone',
            'nametwo',
            'display',
            'displaymenu',
            'prefleftwidth',
            'prefcenterwidth',
            'prefrightwidth',
            'parent',
            'sortorder',
            'template',
            'showbuttons',
            'cmid',
            'lockingcmid',
            'lockingscore'
        ));

        $discussion  = new backup_nested_element('discussion', array('id'), array(
        	'pageid',
        	'discussion',
        	'lastmodified',
        	'lastwriteuser'
        ));

        $grants  = new backup_nested_element('grants');
        $access  = new backup_nested_element('access', array('id'), array(
        	'pageid',
        	'policy',
        	'arg1int',
        	'arg2int',
        	'arg3text'
        ));

        /** discussion user tracks not saved 
        $discussionusers  = new backup_nested_element('discussion_users', array('id'), array(
        );
		*/

        $items = new backup_nested_element('items');
        $item  = new backup_nested_element('item', array('id'), array('pageid', 'cmid', 'blockinstance' /* , 'position', 'sortorder', 'visible' deprecated thus not saved */));

        // Now the format specific tree
        $pluginwrapper->add_child($pages);
        $pages->add_child($page);

        $page->add_child($items);
        $items->add_child($item);
        
        $page->add_child($discussion);

        $page->add_child($grants);
        $grants->add_child($access);

        // Set source to populate the data
        $page->set_source_table('format_page', array('courseid' => backup::VAR_COURSEID));
        $item->set_source_table('format_page_items', array('pageid' => backup::VAR_PARENTID));
        $discussion->set_source_table('format_page_discussion', array('pageid' => backup::VAR_PARENTID));
        $access->set_source_table('format_page_access', array('pageid' => backup::VAR_PARENTID));

        // Annotate ids
        $discussion->annotate_ids('user', 'lastwriteuser');

        return $plugin;
    }
}