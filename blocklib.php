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

require_once($CFG->dirroot.'/course/format/page/page.class.php');

/**
 * This script proposes an enhanced version of the class block_manager
 * that allows sufficant overrides to handle blocks in multipages formats
 * using format_page_items as additional information to build regions.
 *
 */
class page_enabled_block_manager extends block_manager {

    /**
     * Add a block to the current page, or related pages. The block is added to
     * context $this->page->contextid. If $pagetypepattern $subpagepattern
     * The bloc is recorded also in format_page_items
     *
     * @param string $blockname The type of block to add.
     * @param string $region the block region on this page to add the block to.
     * @param integer $weight determines the order where this block appears in the region.
     * @param boolean $showinsubcontexts whether this block appears in subcontexts, or just the current context.
     * @param string|null $pagetypepattern which page types this block should appear on. Defaults to just the current page type.
     * @param string|null $subpagepattern which subpage this block should appear on. NULL = any (the default), otherwise only the specified subpage.
     */
    public function add_block($blockname, $region, $weight, $showinsubcontexts, $pagetypepattern = null, $subpagepattern = null) {
        global $DB, $COURSE;

        /*
         * Allow invisible blocks because this is used when adding default page blocks, which
         * might include invisible ones if the user makes some default blocks invisible
         */
        $this->check_known_block_type($blockname, true);
        $this->check_region_is_known($region);

        if (empty($pagetypepattern)) {
            $pagetypepattern = $this->page->pagetype;
        }

        $blockinstance = new stdClass;
        $blockinstance->blockname = $blockname;
        $blockinstance->parentcontextid = $this->page->context->id;
        $blockinstance->showinsubcontexts = !empty($showinsubcontexts);
        $blockinstance->pagetypepattern = $pagetypepattern;
        $blockinstance->subpagepattern = $subpagepattern;
        $blockinstance->defaultregion = $region;
        $blockinstance->defaultweight = $weight;
        $blockinstance->configdata = '';
        $blockinstance->id = $DB->insert_record('block_instances', $blockinstance);

        // Ensure the block context is created.
        context_block::instance($blockinstance->id);

        // If the new instance was created, allow it to do additional setup.
        if ($block = block_instance($blockname, $blockinstance)) {
            $block->instance_create();
        }

        // Inserts into format_page_items on curent page.
        if ($COURSE->format == 'page') {
            // This is a silly collision case with module "page".
            if (is_array(@$_POST['page'])) {
                $page = course_page::get_current_page($COURSE->id);
                $pageid = $page->id;
            } else {
                if (!$pageid = optional_param('page', 0, PARAM_INT)) {
                    if (!$pageid = @$COURSE->pageid) {
                        $page = course_page::get_current_page($COURSE->id);
                        $pageid = $page->id;
                    }
                }
            }

            $pageitem = new StdClass();
            $pageitem->pageid = $pageid;
            $pageitem->blockinstance = $blockinstance->id;
            $pageitem->visible = 1; // This is not used any more.
            $pageitem->sortorder = 1; // This is not used any more.
            $DB->insert_record('format_page_items', $pageitem);
        }

        // We need this for extra processing after block creation.
        return $blockinstance;
    }

    /**
     * Knows how to turn around the theme cascade.
     */
    public function add_block_at_end_of_page_region($blockname, $pageid = 0) {
        global $COURSE, $CFG;

        if ($COURSE->format != 'page') {
            throw new coding_exception('This block add variant should not be used in non page format');
        }

        $this->page->initialise_theme_and_output();

        // Forces region existance anyway in page format, whatever page we are working in.
        $this->regions['side-post'] = 1;
        $this->regions['main'] = 1;
        $this->regions['side-pre'] = 1;

        if (!empty($CFG->format_page_default_region)) {
            $defaulregion = $CFG->format_page_default_region;
        } else {
            $defaulregion = 'main';
        }

        // We need recalculate weight for region by our own.
        $weight = $this->compute_weight_in_page($defaulregion, $pageid);

        // Special case.
        // We force course view as the actual page context is a in-module context.
        $pagetypepattern = 'course-view-*';

        return $this->add_block($blockname, $defaulregion, $weight, false, $pagetypepattern, 'page-'.$pageid);
    }

    /**
     * Computes the blocks weight
     * @param string $defaultregion
     * @param int $pageid
     */
    public function compute_weight_in_page($defaultregion, $pageid) {
        global $DB;

        // Positionned.
        $posweight = 0 + $DB->get_field('block_positions', 'MAX(weight)', array('subpage' => 'page-'.$pageid, 'region' => $defaultregion));

        $weight = 0 + $DB->get_field('block_instances', 'MAX(defaultweight)', array('subpagepattern' => 'page-'.$pageid));

        $weight = (max($posweight, $weight));

        return 0 + $weight + 1;
    }

