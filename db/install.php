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

function xmldb_format_page_install() {
    global $DB;

    $DB->delete_records('format_page_pfamily');

    $mdl_format_page_pfamily = array(
        array('type' => 'block', 'shortname' => 'navigation', 'name' => '<span class="multilang" lang="en">Navigation Helpers</span><span class="multilang" lang="fr">Aide à la navigation</span>','sortorder' => '1'),
        array('type' => 'block', 'shortname' => 'summaries', 'name' => '<span class="multilang" lang="en">Summaries and information</span><span class="multilang" lang="fr">Résumés</span>','sortorder' => '3'),
        array('type' => 'block', 'shortname' => 'activity', 'name' => '<span class="multilang" lang="en">Activity accessories</span><span class="multilang" lang="fr">Accessoires d\'activité</span>','sortorder' => '2'),
        array('type' => 'block', 'shortname' => 'studenttools', 'name' => '<span class="multilang" lang="en">Course tools for students</span><span class="multilang" lang="fr">Outils de l\'étudiant</span>','sortorder' => '3'),
        array('type' => 'block', 'shortname' => 'teachertools', 'name' => '<span class="multilang" lang="en">Course tools for teachers</span><span class="multilang" lang="fr">Outils de l\'enseignant</span>','sortorder' => '3'),
        array('type' => 'block', 'shortname' => 'connectors', 'name' => '<span class="multilang" lang="en">External wrappers</span><span class="multilang" lang="fr">Connecteurs</span>','sortorder' => '4'),
        array('type' => 'mod', 'shortname' => 'social', 'name' => '<span class="multilang" lang="en">Social generics</span><span class="multilang" lang="fr">Outils sociaux</span>','sortorder' => '1'),
        array('type' => 'mod', 'shortname' => 'evaluation', 'name' => '<span class="multilang" lang="en">Evaluation tools</span><span class="multilang" lang="fr">Outils d\'évaluation</span>','sortorder' => '2'),
        array('type' => 'mod', 'shortname' => 'resources', 'name' => '<span class="multilang" lang="en">Resources and documents</span><span class="multilang" lang="fr">Ressources et documents</span>','sortorder' => '3'),
        array('type' => 'mod', 'shortname' => 'workshops', 'name' => '<span class="multilang" lang="en">Workshops</span><span class="multilang" lang="fr">Outils de production</span>','sortorder' => '4'),
        array('type' => 'block', 'shortname' => 'administration', 'name' => '<span class="multilang" lang="en">Management</span><span class="multilang" lang="fr">Gestion</span>','sortorder' => '5'),
        array('type' => 'block', 'shortname' => 'content', 'name' => '<span class="multilang" lang="en">content</span><span class="multilang" lang="fr">contentu</span>','sortorder' => '6'),
        array('type' => 'block', 'shortname' => 'tracking', 'name' => '<span class="multilang" lang="en">Completion and tracking</span><span class="multilang" lang="fr">Tracking et progresssion</span>','sortorder' => '7'),
        array('type' => 'block', 'shortname' => 'marketplace', 'name' => '<span class="multilang" lang="en">Market place and course selling</span><span class="multilang" lang="fr">Commercialisation des cours</span>','sortorder' => '8'),
    );

    foreach ($mdl_format_page_pfamily as $pf) {
        $record = (object)$pf;
        if (!empty($record)) {
            $DB->insert_record('format_page_pfamily', $record);
        }
    }

    $DB->delete_records('format_page_plugins');

    $mdl_format_page_pfamily_assigns = array(
        array('type' => 'block', 'plugin' => 'activity_modules', 'familyname' => 'navigation'),
        array('type' => 'block', 'plugin' => 'admin_bookmarks', 'familyname' => 'navigation'),
        array('type' => 'block', 'plugin' => 'badges', 'familyname' => 'studenttools'),
        array('type' => 'block', 'plugin' => 'blog_menu', 'familyname' => 'studenttools'),
        array('type' => 'block', 'plugin' => 'blog_recent', 'familyname' => 'studenttools'),
        array('type' => 'block', 'plugin' => 'blog_tags', 'familyname' => 'studenttools'),
        array('type' => 'block', 'plugin' => 'calendar_month', 'familyname' => 'studenttools'),
        array('type' => 'block', 'plugin' => 'calendar_upcoming', 'familyname' => 'summaries'),
        array('type' => 'block', 'plugin' => 'chronometer', 'familyname' => 'studenttools'),
        array('type' => 'block', 'plugin' => 'cms_navigation', 'familyname' => 'navigation'),
        array('type' => 'block', 'plugin' => 'comments', 'familyname' => 'studenttools'),
        array('type' => 'block', 'plugin' => 'community', 'familyname' => 'studenttools'),
        array('type' => 'block', 'plugin' => 'completionstatus', 'familyname' => 'tracking'),
        array('type' => 'block', 'plugin' => 'course_ascendants', 'familyname' => 'navigation'),
        array('type' => 'block', 'plugin' => 'course_descendants', 'familyname' => 'navigation'),
        array('type' => 'block', 'plugin' => 'course_list', 'familyname' => 'navigation'),
        array('type' => 'block', 'plugin' => 'course_notification', 'familyname' => 'teachertools'),
        array('type' => 'block', 'plugin' => 'course_overview', 'familyname' => 'summaries'),
        array('type' => 'block', 'plugin' => 'course_status', 'familyname' => 'teachertools'),
        array('type' => 'block', 'plugin' => 'course_summary', 'familyname' => 'summaries'),
        array('type' => 'block', 'plugin' => 'dashboard', 'familyname' => 'teachertools'),
        array('type' => 'block', 'plugin' => 'editablecontenthtml', 'familyname' => 'content'),
        array('type' => 'block', 'plugin' => 'elluminate', 'familyname' => 'connectors'),
        array('type' => 'block', 'plugin' => 'feedback', 'familyname' => 'studenttools'),
        array('type' => 'block', 'plugin' => 'glossary_random', 'familyname' => 'content'),
        array('type' => 'block', 'plugin' => 'groupspecifichtml', 'familyname' => 'content'),
        array('type' => 'block', 'plugin' => 'html', 'familyname' => 'content'),
        array('type' => 'block', 'plugin' => 'learningtimecheck', 'familyname' => 'teachertool'),
        array('type' => 'block', 'plugin' => 'mentees', 'familyname' => 'teachertools'),
        array('type' => 'block', 'plugin' => 'messages', 'familyname' => 'studenttools'),
        array('type' => 'block', 'plugin' => 'mnet_hosts', 'familyname' => 'navigation'),
        array('type' => 'block', 'plugin' => 'microsoft', 'familyname' => 'studenttools'),
        array('type' => 'block', 'plugin' => 'my_peers', 'familyname' => 'studenttools'),
        array('type' => 'block', 'plugin' => 'myprofile', 'familyname' => 'summaries'),
        array('type' => 'block', 'plugin' => 'navigation', 'familyname' => 'navigation'),
        array('type' => 'block', 'plugin' => 'news_items', 'familyname' => 'summaries'),
        array('type' => 'block', 'plugin' => 'o365_links', 'familyname' => 'connectors'),
        array('type' => 'block', 'plugin' => 'online_users', 'familyname' => 'teachertools'),
        array('type' => 'block', 'plugin' => 'onenote', 'familyname' => 'connectors'),
        array('type' => 'block', 'plugin' => 'page_tracker', 'familyname' => 'navigation'),
        array('type' => 'block', 'plugin' => 'participants', 'familyname' => 'teachertools'),
        array('type' => 'block', 'plugin' => 'private_files', 'familyname' => 'studenttools'),
        array('type' => 'block', 'plugin' => 'profileselectorhtml', 'familyname' => 'content'),
        array('type' => 'block', 'plugin' => 'profilespecifichtml', 'familyname' => 'content'),
        array('type' => 'block', 'plugin' => 'publishflow', 'familyname' => 'teachertools'),
        array('type' => 'block', 'plugin' => 'quiz_dyn_key', 'familyname' => 'teachertools'),
        array('type' => 'block', 'plugin' => 'quiz_progress', 'familyname' => 'studenttools'),
        array('type' => 'block', 'plugin' => 'quiz_results', 'familyname' => 'studenttools'),
        array('type' => 'block', 'plugin' => 'recent_activity', 'familyname' => 'summaries'),
        array('type' => 'block', 'plugin' => 'rolespecifichtml', 'familyname' => 'content'),
        array('type' => 'block', 'plugin' => 'rss_client', 'familyname' => 'content'),
        array('type' => 'block', 'plugin' => 'search', 'familyname' => 'studenttools'),
        array('type' => 'block', 'plugin' => 'search_forums', 'familyname' => 'studenttools'),
        array('type' => 'block', 'plugin' => 'section_links', 'familyname' => 'navigation'),
        array('type' => 'block', 'plugin' => 'selfcompletion', 'familyname' => 'studenttools'),
        array('type' => 'block', 'plugin' => 'sharedresources', 'familyname' => 'teachertools'),
        array('type' => 'block', 'plugin' => 'shop', 'familyname' => 'marketplace'),
        array('type' => 'block', 'plugin' => 'shop_bills', 'familyname' => 'marketplace'),
        array('type' => 'block', 'plugin' => 'shop_products', 'familyname' => 'marketplace'),
        array('type' => 'block', 'plugin' => 'shop_total', 'familyname' => 'marketplace'),
        array('type' => 'block', 'plugin' => 'site_main_menu', 'familyname' => 'navigation'),
        array('type' => 'block', 'plugin' => 'social_activities', 'familyname' => 'studenttools'),
        array('type' => 'block', 'plugin' => 'tag_flickr', 'familyname' => 'connectors'),
        array('type' => 'block', 'plugin' => 'tag_youtube', 'familyname' => 'connectors'),
        array('type' => 'block', 'plugin' => 'tags', 'familyname' => 'summaries'),
        array('type' => 'block', 'plugin' => 'teams', 'familyname' => 'studenttools'),
        array('type' => 'block', 'plugin' => 'tests', 'familyname' => 'activity'),
        array('type' => 'block', 'plugin' => 'use_stats', 'familyname' => 'tracking'),
        array('type' => 'block', 'plugin' => 'user_contact', 'familyname' => 'teachertools'),
        array('type' => 'block', 'plugin' => 'user_delegation', 'familyname' => 'administration'),
        array('type' => 'block', 'plugin' => 'user_memo', 'familyname' => 'studenttools'),
        array('type' => 'block', 'plugin' => 'user_mnet_hosts', 'familyname' => 'navigation'),
        array('type' => 'block', 'plugin' => 'userquiz_limits', 'familyname' => 'administration'),
        array('type' => 'block', 'plugin' => 'userquiz_monitor', 'familyname' => 'activity'),
        array('type' => 'block', 'plugin' => 'vmoodle', 'familyname' => 'administration'),
        array('type' => 'block', 'plugin' => 'yammer', 'familyname' => 'connectors'),
    );

    foreach ($mdl_format_page_pfamily_assigns as $pfa) {
        $record = (object)$pfa;
        if (!empty($record)) {
            $DB->insert_record('format_page_plugins', $record);
        }
    }
    return true;
}
