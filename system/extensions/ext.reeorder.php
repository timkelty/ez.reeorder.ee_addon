<?php

//------------------------------------
//   "REEOrder" Extension
//   using 'show_full_control_panel_end' hook
//   'cp.display.php' (EE 1.5.2)
//   Build: 20090301
//   author: Elwin Zuiderveld
//------------------------------------

if ( ! defined('EXT'))
{
    exit('Invalid file request');
}

class REEOrder
{
	var $DEV_MODE = 1; // 1
	
	var $settings = array();
	var $name = 'REEOrder';
	var $classname = 'REEOrder';
	var $version = '1.0';
	var $description = 'Hides the Custom Field that is used by the REEOrder Module';
	var $settings_exist = 'n';
	var $docs_url = '';
	
	//------------------------------------
	//   Constructor - Settings
	//------------------------------------
	
	function REEOrder($settings='')
	{
		$this->settings = $settings;
	}
	// END
	
	//------------------------------------
	//  Activate Extension
	//------------------------------------
	
	function activate_extension()
	{
		global $DB;
		
		$DB->query($DB->insert_string('exp_extensions',
				array(
				'extension_id'	=> '',
				'class'			=> $this->classname,
				'method'		=> "hide_field",
				'hook'			=> "show_full_control_panel_end",
				'settings'		=> '',
				'priority'		=> 10,
				'version'		=> $this->version,
				'enabled'		=> "y"
				)
			)
		);
	}
	// END
	
	//------------------------------------
	//  Update Extension
	//------------------------------------
	
	function update_extension($current='')
	{
		global $DB;
		
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}
		
		if ($current < '1.0')
		{
			// Update to 1.0
		}
		
		$DB->query("UPDATE exp_extensions 
					SET version = '".$DB->escape_str($this->version)."' 
					WHERE class = '$this->classname'");
	}
	// END
	
	//------------------------------------
	//  Disable Extension (DEVMODE only)
	//------------------------------------
	
	function disable_extension()
	{
		global $DB;
		if ($this->DEV_MODE) $DB->query("DELETE FROM exp_extensions WHERE class = '$this->classname'");
	}
	// END
	
	//------------------------------------
	//  Extension Settings
	//------------------------------------
	
	function settings()
	{
		$settings = array();
		
		return $settings;
	}
	// END
	
	//--------------------------------------
	// Get ID of 'reeorder_module' field
	//--------------------------------------
	
	function get_field_id()
	{
		global $DB, $IN, $DSP, $LANG;
		
		$weblog_id = $IN->GBL('weblog_id');
		$custom_field_id_query = $DB->query("SELECT * FROM exp_reeorder_prefs WHERE weblog_id = '$weblog_id'");
		
		if ($custom_field_id_query->num_rows == 0)
		{
			return;
		}
		
		return $custom_field_id_query->row['field_id'];
	}
	// END
	
	//------------------------------------
	//   Hide Custom Field
	//------------------------------------
	
	function hide_field($out)
	{
		global $IN, $EXT, $DSP, $SESS;
		
		// This variable will return whatever the last extension returned to this hook
		if($EXT->last_call !== false)
		{
			$out = $EXT->last_call;
		}
		
		if ($IN->GBL('M') == 'edit_entry' || $IN->GBL('M') == 'entry_form' || $IN->GBL('M') == 'new_entry')
		{
			$field_id = $this->get_field_id();
			
			$js = <<<JS
			<!-- 'REEOrder' Extension script -->
			<script type="text/javascript">
			//<![CDATA[
			document.getElementById('field_pane_off_{$field_id}').parentNode.style.display='none';
			//]]>
			</script>
			<!-- END -->
JS;
			$out = str_replace('</body>', $js."\n\n</body>", $out);
		}
		
		return $out;
	}
	// END
	
}
?>