    /**
     * This method actually loads the blocks for our page from the database.
     * Loading blocs needs to complete standard queries with page_items mapping.
     *
     * @param boolean|null $includeinvisible
     *      null (default) - load hidden blocks if $this->page->user_is_editing();
     *      true - load hidden blocks.
     *      false - don't load hidden blocks.
     */
    public function load_blocks($includeinvisible = null) {
        global $DB, $CFG, $COURSE, $PAGE;

        if (!is_null($this->birecordsbyregion)) {
            // Already done.
            return;
        }

        if ($CFG->version < 2009050619) {
            // Upgrade/install not complete. Don't try too show any blocks.
            $this->birecordsbyregion = array();
            return;
        }

        // Ensure we have been initialised.
        if (is_null($this->defaultregion)) {
            $this->page->initialise_theme_and_output();
            // If there are still no block regions, then there are no blocks on this page.
            if (empty($this->regions)) {
                $this->birecordsbyregion = array();
                return;
            }
        }

        // Check if we need to load normal blocks.
        if ($this->fakeblocksonly) {
            $this->birecordsbyregion = $this->prepare_per_region_arrays();
            return;
        }

        if (is_null($includeinvisible)) {
            $includeinvisible = $this->page->user_is_editing();
        }
        if ($includeinvisible) {
            $visiblecheck = '';
        } else {
            $visiblecheck = '(bp.visible = 1 OR bp.visible IS NULL) AND';
        }

        $context = $this->page->context;
        $contexttest = 'bi.parentcontextid = :contextid2';
        $parentcontextparams = array();
        $parentcontextids = get_parent_contexts($context);
        if ($parentcontextids && ($COURSE->format != 'page' || $PAGE->pagelayout == 'format_page')) {
            list($parentcontexttest, $parentcontextparams) = $DB->get_in_or_equal($parentcontextids, SQL_PARAMS_NAMED, 'parentcontext');
            $contexttest = "($contexttest OR (bi.showinsubcontexts = 1 AND bi.parentcontextid $parentcontexttest)) AND";
        } else {
            $contexttest .= ' AND';
        }

        $pagetypepatterns = matching_page_type_patterns($this->page->pagetype);
        list($pagetypepatterntest, $pagetypepatternparams) =
                $DB->get_in_or_equal($pagetypepatterns, SQL_PARAMS_NAMED, 'pagetypepatterntest');

        list($ccselect, $ccjoin) = context_instance_preload_sql('bi.id', CONTEXT_BLOCK, 'ctx');

        // Computes an extra page related clause.
        $pageclause = '';
        $pagejoin = '';
        if ($COURSE->format == 'page') {
            if ($PAGE->pagelayout == 'format_page') {
                // Special weird case : for module "page" : page is an array, but is only present on non page format pagetypes...
                if (is_array(@$_POST['page'])) {
                    $page = course_page::get_current_page($COURSE->id);
                    $pageclause = " fpi.pageid = $page->id AND ";
                    $this->page->set_subpage('page-'.$page->id);
                } else {
                    if ($pageid = optional_param('page', 0, PARAM_INT)) {
                        $pageclause = " fpi.pageid = $pageid AND ";
                        $this->page->set_subpage('page-'.$pageid);
                    } else {
                        if ($page = course_page::get_current_page($COURSE->id)) {
                            $pageclause = " fpi.pageid = $page->id AND ";
                            $this->page->set_subpage('page-'.$page->id);
                        } else {
                            // No pages standard for no blocks !!
                            $pageclause = " fpi.pageid = 0 AND ";
                        }
                    }
                }
                $pagejoin = "JOIN {format_page_items} fpi ON bi.id = fpi.blockinstance ";
            } else {
                // For other non paged layouts used in context of format page, get only navigation and settings.
                $pagejoin = "LEFT JOIN {format_page_items} fpi ON bi.id = fpi.blockinstance ";
                $pageclause = " (bi.showinsubcontexts = 1 OR bi.blockname IN ('navigation', 'settings')) AND ";
            }
        }

        $params = array(
            'subpage1' => $this->page->subpage,
            'subpage2' => $this->page->subpage,
            'contextid1' => $context->id,
            'contextid2' => $context->id,
            'pagetype' => $this->page->pagetype,
        );
        if ($this->page->subpage === '') {
            $params['subpage1'] = '';
            $params['subpage2'] = '';
        }
        $sql = "SELECT DISTINCT
                    bi.id,
                    bp.id AS blockpositionid,
                    bi.blockname,
                    bi.parentcontextid,
                    bi.showinsubcontexts,
                    bi.pagetypepattern,
                    bi.subpagepattern,
                    bi.defaultregion,
                    bi.defaultweight,
                    COALESCE(bp.visible, 1) AS visible,
                    COALESCE(bp.region, bi.defaultregion) AS region,
                    COALESCE(bp.weight, bi.defaultweight) AS weight,
                    bi.configdata
                    $ccselect
                FROM
                    {block_instances} bi
                $pagejoin
                JOIN
                    {block} b
                ON
                    bi.blockname = b.name
                LEFT JOIN
                    {block_positions} bp
                ON
                    bp.blockinstanceid = bi.id AND
                    bp.contextid = :contextid1 AND
                    bp.pagetype = :pagetype AND
                    bp.subpage = :subpage1
                    $ccjoin
                WHERE
                    $pageclause
                    $contexttest
                    bi.pagetypepattern $pagetypepatterntest AND
                    (bi.subpagepattern IS NULL OR bi.subpagepattern = :subpage2) AND
                    $visiblecheck
                    b.visible = 1
                ORDER BY
                    COALESCE(bp.region, bi.defaultregion),
                    COALESCE(bp.weight, bi.defaultweight),
                    bi.id";
        $blockinstances = $DB->get_records_sql($sql, $params + $parentcontextparams + $pagetypepatternparams);

        $this->birecordsbyregion = $this->prepare_per_region_arrays();

        $unknown = array();
        $inpage = array();

        foreach ($blockinstances as $bi) {
            context_instance_preload($bi);
            if (!$instance = block_instance($bi->blockname)) {
                continue;
            }
            if ($instance->instance_allow_multiple() || !array_key_exists($bi->blockname, $inpage)) {
                $inpage[$bi->blockname] = 1;
                if ($this->is_known_region($bi->region)) {
                    $this->birecordsbyregion[$bi->region][] = $bi;
                } else {
                    $unknown[] = $bi;
                }
            }
        }

        // We are NOT editing a block.
        if (!isset($_GET['bui_editid'])) {

            /*
             * Pages don't necessarily have a defaultregion. The  one time this can
             * happen is when there are no theme block regions, but the script itself
             * has a block region in the main content area.
             */
            if (!empty($this->defaultregion)) {
                $this->birecordsbyregion[$this->defaultregion] = array_merge($this->birecordsbyregion[$this->defaultregion], $unknown);
            }
        }
    }

