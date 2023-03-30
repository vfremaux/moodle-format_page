// Javascript functions for Topics course format

M.course = M.course || {};

M.course.format = M.course.format || {};

/**
 * Get sections config for this format
 *
 * The section structure is:
 * <ul class="topics">
 *  <li class="section">...</li>
 *  <li class="section">...</li>
 *   ...
 * </ul>
 *
 * @return {object} section list configuration
 */
M.course.format.get_config = function() {
    return {
        container_node : 'div',
        container_class : 'format_page_content',
        section_node : 'div',
        section_class : 'section-wrapper'
    };
}

M.course.format.get_section_wrapper = function() {
    var config = M.course.format.get_config();
    if (config.section_node && config.section_class) {
        return config.section_node + '.' + config.section_class;
    }
    return null;
}
