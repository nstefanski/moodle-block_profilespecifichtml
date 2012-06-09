<?php //$Id: block_profilespecifichtml.php,v 1.2 2012-04-28 10:24:54 vf Exp $

class block_profilespecifichtml extends block_base {

    function init() {
        $this->title = get_string('blockname', 'block_profilespecifichtml');
        $this->version = 2012042700;
    }

    function applicable_formats() {
        return array('all' => true);
    }

    function specialization() {
        $this->title = isset($this->config->title) ? format_string($this->config->title) : format_string(get_string('newhtmlblock', 'block_profilespecifichtml'));
    }

    function instance_allow_multiple() {
        return true;
    }

    function get_content() {
    	global $USER;
    	
        if ($this->content !== NULL) {
            return $this->content;
        }

        if (!empty($this->instance->pinned) or $this->instance->pagetype === 'course-view') {
            // fancy html allowed only on course page and in pinned blocks for security reasons
            $filteropt = new stdClass;
            $filteropt->noclean = true;
        } else {
            $filteropt = null;
        }
        
        $this->content = new stdClass;
        $this->content->text .= !empty($this->config->text_all) ? format_text($this->config->text_all, FORMAT_HTML, $filteropt) : '';
        
        if (empty($this->config->field1) && empty($this->config->field2)){
        	$this->content->footer = '';
        	return($this->content);
        }       
        
        $uservalue = get_field('user_info_data', 'data', 'fieldid', $this->config->field1, 'userid', $USER->id); 
        
        $expr = "\$res1 = {$uservalue} {$this->config->op1} {$this->config->value1} ;";
        @eval($expr);
        
        if ($this->config->op){

	        $uservalue = get_field('user_info_data', 'data', 'fieldid', $this->config->field2, 'userid', $USER->id); 
	        
	        $expr = "\$res2 = {$uservalue} {$this->config->op2} {$this->config->value2} ;";
	        @eval($expr);
	        
	        $finalexpr = "\$res = $res1 {$this->config->op} $res2 ;"; 
	        @eval($finalexpr);
        } else {
        	$res = @$res1;
        }

		if (@$res){
        	$this->content->text .= format_text(@$this->config->text_match, FORMAT_HTML, $filteropt);
        } else {
        	$this->content->text .= format_text(@$this->config->text_nomatch, FORMAT_HTML, $filteropt);
        }
        $this->content->footer = '';

        unset($filteropt); // memory footprint

        return $this->content;
    }

    /**
     * Will be called before an instance of this block is backed up, so that any links in
     * any links in any HTML fields on config can be encoded.
     * @return string
     */
    function get_backup_encoded_config() {
        /// Prevent clone for non configured block instance. Delegate to parent as fallback.
        if (empty($this->config)) {
            return parent::get_backup_encoded_config();
        }
        $data = clone($this->config);
        $data->text_all = backup_encode_absolute_links($data->textall);
        $data->text_match = backup_encode_absolute_links($data->text_match);
        $data->text_nomatch = backup_encode_absolute_links($data->text_nomatch);
        return base64_encode(serialize($data));
    }

    /**
     * This function makes all the necessary calls to {@link restore_decode_content_links_worker()}
     * function in order to decode contents of this block from the backup 
     * format to destination site/course in order to mantain inter-activities 
     * working in the backup/restore process. 
     * 
     * This is called from {@link restore_decode_content_links()} function in the restore process.
     *
     * NOTE: There is no block instance when this method is called.
     *
     * @param object $restore Standard restore object
     * @return boolean
     **/
    function decode_content_links_caller($restore) {
        global $CFG;

        if ($restored_blocks = get_records_select("backup_ids", "table_name = 'block_instance' AND backup_code = $restore->backup_unique_code AND new_id > 0", "", "new_id")) {
            $restored_blocks = implode(',', array_keys($restored_blocks));
            $sql = "SELECT bi.*
                      FROM {$CFG->prefix}block_instance bi
                           JOIN {$CFG->prefix}block b ON b.id = bi.blockid
                     WHERE b.name = 'profilespecifichtml' AND bi.id IN ($restored_blocks)"; 

            if ($instances = get_records_sql($sql)) {
                foreach ($instances as $instance) {
                    $blockobject = block_instance('profilespecifichtml', $instance);
                    $blockobject->config->text_all = restore_decode_absolute_links($blockobject->config->text_all);
                    $blockobject->config->text_match = restore_decode_absolute_links($blockobject->config->text_match);
                    $blockobject->config->text_nomatch = restore_decode_absolute_links($blockobject->config->text_nomatch);
                    $blockobject->instance_config_commit($blockobject->pinned);
                }
            }
        }

        return true;
    }

    /*
     * Hide the title bar when none set..
     */
    function hide_header(){
        return empty($this->config->title);
    }
}
?>
