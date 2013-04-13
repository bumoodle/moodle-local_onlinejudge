<?php

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
//                      Online Judge for Moodle                          //
//        https://github.com/hit-moodle/moodle-local_onlinejudge         //
//                                                                       //
// Copyright (C) 2009 onwards  Sun Zhigang  http://sunner.cn             //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 3 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/**
 * online judge assignment type for online judge 2
 *
 * @package   local_onlinejudge
 * @copyright 2011 Sun Zhigang (http://sunner.cn)
 * @author    Sun Zhigang
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/assignment/type/upload/assignment.class.php');
require_once($CFG->libdir.'/filelib.php');
require_once($CFG->libdir.'/questionlib.php'); //for get_grade_options()
require_once($CFG->dirroot.'/local/onlinejudge/judgelib.php');

/**
 * Extends the upload assignment class
 *
 * @author Arkaitz Garro, Sunner Sun
 */
class assignment_onlinejudge extends assignment_upload {

    var $onlinejudge;

    function assignment_onlinejudge($cmid='staticonly', $assignment=NULL, $cm=NULL, $course=NULL) {
        global $DB;

        parent::assignment_upload($cmid, $assignment, $cm, $course);
        $this->type = 'onlinejudge';

        if (isset($this->assignment->id)) {
            $this->onlinejudge = $DB->get_record('assignment_oj', array('assignment' => $this->assignment->id), '*', MUST_EXIST);
        }
    }

    /**
     * Print the form for this assignment type
     *
     * @param $mform object Allready existant form
     */
    function setup_elements(&$mform ) {
        global $CFG, $COURSE, $DB;

        // Some code are copied from parent::setup_elements(). Keep sync please.

        $ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));

        $choices = get_max_upload_sizes($CFG->maxbytes, $COURSE->maxbytes);
        $choices[0] = get_string('courseuploadlimit') . ' ('.display_size($COURSE->maxbytes).')';
        $mform->addElement('select', 'maxbytes', get_string('maximumfilesize', 'assignment_onlinejudge'), $choices);
        $mform->setDefault('maxbytes', $CFG->assignment_maxbytes);

        $mform->addElement('select', 'resubmit', get_string('allowdeleting', 'assignment'), $ynoptions);
        $mform->addHelpButton('resubmit', 'allowdeleting', 'assignment');
        $mform->setDefault('resubmit', 1);

        $options = array();
        for($i = 1; $i <= 20; $i++) {
            $options[$i] = $i;
        }
        $mform->addElement('select', 'var1', get_string('allowmaxfiles', 'assignment'), $options);
        $mform->addHelpButton('var1', 'allowmaxfiles', 'assignment');
        $mform->setDefault('var1', 1);

        $mform->addElement('select', 'var2', get_string('allownotes', 'assignment'), $ynoptions);
        $mform->addHelpButton('var2', 'allownotes', 'assignment');
        $mform->setDefault('var2', 0);

        $mform->addElement('select', 'var3', get_string('hideintro', 'assignment'), $ynoptions);
        $mform->addHelpButton('var3', 'hideintro', 'assignment');
        $mform->setDefault('var3', 0);

        $mform->addElement('select', 'emailteachers', get_string('emailteachers', 'assignment'), $ynoptions);
        $mform->addHelpButton('emailteachers', 'emailteachers', 'assignment');
        $mform->setDefault('emailteachers', 0);

        // Get existing onlinejudge settings
        $update = optional_param('update', 0, PARAM_INT);
        if (!empty($update)) {
            $cm = $DB->get_record('course_modules', array('id' => $update), '*', MUST_EXIST);
            $onlinejudge = $DB->get_record('assignment_oj', array('assignment' => $cm->instance));
        }

        // Programming languages
        unset($choices);
        $choices = onlinejudge_get_languages();
        $mform->addElement('select', 'language', get_string('assignmentlangs', 'assignment_onlinejudge'), $choices);
        $mform->setDefault('language', isset($onlinejudge) ? $onlinejudge->language : get_config('local_onlinejudge', 'defaultlanguage'));

        // Presentation error grade ratio
        unset($choices);
        $choices = get_grade_options()->gradeoptions; // Steal from question lib
        $mform->addElement('select', 'ratiope', get_string('ratiope', 'assignment_onlinejudge'), $choices);
        $mform->addHelpButton('ratiope', 'ratiope', 'assignment_onlinejudge');
        $mform->setDefault('ratiope', isset($onlinejudge) ? $onlinejudge->ratiope : 0);
        $mform->setAdvanced('ratiope');

        // Max. CPU time
        unset($choices);
        $choices = $this->get_max_cpu_times();
        $mform->addElement('select', 'cpulimit', get_string('cpulimit', 'assignment_onlinejudge'), $choices);
        $mform->setDefault('cpulimit', isset($onlinejudge) ? $onlinejudge->cpulimit : 1);

        // Max. memory usage
        unset($choices);
        $choices = $this->get_max_memory_usages();
        $mform->addElement('select', 'memlimit', get_string('memlimit', 'assignment_onlinejudge'), $choices);
        $mform->setDefault('memlimit', isset($onlinejudge) ? $onlinejudge->memlimit : 1048576);

        // Compile only?
        $mform->addElement('select', 'compileonly', get_string('compileonly', 'assignment_onlinejudge'), $ynoptions);
        $mform->addHelpButton('compileonly', 'compileonly', 'assignment_onlinejudge');
        $mform->setDefault('compileonly', isset($onlinejudge) ? $onlinejudge->compileonly : 0);
        $mform->setAdvanced('compileonly');

        //ideone.com
        $mform->addElement('text', 'ideoneuser', get_string('ideoneuser', 'assignment_onlinejudge'), array('size' => 20));
        $mform->addHelpButton('ideoneuser', 'ideoneuser', 'assignment_onlinejudge');
        $mform->setType('ideoneuser', PARAM_ALPHANUMEXT);
        $mform->setDefault('ideoneuser', isset($onlinejudge) ? $onlinejudge->ideoneuser : '');
        $mform->addElement('password', 'ideonepass', get_string('ideonepass', 'assignment_onlinejudge'), array('size' => 20));
        $mform->addHelpButton('ideonepass', 'ideonepass', 'assignment_onlinejudge');
        $mform->setDefault('ideonepass', isset($onlinejudge) ? $onlinejudge->ideonepass : '');

        $course_context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
        plagiarism_get_form_elements_module($mform, $course_context);
    }

    /**
     * Any extra validation checks needed for the settings
     * form for this assignment type
     *
     * See lib/formslib.php, 'validation' function for details
     */
    function form_validation($data, $files) {
        $errors = array();
        if (substr($data['language'], -6) == 'ideone') {
            // ideone.com do not support multi-files
            // TODO: do not hardcode ideone here. judge should has support_multifile() function
            if ($data['var1'] > 1) {
                $errors['var1'] = get_string('onefileonlyideone', 'local_onlinejudge');
            }

            if (empty($data['ideoneuser'])) {
                $errors['ideoneuser'] = get_string('ideoneuserrequired', 'local_onlinejudge');
            }
            if (empty($data['ideonepass'])) {
                $errors['ideonepass'] = get_string('ideoneuserrequired', 'local_onlinejudge');
            } else if (!empty($data['ideoneuser'])) { // test username and password
                // creating soap client
                $client = new SoapClient("http://ideone.com/api/1/service.wsdl");
                // calling test function
                $testArray = $client->testFunction($data['ideoneuser'], $data['ideonepass']);
                if ($testArray['error'] == 'AUTH_ERROR') {
                    $errors['ideoneuser'] = $errors['ideonepass'] = get_string('ideoneautherror', 'local_onlinejudge');
                }
            }

        }
        return $errors;
    }

    /**
     * Create a new onlinejudge type assignment activity
     *
     * @param object $assignment The data from the form
     * @return int The id of the assignment
     */
    function add_instance($assignment) {
        global $DB;

        // Add assignment instance
        $assignment->id = parent::add_instance($assignment);

        if ($assignment->id) {
            $onlinejudge = $assignment;
            $onlinejudge->assignment = $onlinejudge->id;
            $DB->insert_record('assignment_oj', $onlinejudge);
        }

        return $assignment->id;
    }

    /**
     * Updates a program assignment activity
     *
     * @param object $assignment The data from the form
     * @return int The assignment id
     */
    function update_instance($assignment) {
        global $DB;

        // Add assignment instance
        $returnid = parent::update_instance($assignment);

        if ($returnid) {
            $onlinejudge = $assignment;
            $old_onlinejudge = $DB->get_record('assignment_oj', array('assignment' => $assignment->id));
            if ($old_onlinejudge) {
                $onlinejudge->id = $old_onlinejudge->id;
                $DB->update_record('assignment_oj', $onlinejudge);
            }
        }

        return $returnid;
    }

    /**
     * Deletes a program assignment activity
     *
     * Deletes all database records, files and calendar events for this assignment.
     *
     * @param object $assignment The assignment to be deleted
     * @return boolean False indicates error
     */
    function delete_instance($assignment) {
        global $CFG, $DB;

        // delete onlinejudge submissions
        $submissions = $DB->get_records('assignment_submissions', array('assignment' => $assignment->id));
        foreach ($submissions as $submission) {
            if (!$DB->delete_records('assignment_oj_submissions', array('submission' => $submission->id)))
                return false;
        }

        // delete testcases
        // parent will delete all files in this context
        if (!$DB->delete_records('assignment_oj_testcases', array('assignment' => $assignment->id))) {
            return false;
        }

        // delete onlinejudge settings
        if (!$DB->delete_records('assignment_oj', array('assignment' => $assignment->id))) {
            return false;
        }

        // inform judgelib to delete related tasks
        $cm = get_coursemodule_from_instance('assignment', $assignment->id);
        if (!onlinejudge_delete_coursemodule($cm->id)) {
            return false;
        }

        $result = parent::delete_instance($assignment);

        return $result;
    }

    /**
     * Get testcases data of current assignment.
     *
     * @return An array of testcases objects. All testcase files are read into memory
     */
    function get_testcases() {
        global $CFG, $DB;

        $records = $DB->get_records('assignment_oj_testcases', array('assignment' => $this->assignment->id), 'sortorder ASC');
        $tests = array();

        foreach ($records as $record) {
            if ($record->usefile) {
                $fs = get_file_storage();

                if ($files = $fs->get_area_files($this->context->id, 'mod_assignment', 'onlinejudge_input', $record->id)) {
                    $file = array_pop($files);
                    $record->input = $file->get_content();
                }
                if ($files = $fs->get_area_files($this->context->id, 'mod_assignment', 'onlinejudge_output', $record->id)) {
                    $file = array_pop($files);
                    $record->output = $file->get_content();
                }
            }
            $tests[] = $record;
        }

        return $tests;
    }

    /**
     * Rejudge all submissions
     */
    function rejudge_all() {
        global $DB;

        $submissions = $DB->get_records('assignment_submissions', array('assignment' => $this->assignment->id));
        foreach ($submissions as $submission) {
            $this->request_judge($submission);
        }
    }

    /**
     * Display the assignment intro
     *
     */
    function view_intro() {
        global $OUTPUT;

        parent::view_intro();

        $this->view_judge_info();
    }

    /**
     * Print a link to student submitted file.
     *
     * @param int $userid User Id
     * @param boolean $return Return the link or print it directly
     */
    function print_student_answer($userid, $return = false) {
        $output = parent::print_student_answer($userid, true);

        $submission = $this->get_submission($userid);

        $results = $this->get_onlinejudge_result($submission);

        // replace draft status with onlinejudge status
        $pattern = '/(<div class="box files">).*(<div )/';
        $statusstyle = $results->status == ONLINEJUDGE_STATUS_ACCEPTED ? 'notifysuccess' : 'notifyproblem';
        $statustext = html_writer::tag('span', get_string('status'.$results->status, 'local_onlinejudge'), array('class' => $statusstyle));
        $replacement = '$1'.$statustext.'$2';
        $output = preg_replace($pattern, $replacement, $output, 1);

        // TODO: Syntax Highlight source code link

        return $output; // Always return since parent do so too
    }

    /**
     * Produces a list of links to the files uploaded by a user
     *
     * @param $userid int optional id of the user. If 0 then $USER->id is used.
     * @param $return boolean optional defaults to false. If true the list is returned rather than printed
     * @return string optional
     */
    function print_user_files($userid=0, $return=false) {
        $output = parent::print_user_files($userid, true);

        // TODO: Syntax Highlight source code link

        $output .= $this->view_summary($userid);

        if ($return) {
            return $output;
        }
        echo $output;
    }

    function submissions($mode) {
        $forcejudge = optional_param('forcejudge', FALSE, PARAM_TEXT);
        if ($forcejudge) {
            $user = required_param('userid', PARAM_INT);
            $this->request_judge($this->get_submission($user));

            $offset = required_param('offset', PARAM_INT);
            $id = required_param('id', PARAM_INT);
            redirect('submissions.php?id='.$id.'&userid='. $user . '&mode=single&offset='.$offset);
        }
        parent::submissions($mode);
    }

    /**
     * Forked from upload. Don't forget to keep sync
     */
    function view_upload_form() {
        global $CFG, $USER, $OUTPUT;

        $submission = $this->get_submission($USER->id);

        $tests = $this->get_testcases();
        if (empty($tests)) {
            echo $OUTPUT->heading(get_string('notestcases','assignment_onlinejudge'), 3);
        } else if ($this->can_upload_file($submission)) {
            $fs = get_file_storage();
            // edit files in another page
            if ($submission) {
                if ($files = $fs->get_area_files($this->context->id, 'mod_assignment', 'submission', $submission->id, "timemodified", false)) {
                    $str = get_string('editthesefiles', 'assignment');
                } else {
                    $str = get_string('uploadfiles', 'assignment');
                }
            } else {
                $str = get_string('uploadfiles', 'assignment');
            }
            echo $OUTPUT->single_button(new moodle_url('/mod/assignment/type/onlinejudge/upload.php', array('contextid'=>$this->context->id, 'userid'=>$USER->id)), $str, 'get');
        }
    }

    /**
     * Forked from upload. Don't forget to keep sync
     */
    function upload_file($mform, $options) {
        global $CFG, $USER, $DB, $OUTPUT;

        $returnurl  = new moodle_url('/mod/assignment/view.php', array('id'=>$this->cm->id));
        $submission = $this->get_submission($USER->id);

        if (!$this->can_upload_file($submission)) {
            $this->view_header(get_string('upload'));
            echo $OUTPUT->notification(get_string('uploaderror', 'assignment'));
            echo $OUTPUT->continue_button($returnurl);
            $this->view_footer();
            die;
        }

        if ($formdata = $mform->get_data()) {
            $fs = get_file_storage();
            $submission = $this->get_submission($USER->id, true); //create new submission if needed
            $fs->delete_area_files($this->context->id, 'mod_assignment', 'submission', $submission->id);
            $formdata = file_postupdate_standard_filemanager($formdata, 'files', $options, $this->context, 'mod_assignment', 'submission', $submission->id);
            $updates = new stdClass();
            $updates->id = $submission->id;
            $updates->numfiles = count($fs->get_area_files($this->context->id, 'mod_assignment', 'submission', $submission->id, 'sortorder', false));
            $updates->timemodified = time();
            $DB->update_record('assignment_submissions', $updates);
            add_to_log($this->course->id, 'assignment', 'upload',
                    'view.php?a='.$this->assignment->id, $this->assignment->id, $this->cm->id);
            $this->update_grade($submission);
            if (!$this->drafts_tracked()) {
                $this->email_teachers($submission);
            }

            $this->request_judge($submission);  // Added by onlinejudge

            // send files to event system
            $files = $fs->get_area_files($this->context->id, 'mod_assignment', 'submission', $submission->id);
            // Let Moodle know that assessable files were  uploaded (eg for plagiarism detection)
            $eventdata = new stdClass();
            $eventdata->modulename   = 'assignment';
            $eventdata->cmid         = $this->cm->id;
            $eventdata->itemid       = $submission->id;
            $eventdata->courseid     = $this->course->id;
            $eventdata->userid       = $USER->id;
            if ($files) {
                $eventdata->files        = $files;
            }
            events_trigger('assessable_file_uploaded', $eventdata);
            $returnurl  = new moodle_url('/mod/assignment/view.php', array('id'=>$this->cm->id));
            redirect($returnurl);
        }

        $this->view_header(get_string('upload'));
        echo $OUTPUT->notification(get_string('uploaderror', 'assignment'));
        echo $OUTPUT->continue_button($returnurl);
        $this->view_footer();
        die;
    }

    /**
     * Display judge info about the assignment
     */
    function view_judge_info() {
        global $OUTPUT;

        echo $OUTPUT->heading(get_string('typeonlinejudge', 'assignment_onlinejudge'), 3);
        echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');

        $table = new html_table();
        $table->id = 'assignment_onlinejudge_information';
        $table->attributes['class'] = 'generalbox boxaligncenter';
        $table->align = array ('right', 'left');
        $table->size = array('20%', '');
        $table->width = '100%';

        // Language
        $item_name = get_string('assignmentlangs','assignment_onlinejudge').':';
        $item = onlinejudge_get_language_name($this->onlinejudge->language);
        $table->data[] = array($item_name, $item);

        // Compiler
        if ($compiler_info = onlinejudge_get_compiler_info($this->onlinejudge->language)) {
            $item_name = get_string('compiler','assignment_onlinejudge').':';
            $table->data[] = array($item_name, $compiler_info);
        }

        // Limits
        $item_name = get_string('memlimit','assignment_onlinejudge').':';
        $item = display_size($this->onlinejudge->memlimit);
        $table->data[] = array($item_name, $item);
        $item_name = get_string('cpulimit','assignment_onlinejudge').':';
        $item = $this->onlinejudge->cpulimit.' '.get_string('sec');
        $table->data[] = array($item_name, $item);

        echo html_writer::table($table);

        echo $OUTPUT->box_end();
    }

    function generate_status_row($submission, $results) {

        global $OUTPUT, $PAGE;

        $item_name = get_string('status', 'assignment_onlinejudge').$OUTPUT->help_icon('status', 'assignment_onlinejudge').':';
        $item = get_string('notavailable');

        if (isset($results->status)) {

            $itemstyle = $submission->grade == $this->assignment->grade ? 'notifysuccess' : 'notifyproblem';
            $item = html_writer::start_tag('span',  array('class' => $itemstyle));
            $item .= get_string('status'.$results->status, 'local_onlinejudge') . ' ('.$submission->grade . '/' . $this->assignment->grade . ')';
            $item .= html_writer::end_tag('span');


            // Show forcejudge button in submissions.php page only
            if (strstr($PAGE->url, '/mod/assignment/submissions.php') and has_capability('mod/assignment:grade', $this->context)) {
                $item .= '<input type="submit" name="forcejudge" value="'.get_string('forcejudge', 'assignment_onlinejudge').'" />';
            }
        }

        return array($item_name, $item);
    
    }

    function generate_judge_time_row($submission, $results) {
        $item_name = get_string('judgetime','assignment_onlinejudge').':';
        $item = get_string('notavailable');
        if (!empty($results->judgetime)) {
            $item = userdate($results->judgetime).'&nbsp('.get_string('early', 'assignment', format_time(time() - $results->judgetime)) . ')';
        }
        return array($item_name, $item);
    }


    /**
     * Summarizes the comments for a given test-case in an easy-to-read table.
     */
    function summarize_comments($test_case, $max_grade) {
    
      //And generate a new table containing each of the comments and demerits
      //generated by the unit tests.
      $table = new html_table();
      $table->attributes['class'] = 'codefeedback';
      $table->align = array('center', 'left');
      $table->width = '100%';
      $table->size = array('4em', '');

      //Display each of the grading comments for the given row.
      foreach($test_case->comments as $comment) {

        //If points have been added/subtracted for this, render a number of poitns...
        if($comment[0] != 0)  {
          $comment[0] = ($comment[0] / 100.0) * $max_grade; 
        } 
        //Otherwise, leave this field blank.
        else {
          $comment[0] = '';
        }

        //Add the testbench comment to the table.
        $table->data[] = $comment;

      }

      //Convert the comments into a HTML table.
      return html_writer::table($table);
    
    }

    /**
     * Generates the HTML code used to describe a graded problem.
     *
     * @param object test_case An object representing the known information regarding the test case, as
     *               returned by get_results.
     * @param string name The name to be displayed in the left-hand column of the table.
     * @param float total_weight The total weight of all questions.
     * 
     */ 
    function generate_test_case_result_row($test_case, $name, $total_weight = 1.0) {

      global $OUTPUT;

      //print_object($this);

      //Determine the maximum possible grade for the given question.
      $max_grade = ($test_case->weight / $total_weight) * $this->assignment->grade;

      //Create the label for the problem.
      $label  = html_writer::tag('strong', $name . html_writer::empty_tag('br'));

      //If the problem has been graded, display its grade.
      if($test_case->status == ONLINEJUDGE_STATUS_ACCEPTED) {
        $label .= html_writer::tag('span', $test_case->grade * $max_grade . '/' . $max_grade);
      }

      //Fill in the comment status.
      $contents  = get_string('status'.$test_case->status, 'local_onlinejudge').'.';
      $contents .= html_writer::empty_tag('br');

      //If the test case has been graded, and has comments, display it.
      if($test_case->status == ONLINEJUDGE_STATUS_ACCEPTED && !empty($test_case->comments)) {
        $contents .= $OUTPUT->spacer();
        $contents .= $this->summarize_comments($test_case, $max_grade); 
      }

      //Return the two cells that make up the problem's row in the outer table.
      return array($label, $contents);
    }

    /**
     * Display grading information regarding the submission.
     */
    function view_summary($user = 0, $return = true) {
        global $USER, $CFG, $DB, $OUTPUT, $PAGE;

        //If we weren't passed a user, use the current user.
        if ($user == 0) {
            $user = $USER->id;
        }

        //Create a new table, which will display the results of the submission.
        $table = new html_table();
        $table->id = 'assignment_onlinejudge_summary';
        $table->attributes['class'] = 'generaltable';
        $table->align = array ('right', 'left');
        $table->size = array('20%', '');
        $table->width = '100%';

        //Retrieve the results of the current submission.
        $submission = $this->get_submission($user);
        $results = $this->get_onlinejudge_result($submission);

        //Output the status and judge time.
        $table->data[] = $this->generate_status_row($submission, $results);
        $table->data[] = $this->generate_judge_time_row($submission, $results);
        $output = html_writer::table($table);
        $output .= $OUTPUT->spacer(null, true);

        //If a compilation error occurred, display it directly.
        if($results->status == ONLINEJUDGE_STATUS_COMPILATION_ERROR) {

          //Get the compiler output for the first test case.
          $compiler_output = htmlspecialchars(reset($results->testcases)->compileroutput);

          //And display it to the student.
          $table->size = array('18%', '');
          $table->data = array(array(html_writer::tag('pre', $compiler_output)));

        } 
        //Otherwise, summarize each of the indvidual statuses.
        else {

          //Create a table summarizing each of the problems.
          $table->data = array();
          $table->size = array('18%', '');
          $table->align = array ('right', 'left');

          //And add teach test case.
          foreach($results->testcases as $i => $test_case) {
            $table->data[] = $this->generate_test_case_result_row($test_case, get_string('problem', 'assignment_onlinejudge', $i + 1), $results->total_weight);
          }

        }

        //Add it to the output.
        $output .= html_writer::table($table);

        //If return is set, return the contents instead of echoing it.
        if($return) {
          return $output;
        } 
        //Otherwise, output it directly.
        else {
          echo $output;
        }

    }

    /**
     * return success rate. return more details if $detail is set
     */
    function get_statistics($submission = null, &$detail = null) {
        global $DB;

        if (is_null($submission))
            $submission = $this->get_submission();
        if (isset($submission->id) && true /*TODO: judged? */) {
            $statistics = array();
            foreach ($results as $result) {
                $status = $result->status;
                if (!array_key_exists($status, $statistics))
                    $statistics[$status] = 0;
                $statistics[$status]++;
            }

            $judge_count = 0;
            foreach($statistics as $status => $count) {
                if (empty($detail))
                    $detail = get_string('status'.$status, 'assignment_onlinejudge').': '.$count;
                else
                    $detail .= '<br />'.get_string('status'.$status, 'assignment_onlinejudge').': '.$count;
                if ($status == 'ac') // all ac count as one
                    $judge_count += 1;
                else
                    $judge_count += $count;
            }

            if (array_key_exists('ac', $statistics))
                return 1/$judge_count;
            else
                return 0;
        }
        $detail = get_string('notavailable');
        return 0;
    }

    /**
     * return all results of the submission
     *
     * it will update the grade if necessary
     * @param object submission
     * @return object
     */
    function get_onlinejudge_result($submission) {
        global $DB;

        if (empty($submission)) {
          return null;
        }

        $test_cases = self::get_testcases_for_submission($submission);

        $cases = array();
        $result = new stdClass;
        $result->judgetime = 0;
        $result->total_weight = 0;

        foreach ($test_cases as $test_case) {

            if ($task = onlinejudge_get_task($test_case->task)) {
                $task->testcase = $test_case->testcase;
                $task->feedback = $test_case->feedback;

                list($task->grade, $task->weight, $task->comments) = $this->get_test_case_grade($test_case, $task);

                //Add the test case's weight to our running total.
                $result->total_weight += $task->weight;

                if ($task->judgetime > $result->judgetime) {
                    $result->judgetime = $task->judgetime;
                }

                $cases[] = $task;
            } else {
                $cases[] = null;
            }
        }

        $result->testcases = $cases;
        $result->status = onlinejudge_get_overall_status($cases);

        return $result;
    }

    function update_submission($submission, $new_oj=false) {
        global $DB;

        $DB->update_record('assignment_submission', $submission);

        if ($new_oj) {
            $submission->submission = $submission->id;
            $DB->insert_record('assignment_oj_submissions', $submission);
        } else {
            $submission->id = $submission->oj_id;
            $DB->update_record('assignment_oj_submissions', $submission);
        }
    }

    /**
     * This function returns an
     * array of possible memory sizes in an array, translated to the
     * local language.
     *
     * @return array
     */
    static function get_max_memory_usages() {

        // Get max size
        $maxsize = 1024 * 1024 * get_config('local_onlinejudge', 'maxmemlimit');
        $memusage[$maxsize] = display_size($maxsize);

        $sizelist = array(1048576, 2097152, 4194304, 8388608, 16777216, 33554432,
                          67108864, 134217728, 268435456, 536870912);

        foreach ($sizelist as $sizebytes) {
           if ($sizebytes < $maxsize) {
               $memusage[$sizebytes] = display_size($sizebytes);
           }
        }

        ksort($memusage, SORT_NUMERIC);

        return $memusage;
    }

    /**
     * This function returns an
     * array of possible CPU time (in seconds) in an array
     *
     * @return array
     */
    static function get_max_cpu_times() {

        // Get max size
        $maxtime = get_config('local_onlinejudge', 'maxcpulimit');
        $cputime[$maxtime] = get_string('numseconds', 'moodle', $maxtime);
        $timelist = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 20, 25, 30, 40, 50, 60);
        foreach ($timelist as $timesecs) {
           if ($timesecs < $maxtime) {
               $cputime[$timesecs] = get_string('numseconds', 'moodle', $timesecs);
           }
        }

        ksort($cputime, SORT_NUMERIC);

        return $cputime;
    }

    /**
     * Send judge task request to judgelib
     */
    function request_judge($submission) {
        global $DB;

        $oj = $DB->get_record('assignment_oj', array('assignment' => $submission->assignment), '*', MUST_EXIST);

        $source = array();
        $fs = get_file_storage();
        $files = $fs->get_area_files($this->context->id, 'mod_assignment', 'submission', $submission->id, 'sortorder, timemodified', false);

        // Mark all old tasks as old
        $DB->set_field('assignment_oj_submissions', 'latest', 0, array('submission' => $submission->id));

        $tests = $this->get_testcases();
        foreach ($tests as $test) {
            $oj->input = $test->input;
            $oj->output = $test->output;
            $oj->var1 = $oj->ideoneuser;
            $oj->var2 = $oj->ideonepass;

            // Submit task. Use transaction to avoid task is been judged before inserting into assignment_oj_submissions
            try {
                $transaction = $DB->start_delegated_transaction();
                $taskid = onlinejudge_submit_task($this->cm->id, $submission->userid, $oj->language, $files, 'assignment_onlinejudge', $oj);
                $DB->insert_record('assignment_oj_submissions', array('submission' => $submission->id, 'testcase' => $test->id, 'task' => $taskid, 'latest' => 1));
                $transaction->allow_commit();
            } catch (Exception $e) {
                //TODO: reconnect db ?
                $transaction->rollback($e); // rethrows exception
            }
        }
    }

    /**
     * return grade
     *
     * @param int status
     * @param float $fraction
     * @return grade
     */
    function grade_marker($status, $fraction) {

        //TODO: Get rid of accepted?

        $grades = array(
            ONLINEJUDGE_STATUS_PENDING                 => -1,
            ONLINEJUDGE_STATUS_JUDGING                 => -1,
            ONLINEJUDGE_STATUS_INTERNAL_ERROR          => -1,
            ONLINEJUDGE_STATUS_WRONG_ANSWER            => $fraction,
            ONLINEJUDGE_STATUS_RUNTIME_ERROR           => 0,
            ONLINEJUDGE_STATUS_TIME_LIMIT_EXCEED       => 0,
            ONLINEJUDGE_STATUS_MEMORY_LIMIT_EXCEED     => 0,
            ONLINEJUDGE_STATUS_OUTPUT_LIMIT_EXCEED     => 0,
            ONLINEJUDGE_STATUS_COMPILATION_ERROR       => 0,
            ONLINEJUDGE_STATUS_COMPILATION_OK          => 0,
            ONLINEJUDGE_STATUS_RESTRICTED_FUNCTIONS    => 0,
            ONLINEJUDGE_STATUS_ABNORMAL_TERMINATION    => 0,
            ONLINEJUDGE_STATUS_ACCEPTED                => $fraction,
            ONLINEJUDGE_STATUS_PRESENTATION_ERROR      => $fraction
        );

        return $grades[$status];
    }

    /**
     * Adds specific settings to the settings block
     */
    function extend_settings_navigation($assignmentnode) {
        global $PAGE, $DB, $USER, $CFG;

        if (has_capability('mod/assignment:grade', $PAGE->cm->context)) {
            $string = get_string('rejudgeall','assignment_onlinejudge');
            $link = $CFG->wwwroot.'/mod/assignment/type/onlinejudge/rejudge.php?id='.$this->cm->id;
            $assignmentnode->add($string, $link, navigation_node::TYPE_SETTING);

            $string = get_string('managetestcases','assignment_onlinejudge');
            $link = $CFG->wwwroot.'/mod/assignment/type/onlinejudge/testcase.php?id='.$this->cm->id;
            $assignmentnode->add($string, $link, navigation_node::TYPE_SETTING);
        }
    }

    /**
     * Attempts to the submission data for the given task.
     * 
     * @param object task An object representing the task that was just judged, in the same format retrieved from the database.
     * 
     * @return object The database information for the submission that produced the given task, or false if none exists.
     */
    public static function get_submission_for_task($task) {
      
        global $DB;
    
        $sql = 'SELECT s.*
            FROM {assignment_submissions} s LEFT JOIN {assignment_oj_submissions} o
            ON s.id = o.submission
            WHERE o.task = ?';

        return $DB->get_record_sql($sql, array($task->id));
        
    }

    /**
     * Attempts to the submission data for the given task.
     * 
     * @param object task An object representing the relevant submission.
     * 
     * @return object The database information for the testcases associated with the given submission.
     */
    public static function get_testcases_for_submission($submission) {

        global $DB;

        $sql = 'SELECT s.*, t.subgrade, t.feedback
            FROM {assignment_oj_submissions} s LEFT JOIN {assignment_oj_testcases} t
            ON s.testcase = t.id
            WHERE s.submission = ? AND s.latest = 1
            ORDER BY t.sortorder ASC'; //FIXME: last line may be bad?

        return $DB->get_records_sql($sql, array($submission->id));
    }

    /**
     * Summates all of the task comments in the testbench.
     */
    public static function extract_task_comments($task, $max_grade = 100) {

        //A test-case that fails no assertions should be
        //silent; and thus have the maximum possible grade.
        $grade = $max_grade;

        //Create an array to store each of the relevant instructor comments.
        $remarks = [];

        //Parse the test-case output.
        $marks = explode("\n", $task->output);

        //For each of the marks in the test-case output,
        //adjust the grade.
        foreach($marks as $mark) {

            //Skip empty marks.
            if(empty($mark)) {
              continue;
            }

            //Break the grade into its components.
            $remark = explode('|', $mark);
            $remarks[] = $remark;

            //Add the mark to the assignment's grade.
            $grade += intval($remark[0]);
        }

        //Return the final grade.
        return array($grade, $remarks);;
    }

    
    public function get_test_case_grade($test_case, $task = null) {
    
        //Fetch the results of the individual test case.
        $task = $task ?: onlinejudge_get_task($test_case->task);

        //If we weren't able to fetch the given task,
        //return -1.
        if(!$task) {
            return -1;
        }

        //Get the grade for the given assignment.
        //TODO: rename grade marker?
        list($raw_grade, $feedback) = self::extract_task_comments($task);
        $grade = $this->grade_marker($task->status, $raw_grade);

        //Weight the grade according to the task's weight.
        $grade = ($grade * $test_case->subgrade) / 100;

        //If the judge engine did not grade this test case (due to an internal error?)
        //skip this iteration of graidng; and keep the old grade.
        if ($grade == -1) { 
            return true;
        }

        return array($grade, $test_case->subgrade, $feedback);
    }


    /**
     * Grades a given submission, updating its grade.
     *
     * @param object $submission The database record for the given submission.
     */
    public function grade_submission($submission) {

        //Retrieve all of the test cases ("problems") associated with the given assignment.
        $test_cases = assignment_onlinejudge::get_testcases_for_submission($submission);

        // If there are no test cases, then complete processing of this event.
        // TODO: Possibly indicate an error?
        if (!$test_cases) {
            debugging('No test cases for submission '.$submission->id);
            return true;   
        }
        
        //Assume an initial grade of zero.
        //TODO: Weighted system?
        $submission->grade = 0;
        $max_grade = 0;

        //For each of the provided "test case" problems.
        foreach ($test_cases as $test_case) {

            //Get the grade for the individual test case.
            list($grade, $weight) = $this->get_test_case_grade($test_case);

            //If we weren't able to grade this test case,
            //skip it.
            //TODO: throw exception?
            if($grade === -1) {
                debugging('Failed grade for submission '.$submission->id);
                return true;
            }

            //Otherwise, add the grade to our total.
            //TODO: Weighted mechanism?
            $submission->grade += $grade;

            //Add the maximum grade for the question to the total maximum grade.
            $max_grade += $weight;
        }

        //Compute the final submission grade.
        $submission->grade = ($submission->grade * $this->assignment->grade) / (float)$max_grade;

        //Mark the time at which the assessment was graded.
        $submission->timemarked = time();

        //TODO: replace administrator with teacher
        $submission->teacher = get_admin()->id;

        //Do not notify students of grading.
        //TODO: Abstract to setting?
        $submission->mailed = 1; 

    }
}


/**
 * Handle the "C program graded" event.
 *
 * @param object task
 */
function onlinejudge_task_judged($task) {
    global $DB;

    //Retrieve the submission associated with the given task.
    $submission = assignment_onlinejudge::get_submission_for_task($task);

    //If no submission was found, return "true", indicating that the task's processing
    //is compelte.
    if(!$submission) {
        return true;
    }

    //Retrieve the metadata for the current assignment.
    $cm = get_coursemodule_from_instance('assignment', $submission->assignment);
    $assignment = new assignment_onlinejudge($cm->id, NULL, $cm);

    //Grade the submission.
    $assignment->grade_submission($submission);

    //Update the assignment...
    $DB->update_record('assignment_submissions', $submission);
    $assignment->update_grade($submission);

    //And indicate success.
    return true;
}