    /**
     * Handle deleting a block.
     * @return boolean true if anything was done. False if not.
     */
    public function process_url_delete() {
        global $COURSE, $DB;

        $blockid = optional_param('bui_deleteid', null, PARAM_INTEGER);
        if (!$blockid) {
            return false;
        }

        require_sesskey();

        $block = $this->page->blocks->find_instance($blockid);

        if (!$block->user_can_edit() || !$this->page->user_can_edit_blocks() || !$block->user_can_addto($this->page)) {
            throw new moodle_exception('nopermissions', '', $this->page->url->out(), get_string('deleteablock'));
        }

        blocks_delete_instance($block->instance);

        if ($COURSE->format == 'page') {
            if (!$pageid = optional_param('page', 0, PARAM_INT)) {
                $page = course_page::get_current_page($COURSE->id);
                $pageid = $page->id;
            }
            $DB->delete_records('format_page_items', array('pageid' => $pageid, 'blockinstance' => $blockid));
        }

        // If the page URL was a guess, it will contain the bui_... param, so we must make sure it is not there.
        $this->page->ensure_param_not_in_url('bui_deleteid');

        return true;
    }

    /**
     * Ensure that there is some content within the given region
     * This override avoids printing the add_block_ui in columns
     * as already provided by course top editing window
     *
     * @param string $region The name of the region to check
     */
    public function ensure_content_created($region, $output) {
        global $COURSE;

        $this->ensure_instances_exist($region);
        if (!array_key_exists($region, $this->visibleblockcontent)) {
            $contents = array();
            if (array_key_exists($region, $this->extracontent)) {
                $contents = $this->extracontent[$region];
            }
            $contents = array_merge($contents, $this->create_block_contents($this->blockinstances[$region], $output, $region));
            if ($COURSE->format != 'page') {
                if ($region == $this->defaultregion) {
                    $addblockui = block_add_block_ui($this->page, $output);
                    if ($addblockui) {
                        $contents[] = $addblockui;
                    }
                }
            }
            $this->visibleblockcontent[$region] = $contents;
        }
    }

