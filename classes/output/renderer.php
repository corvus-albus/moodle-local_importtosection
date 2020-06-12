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
 * This file contains backup and restore output renderers
 *
 * @package   core_backup
 * @copyright 2010 Sam Hemelryk
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
 * $renderer = $PAGE->get_renderer('core', 'backup');
 * ?>
 *
 * @package   core_backup
 * @copyright 2010 Sam Hemelryk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_importtosection_core_backup_renderer extends core_backup_renderer {

     /**
     * creates html-code for the section-selection-page
     *
     * @param moodle_url $nexturl the url to open next
     * @param stdClass $course course object
     * @return bool|string true on success or errormessage on failure
     */

    public function target_section_selector (moodle_url $nexturl, stdClass $course) {

        $sections = get_fast_modinfo($course)->get_section_info_all();
        
        $html  = html_writer::start_tag('div', array('class' => 'import-course-selector backup-restore'));
        $html .= html_writer::start_tag('form', array('method' => 'post', 'action' => $nexturl->out_omit_querystring()));
        foreach ($nexturl->params() as $key => $value) {
            $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => $key, 'value' => $value));
        }
        $html .= html_writer::start_tag('div', array('class' => 'ics-existing-course backup-section'));
        //~ $html .= html_writer::tag('h2', get_string('selecttargetsection', 'local_importtosection'), array('class' => 'header'));
        $html .= $this->output->heading(get_string('selecttargetsection', 'local_importtosection'), 2, array('class' => 'header'));
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
        //~ $html .= html_writer::start_tag('div', array('class' => 'detail-pair'));
        //~ $html .= html_writer::tag('label', '', array('class' => 'detail-pair-label', 'for' => 'detail-pair-value-'));
        //~ $html .= html_writer::tag('div', html_writer::empty_tag('input', $attrs), array('class' => 'detail-pair-value pl-2', 'name' => 'detail-pair-value-'));
        //~ $html .= html_writer::end_tag('div');

        $html .= html_writer::end_tag('div');
        $html .= html_writer::end_tag('div');
        $html .= html_writer::end_tag('form');
        $html .= html_writer::end_tag('div');

        return $html;
    }
}

