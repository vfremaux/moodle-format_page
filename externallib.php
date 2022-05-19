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
 * External Page format control
 *
 * Format page API allows external applications to program and change settings
 * in the tool sync engine to control its behaviour and resources that will be used
 * for synchronisation.
 *
 * @package    format_page
 * @category   external
 * @copyright  2016 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/externallib.php');

class format_page_external extends external_api {

    protected static function validate_block_parameters($configparamdefs, $inputs) {
        global $DB;

        $status = self::validate_parameters($configparamdefs, $inputs);

        switch ($inputs['blockidsource']) {
            case 'idnumber':
                if (!$DB->record_exists('format_page_items', array('idnumber' => $inputs['blockid']))) {
                    throw new moodle_exception('No block format page item with idnumber '.$inputs['blockid']);
                }
                break;

            case 'id':
                if (!$DB->record_exists('block_instances', array('id' => $inputs['blockid']))) {
                    throw new moodle_exception('No block with idnumber '.$inputs['blockid']);
                }

            default:
                throw new moodle_exception('Bad block identification source');
        }

        return $status;
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.2
     */
    public static function get_block_config_parameters() {
        return new external_function_parameters(
            array('blockidsource' => new external_value(PARAM_TEXT, 'Block ID source'),
                  'blockid' => new external_value(PARAM_TEXT, ''),
                  'configkey' => new external_value(PARAM_TEXT, '', VALUE_DEFAULT, '', true),
            )
        );
    }

    /**
     * Retrieving a configuration of a block instance
     *
     * @param string $blockidsource source for the block id. Might be idnumber or id
     * @param string $blockid blockid
     * @param string $configkey Configuration key
     * @return single value
     */
    public static function get_block_config($blockidsource, $blockid, $configkey) {
        global $DB;

        // Validate parameters.
        $params = self::validate_block_parameters(self::get_block_config_parameters(),
                array('blockidsource' => $blockidsource, 'blockid' => $blockid, 'configkey' => $configkey));

        switch ($blockidsource) {
            case 'idnumber':
                $pageitem = $DB->get_record('format_page_items', array('idnumber' => $blockid));
                $blockid = $pageitem->blockinstance;
                break;

            case 'id':
                // nothing to do.
                assert(1);
        }

        if (!$blockrec = $DB->get_record('block_instances', array('id' => $blockid))) {
            throw new moodle_exception('No block with id '.$blockid);
        }
        $blockinstance = block_instance($blockrec->blockname, $blockrec);

        if (!isset($blockinstance->config->$configkey)) {
            throw new moodle_exception('Inexistant config '.$configkey.' in block '.$blockrec->blockname);
        }

        return $blockinstance->config->$configkey;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.2
     */
    public static function get_block_config_returns() {
        return new external_value(PARAM_RAW, 'Config value');
    }

    // Set config in blocks

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.2
     */
    public static function set_block_config_parameters() {
        return new external_function_parameters(
            array('blockidsource' => new external_value(PARAM_TEXT, 'block id source'),
                  'blockid' => new external_value(PARAM_TEXT, '', VALUE_DEFAULT, 0, true),
                  'configkey' => new external_value(PARAM_TEXT, '', VALUE_DEFAULT, '', true),
                  'value' => new external_value(PARAM_RAW, '', VALUE_DEFAULT, '', true),
            )
        );
    }

    /**
     * Retrieving a configuration of a block instance
     *
     * @param string $blockidsource source of the block id
     * @param string $blockid configuration block id
     * @param string $configkey Configuration key
     * @return array
     * @since Moodle 2.2
     */
    public static function set_block_config($blockidsource, $blockid, $configkey, $value) {
        global $DB;

        // Validate parameters.
        $params = self::validate_block_parameters(self::set_block_config_parameters(),
                array('blockidsource' => $blockidsource,
                      'blockid' => $blockid,
                      'configkey' => $configkey,
                      'value' => $value));

        switch ($blockidsource) {
            case 'idnumber':
                $pageitem = $DB->get_record('format_page_items', array('idnumber' => $blockid));
                $blockid = $pageitem->blockinstance;
                break;

            case 'id':
                // nothing to do.
                assert(1);
        }

        if (!$blockrec = $DB->get_record('block_instances', array('id' => $blockid))) {
            throw new moodle_exception('No block with id '.$blockid);
        }
        $blockinstance = block_instance($blockrec->blockname, $blockrec);

        if (!isset($blockinstance->config->$configkey)) {
            throw new moodle_exception('Inexistant config '.$configkey.' in block '.$blockrec->blockname);
        }

        $blockinstance->config->$configkey = $value;
        $DB->set_field('block_instances', 'configdata', base64_encode(serialize($blockinstance->config)), array('id' => $blockid));
        $DB->set_field('block_instances', 'timemodified', time(), array('id' => $blockid));

        return true;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.2
     */
    public static function set_block_config_returns() {
        return new external_value(PARAM_BOOL, 'Change status');
    }

    // Module config ---------------------------------------------------------------.

    protected static function validate_module_parameters($configparamdefs, $inputs) {
        global $DB;

        $status = self::validate_parameters($configparamdefs, $inputs);

        switch ($inputs['moduleidsource']) {
            case 'idnumber':
                if (!$DB->record_exists('course_modules', array('idnumber' => $inputs['moduleid']))) {
                    throw new moodle_exception('Invalid cm idnumber '.$inputs['moduleid']);
                }
                break;
            case 'id':
                if (!$DB->record_exists('course_modules', array('id' => $inputs['moduleid']))) {
                    throw new moodle_exception('Invalid cm id '.$inputs['moduleid']);
                }
                break;
            default:
                throw new moodle_exception('Invalid cm id source');
        }

        return $status;
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.2
     */
    public static function get_module_config_parameters() {
        return new external_function_parameters(
            array('moduleidsource' => new external_value(PARAM_TEXT, 'Module ID source'),
                  'moduleid' => new external_value(PARAM_TEXT, ''),
                  'configkey' => new external_value(PARAM_TEXT, '', VALUE_DEFAULT, '', true),
            )
        );
    }

    /**
     * Retrieving a configuration of a module instance
     *
     * @param string $moduleidsource
     * @param string $moduleid
     * @param string $configkey
     * @return array
     */
    public static function get_module_config($moduleidsource, $moduleid, $configkey) {
        global $DB;

        // Validate parameters.
        $params = self::validate_module_parameters(self::get_module_config_parameters(),
                array('moduleidsource' => $moduleidsource,
                      'moduleid' => $moduleid,
                      'configkey' => $configkey));

        switch ($moduleidsource) {
            case 'idnumber':
                $cm = $DB->get_record('course_modules', array('idnumber' => $moduleid));
                break;
            case 'id':
                $cm = $DB->get_record('course_modules', array('id' => $moduleid));
                break;
        }

        if (!$module = $DB->get_record('modules', array('id' => $cm->module))) {
            throw new moodle_exception("Invalid module $cm->module ");
        }

        $instance = $DB->get_record($module->name, array('id' => $cm->instance));

        if (!isset($instance->$configkey)) {
            throw new moodle_exception("Inexistant config $configkey in module ");
        }

        return $instance->$configkey;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.2
     */
    public static function get_module_config_returns() {
        return new external_value(PARAM_RAW, 'Config value');
    }

    // Set config in module -----------------------------------------------.

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.2
     */
    public static function set_module_config_parameters() {
        return new external_function_parameters(
            array('moduleidsource' => new external_value(PARAM_TEXT, 'block id source'),
                  'moduleid' => new external_value(PARAM_TEXT, ''),
                  'configkey' => new external_value(PARAM_TEXT, ''),
                  'value' => new external_value(PARAM_RAW, ''),
            )
        );
    }

    /**
     * Retrieving a configuration of a block instance
     *
     * @param string $blockidsource source of the module id
     * @param string $blockid configuration block id
     * @param string $configkey Configuration key
     * @return array
     * @since Moodle 2.2
     */
    public static function set_module_config($moduleidsource, $moduleid, $configkey, $value) {
        global $DB;

        // Validate parameters.
        $params = self::validate_module_parameters(self::set_module_config_parameters(),
                array('moduleidsource' => $moduleidsource,
                      'moduleid' => $blockid,
                      'configkey' => $configkey,
                      'value' => $value));

        switch ($moduleidsource) {
            case 'idnumber':
                $cm = $DB->get_record('course_modules', array('idnumber' => $moduleid));
                break;
            case 'id':
                $cm = $DB->get_record('course_modules', array('id' => $moduleid));
                break;
        }

        if (!$module = $DB->get_record('modules', array('id' => $cm->module))) {
            throw new moodle_exception("Unkown module type with id $cm->module");
        }
        if (!$instance = $DB->get_record($module->name, array('id' => $cm->instance))) {
            throw new moodle_exception("Module $module->name instance not found with id $cm->instance");
        }

        if (!isset($instance->$configkey)) {
            throw new moodle_exception('Inexistant config in module ');
        }

        $instance->$configkey = $value;
        $DB->update_record($module->name, $instance);

        return true;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 2.2
     */
    public static function set_module_config_returns() {
        return new external_value(PARAM_BOOL, 'Change status');
    }

}