    /**
     * This reworked version do know how to redirect editing link in special case of a 
     * page_module block linked to a course module. Page module instance is NOT editable
     * in direct;
     *
     * Get the appropriate list of editing icons for a block. This is used
     * to set {@link block_contents::$controls} in {@link block_base::get_contents_for_output()}.
     *
     * @param $output The core_renderer to use when generating the output. (Need to get icon paths.)
     * @return an array in the format for {@link block_contents::$controls}
     */
    public function edit_controls($block) {
        global $CFG, $DB;

        if (!isset($CFG->undeletableblocktypes) || (!is_array($CFG->undeletableblocktypes) && !is_string($CFG->undeletableblocktypes))) {
            $undeletableblocktypes = array('navigation', 'settings');
        } else if (is_string($CFG->undeletableblocktypes)) {
            $undeletableblocktypes = explode(',', $CFG->undeletableblocktypes);
        } else {
            $undeletableblocktypes = $CFG->undeletableblocktypes;
        }

        $controls = array();
        $actionurl = $this->page->url->out(false, array('sesskey' => sesskey()));

        if ($this->page->user_can_edit_blocks()) {
            // Move icon.
            $controls[] = array('url' => $actionurl . '&bui_moveid=' . $block->instance->id,
                    'icon' => 't/move', 'caption' => get_string('move'),
                    'class' => '');
        }

        if ($block->instance->blockname != 'page_module') {
            if ($this->page->user_can_edit_blocks() || $block->user_can_edit()) {
                // Edit config icon - always show - needed for positioning UI.
                $controls[] = array('url' => $actionurl . '&bui_editid=' . $block->instance->id,
                        'icon' => 't/edit', 'caption' => get_string('configuration'),
                        'class' => '');
            }
        } else {
            $configdata = unserialize(base64_decode($block->instance->configdata));
            $baseurl = new moodle_url('/course/mod.php', array('sesskey' => sesskey()));
            $controls[] = array(
                            'url' => new moodle_url($baseurl, array('update' => $configdata->cmid)),
                            'icon' => 't/edit',
                            'caption' => get_string('update'),
                            'class' => '');
        }

        if ($this->page->user_can_edit_blocks() && $block->user_can_edit() && $block->user_can_addto($this->page)) {
            if (!in_array($block->instance->blockname, $undeletableblocktypes)
                    || !in_array($block->instance->pagetypepattern, array('*', 'site-index'))
                    || $block->instance->parentcontextid != SITEID) {
                // Delete icon.
                $controls[] = array('url' => $actionurl . '&bui_deleteid=' . $block->instance->id,
                        'icon' => 't/delete', 'caption' => get_string('delete'),
                        'class' => '');
            }
        }

        if ($this->page->user_can_edit_blocks() && $block->instance_can_be_hidden()) {
            // Show/hide icon.
            if ($block->instance->visible) {
                $controls[] = array('url' => $actionurl . '&bui_hideid=' . $block->instance->id,
                        'icon' => 't/hide', 'caption' => get_string('hide'),
                        'class' => '');
            } else {
                $controls[] = array('url' => $actionurl . '&bui_showid=' . $block->instance->id,
                        'icon' => 't/show', 'caption' => get_string('show'),
                        'class' => '');
            }
        }

        // Assign roles icon.
        if (has_capability('moodle/role:assign', $block->context)) {
            /*
             * TODO: please note it is sloppy to pass urls through page parameters!!
             *      it is shortened because some web servers (e.g. IIS by default) give
             *      a 'security' error if you try to pass a full URL as a GET parameter in another URL.
             */
            $return = $this->page->url->out(false);
            $return = str_replace($CFG->wwwroot . '/', '', $return);

            $controls[] = array('url' => $CFG->wwwroot . '/' . $CFG->admin .
                    '/roles/assign.php?contextid=' . $block->context->id . '&returnurl=' . urlencode($return),
                    'icon' => 'i/roles', 'caption' => get_string('assignroles', 'role'),
                    'class' => '');
        }

        return $controls;
    }

    /**
     * The list of block types that may be added to this page.
     *
     * @return array block name => record from block table.
     */
    public function get_addable_blocks() {
        $this->check_is_loaded();

        if (!is_null($this->addableblocks)) {
            return $this->addableblocks;
        }

        // Lazy load.
        $this->addableblocks = array();

        $allblocks = blocks_get_record();
        if (empty($allblocks)) {
            return $this->addableblocks;
        }

        $unaddableblocks = self::get_undeletable_block_types();
        $pageformat = $this->page->pagetype;
        foreach ($allblocks as $block) {
            if (!$bi = block_instance($block->name)) {
                continue;
            }
            if ($block->name == 'page_module') {
                // Page_module is a technical block not for user's explicit use.
                continue;
            }
            if ($block->visible /* && !in_array($block->name, $unaddableblocks) */ &&
                    ($bi->instance_allow_multiple() || !$this->is_block_present($block->name)) &&
                    blocks_name_allowed_in_format($block->name, $pageformat) &&
                    $bi->user_can_addto($this->page)) {
                $this->addableblocks[$block->name] = $block;
            }
        }

        return $this->addableblocks;
    }

    /**
     *
     * For debug purpose.
     */
    public function print_raw_blocks($level = 2) {
        print_object_nr($this->birecordsbyregion, $level);
    }
}