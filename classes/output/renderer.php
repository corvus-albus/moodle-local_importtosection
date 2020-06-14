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
 * Renderer for local_importtosection.
 *
 * This file contains backup and restore output renderers
 *
 * @package   local_importtosection
 * @copyright corvus albus
 * @copyright based on work Sam Hemelryk 2010
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/backup/moodle2/backup_plan_builder.class.php');
require_once($CFG->dirroot . '/backup/util/ui/renderer.php');

/**
 * The primary renderer for the backup.
 *
 * Can be retrieved with the following code:
 * <?php
 * $renderer = $PAGE->get_renderer('local_importtosection','core_backup');
 * ?>
 *
 * @package   local_importtosection
 * @copyright corvus albus
 * @copyright based on work Sam Hemelryk 2010
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_importtosection_core_backup_renderer extends core_backup_renderer {

    /**
     * Creates html-code for the section-selection-page.
     *
     * Adapted backup/util/ui/renderer.php -> import_course_selector to fit here.
     *
     * @param moodle_url $nexturl the url to open next
     * @param stdClass $course course object
     * @return bool|string true on success or errormessage on failure
     */
    public function local_importtosection_target_section_selector (moodle_url $nexturl, stdClass $course) {

        $sections = get_fast_modinfo($course)->get_section_info_all();

        $html  = html_writer::start_tag('div', array('class' => 'import-course-selector backup-restore'));
        $html .= html_writer::start_tag('form', array('method' => 'post', 'action' => $nexturl->out_omit_querystring()));
        foreach ($nexturl->params() as $key => $value) {
            $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => $key, 'value' => $value));
        }
        $html .= html_writer::start_tag('div', array('class' => 'ics-existing-course backup-section'));
        $html .= $this->output->heading(get_string('selecttargetsection', 'local_importtosection'), 2, array('class' => 'header'));

        // Adapted from backup/util/ui/renderer.php -> render_import_course_search.
        $html .= html_writer::tag('div', get_string('numberweeks') . ' ' . count($sections), array('class' => 'ics-totalresults'));
        $html .= html_writer::start_tag('div', array('class' => 'ics-results'));

        $table = new html_table();
        $table->head = array('', get_string('sectionname'));
        $table->data = array();
        foreach ($sections as $section) {
            $row = new html_table_row();
            $row->attributes['class'] = 'ics-course';
            $row->cells = array(
                html_writer::empty_tag('input', array('type' => 'radio', 'name' => 'targetsection', 'value' => $section->section)),
                format_string(get_section_name($course, $section))
            );
            $table->data[] = $row;
        }
        $html .= html_writer::table($table);
        $html .= html_writer::end_tag('div');

        $attrs = array('type' => 'submit', 'value' => get_string('continue'), 'class' => 'btn btn-primary');
        $html .= html_writer::start_tag('div', array('class' => 'mt-3'));
        $html .= $this->backup_detail_pair('', html_writer::empty_tag('input', $attrs));
        $html .= html_writer::end_tag('div');
        $html .= html_writer::end_tag('div');
        $html .= html_writer::end_tag('form');
        $html .= html_writer::end_tag('div');

        return $html;
    }
}

