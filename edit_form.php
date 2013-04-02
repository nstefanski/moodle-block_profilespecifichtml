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
 * Form for editing HTML block instances.
 *
 * @package   block_profilespecifichtml
 * @copyright 2012 Valery Fremaux (valery.fremaux@gmail.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @version   Moodle 2.x
 */

class block_profilespecifichtml_edit_form extends block_edit_form {
    protected function specific_definition($mform) {
    	global $COURSE, $DB;
    	
        // Fields for editing HTML block title and contents.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

	    $userfieldcats = $DB->get_records('user_info_category', array(), 'sortorder');
	    
	    $fieldoptions = array();
	    foreach($userfieldcats as $cat){
	    	$fieldoptions = $fieldoptions + $DB->get_records_menu('user_info_field', array('categoryid' => $cat->id), 'sortorder', 'id,name');
	    }
	        
	    $fieldopoptions['=='] = '=';
	    $fieldopoptions['!='] = '!=';
	    $fieldopoptions['>'] = '>';
	    $fieldopoptions['<'] = '<';
	    $fieldopoptions['>='] = '>=';
	    $fieldopoptions['<='] = '<=';
	    
	    $clauseopoptions[0] = get_string('minus', 'block_profilespecifichtml');
	    $clauseopoptions['&&'] = get_string('and', 'block_profilespecifichtml');
	    $clauseopoptions['||'] = get_string('or', 'block_profilespecifichtml');

        $mform->addElement('text', 'config_title', get_string('configtitle', 'block_profilespecifichtml'));
        $mform->setType('config_title', PARAM_MULTILANG);

		$group1[0] = &$mform->createElement('select', 'config_field1', '', $fieldoptions);
		$group1[1] = &$mform->createElement('select', 'config_op1', '', $fieldopoptions);
		$group1[2] = &$mform->createElement('text', 'config_value1', '', array('size' => 30));
		$mform->addGroup($group1, 'group1', get_string('configprofilefield1', 'block_profilespecifichtml'), array('&nbsp;'), false);

        $mform->addElement('select', 'config_op', get_string('configprofileop', 'block_profilespecifichtml'), $clauseopoptions);

		$group2[0] = &$mform->createElement('select', 'config_field2', '', $fieldoptions);
		$group2[1] = &$mform->createElement('select', 'config_op2', '', $fieldopoptions);
		$group2[2] = &$mform->createElement('text', 'config_value2', '', array('size' => 30));
		$mform->addGroup($group2, 'group2', get_string('configprofilefield2', 'block_profilespecifichtml'), array('&nbsp;'), false);

        $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean' => true, 'context' => $this->block->context);
        $mform->addElement('editor', 'config_text_all', get_string('configcontentforall', 'block_profilespecifichtml'), null, $editoroptions);
        $mform->setType('config_text_all', PARAM_RAW); // XSS is prevented when printing the block contents and serving files

        $mform->addElement('editor', 'config_text_match', get_string('configcontentwhenmatch', 'block_profilespecifichtml'), null, $editoroptions);
        $mform->setType('config_text_match', PARAM_RAW); // XSS is prevented when printing the block contents and serving files

        $mform->addElement('editor', 'config_text_nomatch', get_string('configcontentwhennomatch', 'block_profilespecifichtml'), null, $editoroptions);
        $mform->setType('config_text_nomatch', PARAM_RAW); // XSS is prevented when printing the block contents and serving files

    }

    function set_data($defaults, &$files = null) {
    	global $COURSE;
    	
    	$text_all = '';
    	$text_match = '';
    	$text_nomatch = '';
        if (!empty($this->block->config) && is_object($this->block->config)) {

			// draft file handling for all
            $text_all = $this->block->config->text_all;
            $draftid_editor = file_get_submitted_draft_itemid('config_text_all');
            if (empty($text_all)) {
                $currenttext = '';
            } else {
                $currenttext = $text_all;
            }
            $defaults->config_text_all['text'] = file_prepare_draft_area($draftid_editor, $this->block->context->id, 'block_profilespecifichtml', 'content', 0, array('subdirs' => true), $currenttext);
            $defaults->config_text_all['itemid'] = $draftid_editor;
            $defaults->config_text_all['format'] = @$this->block->config->format;

			// draft file handling for matching
            $text_match = $this->block->config->text_match;
            $draftid_editor = file_get_submitted_draft_itemid('config_text_match');
            if (empty($text_match)) {
                $currenttext = '';
            } else {
                $currenttext = $text_match;
            }
            $defaults->config_text_match['text'] = file_prepare_draft_area($draftid_editor, $this->block->context->id, 'block_profilespecifichtml', 'match', 0, array('subdirs' => true), $currenttext);
            $defaults->config_text_match['itemid'] = $draftid_editor;
            $defaults->config_text_match['format'] = @$this->block->config->format;

			// draft file handling for no matching
            $text_nomatch = $this->block->config->text_nomatch;
            $draftid_editor = file_get_submitted_draft_itemid('config_text_nomatch');
            if (empty($text_nomatch)) {
                $currenttext = '';
            } else {
                $currenttext = $text_nomatch;
            }
            $defaults->config_text_nomatch['text'] = file_prepare_draft_area($draftid_editor, $this->block->context->id, 'block_profilespecifichtml', 'nomatch', 0, array('subdirs' => true), $currenttext);
            $defaults->config_text_nomatch['itemid'] = $draftid_editor;
            $defaults->config_text_nomatch['format'] = @$this->block->config->format;
		}

        if (!$this->block->user_can_edit() && !empty($this->block->config->title)) {
            // If a title has been set but the user cannot edit it format it nicely
            $title = $this->block->config->title;
            $defaults->config_title = format_string($title, true, $this->page->context);
            // Remove the title from the config so that parent::set_data doesn't set it.
            unset($this->block->config->title);
        }

        // have to delete text here, otherwise parent::set_data will empty content
        // of editor
        unset($this->block->config->text_all);
        unset($this->block->config->text_match);
        unset($this->block->config->text_nomatch);
        parent::set_data($defaults);

        // restore $text in each
        $this->block->config->text_all = $text_all;
        $this->block->config->text_match = $text_match;
        $this->block->config->text_nomatch = $text_nomatch;

        if (isset($title)) {
            // Reset the preserved title
            $this->block->config->title = $title;
        }

    }
}