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
 * This file contains extension of the import_ui classes that adds a methods
 * in order to customise the backup UI for the purposes of
 * import to a single section.
 *
 * @package   local_importtosection
 * @copyright 2020 corvus albus
 * @copyright based on work by Sam Hemelryk 2010
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Importtosection UI class
 *
 * @package   local_importtosection
 * @copyright 2020 corvus albus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class importtosection_ui extends import_ui {

     /**
     * Rewrites files in the backupdir to import to the specified sectionnumber of the targetsection
     *
     *
     * @param string $backupdir the (temporary) dir, where the backup is stored or inflated to
     * @param int $sectionnumber the number of the targetsection
     * @return bool|string true on success or errormessage on failure
     */

    public function rewrite_backup_to_section ($backupdir, $sectionnumber) {

        // modify moodle_backup.xml
        $filename = $backupdir . "/moodle_backup.xml";
        if (!file_exists($filename)) {
            return "Unable to open " . $filename . "!";
        }
        $str = file_get_contents($filename);
        // search for sectionids and sectiondirectories in the archive
        if (preg_match_all("#<section>.*?<sectionid>(\d+).*?<directory>([^<]+).*?</section>#s", $str, $section_blocks)) {
            $section_id = $section_blocks[1][0];
            $section_dir = $section_blocks[2][0];
        }

        // erase hints to other sections but the first
        if (count($section_blocks[1]) > 1) {
            foreach (array_slice($section_blocks[1],1) as $secid) {
                $patterns[] = '#\h*<section>\n\h*<sectionid>'.$secid.'.*?</section>\n#s';
                $patterns[] = '#\h*<setting>\n\h*<level>section</level>\n\h*<section>section_' . $secid . '.*?</setting>\n#s';
            }
            $str = preg_replace($patterns, "", $str);
        }

        // change section title to sectionnumber
        $str = preg_replace("#(<section>.*?<title>)[^<]*#s",'${1}' . $sectionnumber, $str);

        // change sectionid for activities
        $str = preg_replace("#<sectionid>\d+</sectionid>#s", '<sectionid>' . $section_id . '</sectionid>', $str);

        // write back to moodle_backup.xml
        file_put_contents($backupdir . "/moodle_backup.xml", $str);

        // To keep the sequence of the activities/modules within the sections, it must be stored first.
        // Seems to be unnecessary. But it's better to make the backup consistent.
        foreach ($section_blocks[2] as $secdir) {
            $filename = $backupdir . "/" . $secdir . "/section.xml";
            if (!file_exists($filename)) {
                return "Unable to open " . $filename . "!";
            }
            $str = file_get_contents($filename);
            if (preg_match("#<number>(\d+)</number>#", $str, $match)) {
                $secnum = $match[1];
            }
            if (preg_match("#<sequence>([0-9,]*)</sequence>#", $str, $match)) {
                $sequences[$secnum] = $match[1];
            }
        }
        // sort the array bei sectionnumber and concat the contents. Put the sequence into the remaining section.xml later on.
        ksort($sequences);
        $sequence = "";
        foreach ($sequences as $string) {
            if (!empty($string)) {
                if (!empty($sequence)) {
                    $sequence .= ",";
                }
                $sequence .= $string;
            }
        }

        // delete the now surplus section-directories (and included files)
        foreach (array_slice($section_blocks[2],1) as $secdir) {
            foreach (glob($backupdir . "/" . $secdir . "/*.*") as $file) {
                unlink($file);
            }
            rmdir($backupdir . "/" . $secdir);
        }

        // replace sectionnumber in the remaining sections/section_xxxx/section.xml
        $filename = $backupdir . "/" . $section_dir . '/section.xml';
        if (!file_exists($filename)) {
            return 'Unable to open ' . $filename . "!";
        }
        $str = file_get_contents($filename);
        $str = preg_replace('#<number>\d+</number>#', '<number>' . $sectionnumber . '</number>', $str);
        $str = preg_replace('#<name>[^<]*</name>#', '<name>$@NULL@$</name>', $str);
        // the sequence was stored above. Put it in now.
        $str = preg_replace('#<sequence>[^<]*</sequence>#', '<sequence>' . $sequence . '</sequence>', $str);
        file_put_contents($backupdir . '/' . $section_dir . '/section.xml', $str);
        
        
        // replace sectionnumber in all activities/*/module.xml files
        foreach (glob($backupdir . "/activities/*/module.xml") as $filename) {
            if (!file_exists($filename)) {
                return "Unable to open " . $filename . "!";
            }
            $str = file_get_contents($filename);
            $strold = $str;
            if (!preg_match('#<sectionid>' . $section_id . '</sectionid>#S', $str)) {
                $str = preg_replace("#<sectionid>\d+</sectionid>#S", '<sectionid>' . $section_id . '</sectionid>', $str);
            }
            if (!preg_match('#<sectionnumber>' . $section_id . '</sectionnumber>#S', $str)) {
                $str = preg_replace("#<sectionnumber>\d+</sectionnumber>#S", '<sectionnumber>' . $sectionnumber . '</sectionnumber>', $str);
            }
            if ( $strold != $str) {
                file_put_contents($filename, $str);
            }
        }
        return true;
    }
}

