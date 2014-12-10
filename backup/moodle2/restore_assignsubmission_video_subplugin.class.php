<?php
/***************************************************************
*  Copyright Notice
*
*  This script is (c) 2014 Brightcookie.com Educational Technologies
*  <enquiries@brightookie.com> http://www.brightcookie.com - all rights reserved
*
*  You can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*  The GNU General Public License can be found here
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This module is based on assignsubmission_onlinetext module of Moodle http://moodle.org/
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * restore subplugin class that provides the necessary information needed to restore one assign_submission subplugin.
 *
 * @package assignsubmission_video
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_assignsubmission_video_subplugin extends restore_subplugin {

    /**
     *
     * Returns array the paths to be handled by the subplugin at assignment level
     * @return array
     */
    protected function define_submission_subplugin_structure() {

        $paths = array();

        $elename = $this->get_namefor('submission');
        $elepath = $this->get_pathfor('/submission_video'); // we used get_recommended_name() so this works
        $paths[] = new restore_path_element($elename, $elepath);

        return $paths; // And we return the interesting paths
    }

    /**
     * Processes one assignsubmission_video element
     *
     * @param mixed $data
     */
    public function process_assignsubmission_video_submission($data) {
        global $DB;

        $data = (object)$data;
        $data->assignment = $this->get_new_parentid('assign');
        $oldsubmissionid = $data->submission;
        // the mapping is set in the restore for the core assign activity. When a submission node is processed
        $data->submission = $this->get_mappingid('submission', $data->submission);

        $DB->insert_record('assignsubmission_video', $data);

        $this->add_related_files('assignsubmission_video', 'submissions_video', 'submission', null, $oldsubmissionid);
    }

}
