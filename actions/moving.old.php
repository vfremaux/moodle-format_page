<?php
/**
 * Prints the moving interface
 * 
 * @author Jeff Graham, Mark Nielsen
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

$moving = required_param('moving', PARAM_INT);

require_capability('format/page:managepages', $context);

$PAGE->print_tabs('manage');

if ($pages = page_get_all_pages($course->id)) {
    if (!$name = $DB->get_field('format_page', 'nameone', array('id' => $moving))) {
        print_error('errorpagebadname', 'format_page');
    }

    $a       = new stdClass;
    $a->name = format_string($name);
    $a->url  = $PAGE->url_get_full(array('action' => 'manage'));

    $table->head        = array(get_string('movingpage', 'format_page', $a));
    $table->cellpadding = '2px';
    $table->cellspacing = '0';
    $table->width       = '30%';
    $table->wrap        = array('nowrap');
    $table->id          = 'editing-table';
    $table->class       = 'pageeditingtable';
    $table->data        = array();

    $sortorder = 1;
    $data      = array();

    $data[] = page_movehere_widget($moving, 0, 0, -2, get_string('asamasterpageone', 'format_page'));

    foreach ($pages as $page) {
        if ($page->id != $moving) {
            $data   = array_merge($data, page_print_moving_hierarchy($page, $moving));
            $data[] = page_movehere_widget($moving, 0, $sortorder, -2, get_string('asamasterpageafter', 'format_page', format_string($page->nameone)));
            $sortorder++;
        }
    }
    // Convert each item in $data into a table row
    foreach ($data as $row) {
        $table->data[] = array($row);
    }
    echo html_writer::table($table);
} else {
    print_error('nopages', 'format_page');
}

/**
 * Local methods to assist with generating output
 * that is specific to this page
 *
 */

/**
 * Prints a page and recursively prints its children along
 * with move here markers.
 *
 * @param object $page Page object
 * @param int $moving ID of the page that we are moving
 * @return array
 **/
function page_print_moving_hierarchy($page, $moving) {
    global $USER, $CFG, $PAGE;

    $data = array();

    // Add the page link/name
    $data[] = page_pad_string('<a href="'.$PAGE->url_build('page', $page->id).'">'.format_string($page->nameone).'</a>', $page->depth);
    // Add move here for making the moving page a child of this one
    $data[] = page_movehere_widget($moving, $page->id, 0, $page->depth, get_string('asachildof', 'format_page', format_string($page->nameone)));

    // Process all the children
    if (!empty($page->children)) {
        $sortorder = 1;

        foreach($page->children as $child) {
            if ($moving != $child->id) {
                $data   = array_merge($data, page_print_moving_hierarchy($child, $moving));
                $data[] = page_movehere_widget($moving, $page->id, $sortorder, $page->depth, get_string('asachildof', 'format_page', format_string($page->nameone)));
                $sortorder++;
            }
        }
    }
    return $data;
}

/**
 * Creates the move here widget
 *
 * @param int $moving ID of the page that we are moving
 * @param int $moveto The parent ID or 0
 * @param int $pos Sort order position
 * @param int $depth Current depth
 * @param string $label Label to add after the widget
 * @return string
 **/
function page_movehere_widget($moving, $moveto, $pos, $depth, $label) {
    global $CFG, $COURSE;

    $pad     = str_repeat('&nbsp;&nbsp;', $depth + 2);
    $output  = "$pad<a href=\"$CFG->wwwroot/course/format/page/format.php?id=$COURSE->id&amp;action=movepage&amp;moving=$moving&amp;moveto=$moveto&amp;pos=$pos&amp;sesskey=".sesskey().'">';
    $output .= '<img src="'.$OUTPUT->pix_url('/movehere') . '" alt="'.get_string('movehere').'" style="vertical-align:bottom;" /></a> '.$label;

    return $output;
}

?>