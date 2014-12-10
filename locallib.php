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
 * This file contains the definition for the library class for video submission plugin
 *
 * This class provides all the functionality for the new assign module.
 *
 * @package assignsubmission_video
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
/**
 * File area for online text submission assignment
 */
define('ASSIGNSUBMISSION_video_FILEAREA', 'submissions_video');

/**
 * library class for video submission plugin extending submission plugin base class
 *
 * @package assignsubmission_video
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_submission_video extends assign_submission_plugin {

    /**
     * Get the name of the online text submission plugin
     * @return string
     */
    public function get_name() {
        return get_string('video', 'assignsubmission_video');
    }


   /**
    * Get video submission information from the database
    *
    * @param  int $submissionid
    * @return mixed
    */
    private function get_video_submission($submissionid) {
        global $DB;

        return $DB->get_record('assignsubmission_video', array('submission'=>$submissionid));
    }

    /**
     * Add form elements for settings
     * 
     * @param mixed $submission can be null
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return true if elements were added to the form
     */
    public function get_form_elements($submission, MoodleQuickForm $mform, stdClass $data) {
        $elements = array();

        $editoroptions = $this->get_edit_options();
        $submissionid = $submission ? $submission->id : 0;
		//assignment_{$MUSER->id}_{$aid}_video.mp4
		//print_r($submission);
		//die();

        if (!isset($data->video)) {
            $data->video = '';
        }
        if (!isset($data->videoformat)) {
            $data->videoformat = editors_get_preferred_format();
        }
		$vdata="";
		$data2="";
        if ($submission) {
            $videosubmission = $this->get_video_submission($submission->id);
            if ($videosubmission) {
				$vdata=$videosubmission->video;
				$spos=strpos($vdata,'<!-- video -->');
				
				if ($spos !== false) {
					$data2='<iframe src="/mod/assign/submission/video/player/?file=assignment_'.($submission->userid).'_'.($submission->assignment).'_video.mp4" style="display:block; width:480px; height:360px;" frameborder="0" scrolling="no"></iframe>';
					$vdata=str_replace('<!-- video -->',"",$vdata);
				}
				$data->video = $vdata;
				
                
                $data->videoformat = $videosubmission->onlineformat;
            }

        }
		//$data->video="test 1234";
		
        $data = file_prepare_standard_editor($data, 'video', $editoroptions, $this->assignment->get_context(), 'assignsubmission_video', ASSIGNSUBMISSION_video_FILEAREA, $submissionid);
		$mform->addElement('html', '<b><i>Video description:</i></b>');
        $mform->addElement('editor', 'video_editor', html_writer::tag('span', $this->get_name(),
            array('class' => 'accesshide')), null, $editoroptions);
		$mform->addElement('html', '<b><i>Video file</i></b><i>(You can edit/upload this video in mobile app)</i>'.$data2);
		if($data2!="") $mform->addElement('hidden', 'videolink', "<!-- video -->");
		else $mform->addElement('hidden', 'videolink', "");
		
        return true;
    }

    /**
     * Editor format options
     *
     * @return array
     */
    private function get_edit_options() {
         $editoroptions = array(
           'noclean' => false,
           'maxfiles' => EDITOR_UNLIMITED_FILES,
           'maxbytes' => $this->assignment->get_course()->maxbytes,
           'context' => $this->assignment->get_context(),
           'return_types' => FILE_INTERNAL | FILE_EXTERNAL
        );
        return $editoroptions;
    }

     /**
      * Save data to the database and trigger plagiarism plugin, if enabled, to scan the uploaded content via events trigger
      *
      * @param stdClass $submission
      * @param stdClass $data
      * @return bool
      */
     public function save(stdClass $submission, stdClass $data) {
        global $USER, $DB;

        $editoroptions = $this->get_edit_options();

        $data = file_postupdate_standard_editor($data, 'video', $editoroptions, $this->assignment->get_context(), 'assignsubmission_video', ASSIGNSUBMISSION_video_FILEAREA, $submission->id);

        $videosubmission = $this->get_video_submission($submission->id);

        $fs = get_file_storage();
        $files = $fs->get_area_files($this->assignment->get_context()->id, 'assignsubmission_video', ASSIGNSUBMISSION_video_FILEAREA, $submission->id, "id", false);
        // Let Moodle know that an assessable content was uploaded (eg for plagiarism detection)
        $eventdata = new stdClass();
        $eventdata->modulename = 'assign';
        $eventdata->cmid = $this->assignment->get_course_module()->id;
        $eventdata->itemid = $submission->id;
        $eventdata->courseid = $this->assignment->get_course()->id;
        $eventdata->userid = $USER->id;
        $eventdata->content = trim(format_text($data->video, $data->video_editor['format'], array('context'=>$this->assignment->get_context())));
        if ($files) {
            $eventdata->pathnamehashes = array_keys($files);
        }
        events_trigger('assessable_content_uploaded', $eventdata);
		$data->video=$data->video.$data->videolink;
        if ($videosubmission) {

            $videosubmission->video = $data->video;
            $videosubmission->onlineformat = $data->video_editor['format'];


            return $DB->update_record('assignsubmission_video', $videosubmission);
        } else {

            $videosubmission = new stdClass();
            $videosubmission->video = $data->video;
            $videosubmission->onlineformat = $data->video_editor['format'];

            $videosubmission->submission = $submission->id;
            $videosubmission->assignment = $this->assignment->get_instance()->id;
            return $DB->insert_record('assignsubmission_video', $videosubmission) > 0;
        }


    }

    /**
     * Return a list of the text fields that can be imported/exported by this plugin
     *
     * @return array An array of field names and descriptions. (name=>description, ...)
     */
    public function get_editor_fields() {
        return array('video' => get_string('pluginname', 'assignsubmission_comments'));
    }

    /**
     * Get the saved text content from the editor
     *
     * @param string $name
     * @param int $submissionid
     * @return string
     */
    public function get_editor_text($name, $submissionid) {
        if ($name == 'video') {
            $videosubmission = $this->get_video_submission($submissionid);
            if ($videosubmission) {
                return $videosubmission->video;
            }
        }

        return '';
    }

    /**
     * Get the content format for the editor
     *
     * @param string $name
     * @param int $submissionid
     * @return int
     */
    public function get_editor_format($name, $submissionid) {
        if ($name == 'video') {
            $videosubmission = $this->get_video_submission($submissionid);
            if ($videosubmission) {
                return $videosubmission->onlineformat;
            }
		 
        }


         return 0;
    }


     /**
      * Display video word count in the submission status table
      *
      * @param stdClass $submission
      * @param bool $showviewlink - If the summary has been truncated set this to true
      * @return string
      */
    public function view_summary(stdClass $submission, & $showviewlink) {
        global $CFG;

        $videosubmission = $this->get_video_submission($submission->id);
        // always show the view link
        $showviewlink = true;

        if ($videosubmission) {
            $text = $this->assignment->render_editor_content(ASSIGNSUBMISSION_video_FILEAREA,
                                                             $videosubmission->submission,
                                                             $this->get_type(),
                                                             'video',
                                                             'assignsubmission_video');

            $shorttext = shorten_text($text, 140);
            $plagiarismlinks = '';
            if (!empty($CFG->enableplagiarism)) {
                require_once($CFG->libdir . '/plagiarismlib.php');
                $plagiarismlinks .= plagiarism_get_links(array('userid' => $submission->userid,
                    'content' => trim(format_text($videosubmission->video, $videosubmission->onlineformat, array('context'=>$this->assignment->get_context()))),
                    'cmid' => $this->assignment->get_course_module()->id,
                    'course' => $this->assignment->get_course()->id,
                    'assignment' => $submission->assignment));
            }
            if ($text != $shorttext) {
                return $shorttext . $plagiarismlinks . get_string('numwords', 'assignsubmission_video', count_words($text));
            } else {
                return $shorttext . $plagiarismlinks;
            }
        }
        return '';
    }

    /**
     * Produce a list of files suitable for export that represent this submission
     *
     * @param stdClass $submission - For this is the submission data
     * @param stdClass $user - This is the user record for this submission
     * @return array - return an array of files indexed by filename
     */
    public function get_files(stdClass $submission, stdClass $user) {
        global $DB;
        $files = array();
        $videosubmission = $this->get_video_submission($submission->id);
        if ($videosubmission) {
            $finaltext = $this->assignment->download_rewrite_pluginfile_urls($videosubmission->video, $user, $this);
            $submissioncontent = "<html><body>". format_text($finaltext, $videosubmission->onlineformat, array('context'=>$this->assignment->get_context())). "</body></html>";

            $files[get_string('videofilename', 'assignsubmission_video')] = array($submissioncontent);

            $fs = get_file_storage();

            $fsfiles = $fs->get_area_files($this->assignment->get_context()->id, 'assignsubmission_video', ASSIGNSUBMISSION_video_FILEAREA, $submission->id, "timemodified", false);

            foreach ($fsfiles as $file) {
                $files[$file->get_filename()] = $file;
            }
        }

        return $files;
    }

    /**
     * Display the saved text content from the editor in the view table
     *
     * @param stdClass $submission
     * @return string
     */
    public function view(stdClass $submission) {
        $result = '';

        $videosubmission = $this->get_video_submission($submission->id);


        if ($videosubmission) {

            // render for portfolio API
            //$result .= $this->assignment->render_editor_content(ASSIGNSUBMISSION_video_FILEAREA, $videosubmission->submission, $this->get_type(), 'video', 'assignsubmission_video');
			$vplayer='<iframe src="/mod/assign/submission/video/player/?file=assignment_'.($submission->userid).'_'.($submission->assignment).'_video.mp4" style="display:block; width:480px; height:360px;" frameborder="0" scrolling="no"></iframe>';
			$result=str_replace('<!-- video -->',$vplayer,$videosubmission->video);

        }
		//print_r($videosubmission->video);
		//die();
		
		return $result;
       
    }

     /**
     * Return true if this plugin can upgrade an old Moodle 2.2 assignment of this type and version.
     *
     * @param string $type old assignment subtype
     * @param int $version old assignment version
     * @return bool True if upgrade is possible
     */
    public function can_upgrade($type, $version) {
        if ($type == 'online' && $version >= 2011112900) {
            return true;
        }
        return false;
    }


    /**
     * Upgrade the settings from the old assignment to the new plugin based one
     *
     * @param context $oldcontext - the database for the old assignment context
     * @param stdClass $oldassignment - the database for the old assignment instance
     * @param string $log record log events here
     * @return bool Was it a success?
     */
    public function upgrade_settings(context $oldcontext, stdClass $oldassignment, & $log) {
        // first upgrade settings (nothing to do)
        return true;
    }

    /**
     * Upgrade the submission from the old assignment to the new one
     *
     * @param context $oldcontext - the database for the old assignment context
     * @param stdClass $oldassignment The data record for the old assignment
     * @param stdClass $oldsubmission The data record for the old submission
     * @param stdClass $submission The data record for the new submission
     * @param string $log Record upgrade messages in the log
     * @return bool true or false - false will trigger a rollback
     */
    public function upgrade(context $oldcontext, stdClass $oldassignment, stdClass $oldsubmission, stdClass $submission, & $log) {
        global $DB;

        $videosubmission = new stdClass();
        $videosubmission->video = $oldsubmission->data1;
        $videosubmission->onlineformat = $oldsubmission->data2;

        $videosubmission->submission = $submission->id;
        $videosubmission->assignment = $this->assignment->get_instance()->id;

        if ($videosubmission->video === null) {
            $videosubmission->video = '';
        }

        if ($videosubmission->onlineformat === null) {
            $videosubmission->onlineformat = editors_get_preferred_format();
        }

        if (!$DB->insert_record('assignsubmission_video', $videosubmission) > 0) {
            $log .= get_string('couldnotconvertsubmission', 'mod_assign', $submission->userid);
            return false;
        }

        // now copy the area files
        $this->assignment->copy_area_files_for_upgrade($oldcontext->id,
                                                        'mod_assignment',
                                                        'submission',
                                                        $oldsubmission->id,
                                                        // New file area
                                                        $this->assignment->get_context()->id,
                                                        'assignsubmission_video',
                                                        ASSIGNSUBMISSION_video_FILEAREA,
                                                        $submission->id);
        return true;
    }

    /**
     * Formatting for log info
     *
     * @param stdClass $submission The new submission
     * @return string
     */
    public function format_for_log(stdClass $submission) {
        // format the info for each submission plugin add_to_log
        $videosubmission = $this->get_video_submission($submission->id);
        $videologinfo = '';
        $text = format_text($videosubmission->video,
                            $videosubmission->onlineformat,
                            array('context'=>$this->assignment->get_context()));
        $videologinfo .= get_string('numwordsforlog', 'assignsubmission_video', count_words($text));

        return $videologinfo;
    }

    /**
     * The assignment has been deleted - cleanup
     *
     * @return bool
     */
    public function delete_instance() {
        global $DB;
        // will throw exception on failure
        $DB->delete_records('assignsubmission_video', array('assignment'=>$this->assignment->get_instance()->id));

        return true;
    }

    /**
     * No text is set for this plugin
     *
     * @param stdClass $submission
     * @return bool
     */
    public function is_empty(stdClass $submission) {
        $videosubmission = $this->get_video_submission($submission->id);

        return empty($videosubmission->video);
    }

    /**
     * Get file areas returns a list of areas this plugin stores files
     * @return array - An array of fileareas (keys) and descriptions (values)
     */
    public function get_file_areas() {
        return array(ASSIGNSUBMISSION_video_FILEAREA=>$this->get_name());
    }
    
    /**
     * Get the default setting for file submission plugin
     *
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform) {
        global $CFG, $COURSE;

        $defaultmaxvideosubmitlength = $this->get_config('maxvideosubmitlength');
        //$defaultmaxvideosizembs = $this->get_config('maxvideosizembs');

        $settings = array();
        if($CFG->s3maxvideolength) $maxlength=$CFG->s3maxvideolength;
        else $maxlength=300;
        $option1 = array();
        for ($i = 1; $i <= 20; $i++) {
            $val=round($i*($maxlength/20));
			$option1["{$val}"] =$val ;
        }
        
       /* if($CFG->s3maxupload) $maxsize=$CFG->s3maxupload;
        else $maxsize=20;
        $option2 = array();
        for ($i = 1; $i <= 20; $i++) {
			$val=round($i*($maxsize/20),1);
            $option2["{$val}"] = $val;
        }
		
		*/
		
        $name = get_string('maxvideosubmitlength', 'assignsubmission_video');
        $mform->addElement('select', 'assignsubmission_video_maxvideosubmitlength', $name, $option1);
        /*$mform->addHelpButton('assignsubmission_video_maxvideosubmitlength',
                              'maxfilessubmission',
                              'assignsubmission_file'); */
        $mform->setDefault('assignsubmission_video_maxvideosubmitlength', $defaultmaxvideosubmitlength);
        $mform->disabledIf('assignsubmission_video_maxvideosubmitlength', 'assignsubmission_video_enabled', 'notchecked');

		$mform->addElement('html', '<div style="margin-left: 160px; border: 1px solid #999; padding: 5px; border-radius: 5px; background-color: #FFFFCC;">'.get_string('maxvideosubmitlength_desciption', 'assignsubmission_video').'</div>');
       

        //$name = get_string('maxvideosizembs', 'assignsubmission_video');
        //$mform->addElement('select', 'assignsubmission_video_maxvideosizembs', $name, $option2);
        /*$mform->addHelpButton('assignsubmission_video_maxvideosizembs',
                              'maximumsubmissionsize',
                              'assignsubmission_file');*/
        //$mform->setDefault('assignsubmission_video_maxvideosizembs', $defaultmaxvideosizembs);
        /*$mform->disabledIf('assignsubmission_video_maxvideosizembs',
                           'assignsubmission_video_enabled',
                           'notchecked');
		*/
    }

    /**
     * Save the settings for file submission plugin
     *
     * @param stdClass $data
     * @return bool
     */
    public function save_settings(stdClass $data) {
        $this->set_config('maxvideosubmitlength', $data->assignsubmission_video_maxvideosubmitlength);
        //$this->set_config('maxvideosizembs', $data->assignsubmission_video_maxvideosizembs);
        return true;
    }

}


