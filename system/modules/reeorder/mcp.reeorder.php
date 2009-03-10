<?php

/*
=====================================================
REEOrder Module for ExpressionEngine
-----------------------------------------------------
Build: 20090301
-----------------------------------------------------
Copyright (c) 2005 - 2009 Elwin Zuiderveld
=====================================================
THIS MODULE IS PROVIDED "AS IS" WITHOUT WARRANTY OF
ANY KIND OR NATURE, EITHER EXPRESSED OR IMPLIED,
INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE,
OR NON-INFRINGEMENT.
=====================================================
File: mcp.reeorder.php
-----------------------------------------------------
Purpose: REEOrder Module - CP
=====================================================
*/

class Reeorder_CP {

	var $version = '1.2';
	
	// -------------------------
	//	Constructor
	// -------------------------
	
	function Reeorder_CP($switch = TRUE)
	{
		global $IN, $DB;
		
		// Is Module installed?
		if ($IN->GBL('M') == 'INST')
		{
			return;
		}
		
		// Check installed version
		$query = $DB->query("SELECT module_version FROM exp_modules WHERE module_name = 'Reeorder'");
		
		if ($query->num_rows == 0)
		{
			return;
		}
		
		// update version number
		if ($query->row['module_version'] < $this->version)
		{
			$this->reeorder_module_update();
		}
		// end update
		
		// add status field
		if ($query->row['module_version'] < 1.2)
		{
			$DB->query("ALTER TABLE exp_reeorder_prefs ADD COLUMN status TEXT");
		}
		
		if ($switch)
		{
			switch($IN->GBL('P'))
			{
				case 'list_entries'	: $this->list_entries();
					break;
				case 'change_order'	: $this->change_order();
					break;
				case 'preferences'	: $this->preferences();
					break;
				case 'update_prefs'	: $this->update_prefs();
					break;
				default				: $this->reeorder_home();
					break;
			}
		}
	}
	// END
	
	
	// ----------------------------------------
	//	Module installer
	// ----------------------------------------
	
	function reeorder_module_install()
	{
		global $DB;
		
		$sql[] = "INSERT INTO exp_modules (module_id, 
											module_name, 
											module_version, 
											has_cp_backend) 
											VALUES 
											('', 
											'Reeorder', 
											'$this->version', 
											'y')";
		
		$sql[] = "CREATE TABLE IF NOT EXISTS `exp_reeorder_prefs` (`weblog_id` INT(4) UNSIGNED NOT NULL, 
																	`field_id` INT(4) UNSIGNED NOT NULL, 
																	`status` TEXT, 
																	`sort_order` VARCHAR(10) NOT NULL)";
		
		foreach ($sql as $query)
		{
			$DB->query($query);
		}
		
		return true;
	}
	// END
	
	
	// ----------------------------------------
	//	Module installer
	// ----------------------------------------
	
	function reeorder_module_update()
	{
		global $DB;
		
		// update version number
		$sql[] = "UPDATE exp_modules SET module_version = '{$this->version}' WHERE module_name = 'Reeorder'";
		
		$sql[] = "CREATE TABLE IF NOT EXISTS `exp_reeorder_prefs` (`weblog_id` INT(4) UNSIGNED NOT NULL, 
																	`field_id` INT(4) UNSIGNED NOT NULL,
																	`sort_order` VARCHAR(10) NOT NULL)";
		
		foreach ($sql as $query)
		{
			$DB->query($query);
		}
		
		return true;
	}
	// END
	
	
	// ----------------------------------------
	//	Module de-installer
	// ----------------------------------------
	
	function reeorder_module_deinstall()
	{
		global $DB;
		
		$query = $DB->query("SELECT module_id
							 FROM exp_modules 
							 WHERE module_name = 'Reeorder'"); 
		
		$sql[] = "DELETE FROM exp_module_member_groups 
				  WHERE module_id = '".$query->row['module_id']."'";
		
		$sql[] = "DELETE FROM exp_modules 
				  WHERE module_name = 'Reeorder'";
		
		$sql[] = "DELETE FROM exp_actions 
				  WHERE class = 'Reeorder'";
		
		$sql[] = "DELETE FROM exp_actions 
				  WHERE class = 'Reeorder_CP'";
		
		$sql[] = "DROP TABLE IF EXISTS exp_reeorder_prefs";
		
		foreach ($sql as $query)
		{
			$DB->query($query);
		}
		
		return true;
	}
	// END
	
	
	// ----------------------------------------
	//	Module Homepage
	// ----------------------------------------
	
	function reeorder_home($msg='')
	{
		global $DB, $DSP, $FNS, $LANG, $PREFS, $SESS;
		
		// -------------------------------------------------------
		//	HTML Title and Navigation Crumblinks
		// -------------------------------------------------------
		
		$DSP->title = $LANG->line('ttl_reeorder');
		
		$DSP->crumb = $LANG->line('crumb_reeorder');
		
		// only show preferences link to Super Admins
		if ($SESS->userdata['group_id'] == 1) $DSP->right_crumb($LANG->line('preferences'), BASE.AMP.'C=modules'.AMP.'M=reeorder'.AMP.'P=preferences');
		
		// -------------------------------------------------------
		//	Message, if any
		// -------------------------------------------------------
		
		if ($msg != '')
		{
			$DSP->body .= $DSP->qdiv('successBox', $DSP->qdiv('success', $msg));
		}
		
		// -------------------------------------------------------
		//	Table and Table Headers
		// -------------------------------------------------------
		
		$DSP->body .= $DSP->qdiv('tableHeading', $LANG->line('head_choose_a_weblog') . ucwords($PREFS->ini('weblog_nomenclature')));
		
		$DSP->body .= $DSP->table_open(array('class' => 'tableBorder', 'width' => '100%'));
		
		$DSP->body .= $DSP->table_row(array(
											array(
												  'text'  => $LANG->line('weblog_id'),
												  'class' => 'tableHeadingAlt',
												  'width' => '1%'
												  ),
											array(
												  'text'  =>  ucwords($PREFS->ini('weblog_nomenclature')) . $LANG->line('weblog_name'),
												  'class' => 'tableHeadingAlt',
												  'width' => '99%'
												  )// ,
												  //   											array(
												  //   												  'text'  => $LANG->line('weblog_short_name'),
												  //   												  'class' => 'tableHeadingAlt',
												  // 												  'width' => '66%'
												  //   												  )
											)
									  );
		
		// -------------------------------------------------------
		//	Display Available Weblogs
		// -------------------------------------------------------
		
		// only fetch weblogs assigned to current user
		$assigned_weblogs = $FNS->fetch_assigned_weblogs();
		
		$weblog_query = $DB->query("SELECT weblog_id, blog_name, blog_title FROM exp_weblogs WHERE weblog_id IN ('".implode("','", $assigned_weblogs)."')");
		
		$i = 0;
		foreach ($weblog_query->result as $row)
		{
			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
			
			$field_id = $this->get_field_id($row['weblog_id']);
			
			if ($field_id != 'field_id_0' && $field_id != '') {
			
				$DSP->body .= $DSP->table_row(array(
													array(
														  'text'  => $DSP->qdiv('default', $row['weblog_id']),
														  'class' => $style
														  ),
													array(
														  'text'  => $DSP->qdiv('defaultBold', $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=reeorder'.AMP.'P=list_entries'.AMP.'weblog_id='.$row['weblog_id'], $row['blog_title'])),
														  'class' => $style
														  )
													)
											  );
			}
		}
		
		// -------------------------------------------------------
		//	Close Table and Output to $DSP->body
		// -------------------------------------------------------
		
		$DSP->body .= $DSP->table_close();
		$DSP->body .= $DSP->qdiv('box default', $LANG->line('link_documentation'));
		
	}
	// END
	
	
	// -------------------------
	// List Entries
	// -------------------------
	
	function list_entries($msg='')
	{
		//print_r(get_defined_constants()); exit();
		global $FNS, $IN, $DB, $DSP, $LANG, $PREFS, $SESS;
		
		// Import the JS
		if ($js = $DSP->file_open(PATH.'modules/reeorder/jquery.tablednd.js'))
		{
			$DSP->initial_body .= '<script type="text/javascript">'
				. NL.$js.NL
				. '</script>'
				. '<style type="text/css">'
				. '.sort-handle { cursor:move; }'
				. '</style>';
		}
		
		// -------------------------------------------------------
		//	HTML Title and Navigation Crumblinks
		// -------------------------------------------------------
		$DSP->title = $LANG->line('ttl_reeorder');
		
		$DSP->crumb = $LANG->line('crumb_reeorder');
		
		$DSP->crumb = $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=reeorder',$LANG->line('crumb_reeorder'));
		
		$DSP->crumb .= $DSP->crumb_item(ucwords($PREFS->ini('weblog_nomenclature')).$LANG->line('crumb_entries'));
		
		// only show preferences link to Super Admins
		if ($SESS->userdata['group_id'] == 1) $DSP->right_crumb($LANG->line('preferences'), BASE.AMP.'C=modules'.AMP.'M=reeorder'.AMP.'P=preferences');
		
		$weblog_id = $IN->GBL('weblog_id');
		$blog_query = $DB->query("SELECT blog_title FROM exp_weblogs WHERE weblog_id = '$weblog_id'");
		$blog_name = $blog_query->row['blog_title'];
		
		$DSP->body .= $DSP->qdiv('tableHeading', ucwords($PREFS->ini('weblog_nomenclature')). ": " .$blog_name);
		
		// -------------------------------------------------------
		//	Message, if any
		// -------------------------------------------------------
		
		if ($msg != '')
		{
			$DSP->body .= $DSP->qdiv('successBox', $DSP->qdiv('success', $msg));
		}
		
		// -------------------------------------------------------
		//	Table and Table Headers
		// -------------------------------------------------------
		
		$DSP->body .= $DSP->table_open(array('class' => 'tableBorder', 'width' => '100%', 'id' => 'entries'));
		
		$DSP->body .= $DSP->table_row(array(
											// array(
											// 	  'text'  => $LANG->line('entry_id'),
											// 	  'class' => 'tableHeadingAlt'
											// 	  ),
											array(
												  'text'  => $LANG->line('head_order'),
												  'class' => 'tableHeadingAlt'
												  ),
											array(
												  'text'  => '<!--↑↓-->',
												  'class' => 'tableHeadingAlt'
												  ),
											array(
												  'text'  => $LANG->line('head_entry_title'),
												  'class' => 'tableHeadingAlt'
												  ),
											array(
												  'text'  => $LANG->line('head_status'),
												  'class' => 'tableHeadingAlt'
												  )
											)
									  );
		
		// -------------------------------------------------------
		//	Select Entries from Database
		// -------------------------------------------------------
		
		$field_id = $this->get_field_id($weblog_id);
		
		if ($field_id == 'field_id_0' || $field_id == '') {
			$DSP->body .= $DSP->error_message($LANG->line('err_no_field_id'));
			return;
		}
		
		// sort depends on preferences
		$pref_query = $DB->query("SELECT sort_order FROM exp_reeorder_prefs WHERE weblog_id = '$weblog_id'");
		
		if ($pref_query->num_rows == 0)
		{
			$sort = 'DESC';
		} else {
			$sort = $pref_query->row['sort_order'];
		}
		
		$selected_statuses_query = $DB->query("SELECT status 
									FROM exp_reeorder_prefs 
									WHERE weblog_id = $weblog_id");
		
		$selected_statuses = '';
		if ($selected_statuses_query->num_rows > 0)
		{
			$selected_statuses = $selected_statuses_query->row['status'];
		}
		
		// safety measure if status was deleted
		if($selected_statuses == '') {
			$selected_statuses = '0';
		}
		
		$status_query = $DB->query("SELECT status FROM exp_statuses WHERE status_id IN (".$selected_statuses.")");
		
		$sql= "SELECT wt.entry_id, wt.title, wd.$field_id as field, wt.status AS status 
				FROM exp_weblog_titles wt, exp_weblog_data wd 
				WHERE wt.entry_id = wd.entry_id 
				AND wt.weblog_id = $weblog_id ";
		
		// add selected status if any
		if ($status_query->num_rows > 0)
		{
			$stats = "''";
			foreach ($status_query->result as $stat_row)
			{
				$stats .= ",'".$stat_row['status']."'";
			}
			
			$sql .= "AND wt.status IN ($stats) ";
		}
		
		$sql .= "ORDER BY wd.$field_id $sort";
		
		$entry_query = $DB->query($sql);
		
		$ids = array();
		foreach ($entry_query->result as $row) {
			$ids[] = $row['field'];
		}
		
		$i = 0;
		foreach ($entry_query->result as $row)
		{
			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
			
			$DSP->body .= $DSP->table_row(array(
												array(
													'text'  => $DSP->qspan('default', "\n<select name=\"reeorder_row_".$row['field']."\" class=\"select\" style=\"width:60px;\" onchange=\"location.href='".BASE.AMP.'C=modules'.AMP.'M=reeorder'.AMP.'P=change_order'.AMP.'weblog_id='.$weblog_id.AMP.'entry_id='.$row['entry_id'].AMP.'order_id=\'+this.value'."\">\n\t".$this->create_drop_menu($ids,$row['field']).$DSP->input_select_footer()),
													'class' => $style,
													'width' => '1%;padding:0 13px 0 8px'
													 ),
												array(
													'text'  => $DSP->qspan('defaultBold', '<img src="'.PATH_CP_IMG.'sort.png" border="0"  width="16" height="16" alt="" title="" />'),
													'class' => $style.' sort-handle',
													'width' => '20px;padding-right:8px'
													 ),
												array(
													'text'  => $DSP->qspan('defaultBold', $row['title']),
													'class' => $style
													 ),
												array(
													'text'  => $DSP->qspan('default', $row['status']),
													'class' => $style
													 )
												)
											);
		}
		
		// -------------------------------------------------------
		//	Close Table and Output to $DSP->body
		// -------------------------------------------------------
		
		$DSP->body .= $DSP->table_close();
		$DSP->body .= $DSP->qdiv('box default', $LANG->line('link_documentation'));
	}
	// END
	
	
	//--------------------------------------
	// Change Entry Order
	//--------------------------------------
	
	function change_order()
	{
		global $DB, $FNS, $IN;
		
		$field_id = $this->get_field_id($IN->GBL('weblog_id'));
		
		$order_id = $IN->GBL('order_id');
		
		$weblog_id = $IN->GBL('weblog_id');
		
		// Return Location
		$return = BASE.AMP.'C=modules'.AMP.'M=reeorder'.AMP.'P=list_entries'.AMP.'weblog_id='.$IN->GBL('weblog_id');
		
		$selected_statuses_query = $DB->query("SELECT status 
									FROM exp_reeorder_prefs 
									WHERE weblog_id = $weblog_id");
		
		$selected_statuses = '';
		if ($selected_statuses_query->num_rows > 0)
		{
			$selected_statuses = $selected_statuses_query->row['status'];
		}
		
		// safety measure if status was deleted
		if($selected_statuses == '') {
			$selected_statuses = '0';
		}
		
		$status_query = $DB->query("SELECT status FROM exp_statuses WHERE status_id IN ($selected_statuses)");
		
		// sort depends on preferences
		$pref_query = $DB->query("SELECT sort_order FROM exp_reeorder_prefs WHERE weblog_id = $weblog_id");
		
		if ($pref_query->num_rows == 0)
		{
			$sort = 'DESC';
		} else {
			$sort = $pref_query->row['sort_order'];
		}
		
		// only update entries with the correct status
		$sql = "SELECT wd.entry_id, $field_id, status 
				FROM exp_weblog_data wd
				JOIN exp_weblog_titles wt ON wt.entry_id = wd.entry_id 
				WHERE wd.weblog_id = '".$IN->GBL('weblog_id')."' ";
				
		
		// add selected status if any
		if ($status_query->num_rows > 0)
		{
			$stats = "''";
			foreach ($status_query->result as $stat_row)
			{
				$stats .= ",'".$stat_row['status']."'";
			}
			
			$sql .= "AND status IN ($stats) ";
		}
		
		$sql .= "ORDER BY $field_id $sort";
		
		$entries_query = $DB->query($sql);
		
		if ($entries_query->row['entry_id'] == $IN->GBL('entry_id') && $IN->GBL('order') != 'down' && !$IN->GBL('order_id'))
		{
			return $this->list_entries('');
			exit;
		}
		
		$flag	= '';
		$i		= 1;
		$items	= array();
		
		foreach ($entries_query->result as $row)
		{
			if ($IN->GBL('entry_id') == $row['entry_id'])
			{
				if (!$IN->GBL('order_id')) {
					$flag = ($IN->GBL('order') == 'down') ? $i+1 : $i-1;
				} else {
					$flag = $order_id;
				}
			}
			else
			{
				$items[] = $row['entry_id'];
			}
			$i++;
		}
		
		array_splice($items, ($flag -1), 0, $IN->GBL('entry_id'));
		
		// Update order
		$i = 1;
		if ($sort != 'ASC') {
			$i = count($items);
		}
		foreach ($items as $val)
		{
			$i_pad = str_pad($i, 4, "0", STR_PAD_LEFT);
			$DB->query("UPDATE exp_weblog_data SET $field_id = '$i_pad' WHERE entry_id = '$val'");
			
			if ($sort != 'ASC') {
				$i--;
			} else {
				$i++;
			}
		}
		
		// clear all caches
		$FNS->clear_caching('all');
		
		$FNS->redirect($return);
		
	}
	// END
	
	
	// ----------------------------------------
	//	Preferences page
	// ----------------------------------------
	
	function preferences($msg='')
	{
		global $DB, $DSP, $FNS, $LANG, $PREFS, $SESS;
		
		// -------------------------------------------------------
		//	HTML Title and Navigation Crumblinks
		// -------------------------------------------------------
		
		$DSP->title = $LANG->line('ttl_reeorder');
		
		$DSP->crumb = $DSP->anchor(BASE.AMP.'C=modules'.AMP.'M=reeorder', $LANG->line('crumb_reeorder'));
		
		$DSP->crumb .= $DSP->crumb_item($LANG->line('preferences'));
		
		// -------------------------------------------------------
		//	Message, if any
		// -------------------------------------------------------
		
		if ($msg != '')
		{
			$DSP->body .= $DSP->qdiv('successBox', $DSP->qdiv('success', $msg));
		}
		
		if ($SESS->userdata['group_id'] != 1) {
			$DSP->body .= $DSP->error_message($LANG->line('unauthorized_access'));
			return;
		}
		
		// -------------------------------------------------------
		//	Table and Table Headers
		// -------------------------------------------------------
		
		$DSP->body .= $DSP->qdiv('tableHeading', $LANG->line('preferences'));
		
		$DSP->body .= $DSP->div('box');
		
		$DSP->body .= $DSP->qdiv('defaultBold', $LANG->line('choose_field'));
		$DSP->body .= $DSP->qdiv('defaultBold', $LANG->line('field_warning'));
		$DSP->body .= $DSP->div_c(); // box 
		
		$DSP->body .= $DSP->form('C=modules'.AMP.'M=reeorder'.AMP.'P=update_prefs', 'update_prefs');
		
		$DSP->body .= $DSP->table_open(array('class' => 'tableBorder', 'width' => '100%'));
		
		$DSP->body .= $DSP->table_row(array(
											array(
												  'text'  => $LANG->line('weblog_id'),
												  'class' => 'tableHeadingAlt',
												  'width' => '1%;'
												  ),
											array(
												  'text'  =>  ucwords($PREFS->ini('weblog_nomenclature')) . $LANG->line('weblog_name'),
												  'class' => 'tableHeadingAlt',
												  'width' => '25%'
												  ),
  											array(
  												  'text'  => $LANG->line('custom_field'),
  												  'class' => 'tableHeadingAlt',
  												  'width' => '25%'
  												  ),
											array(
												  'text'  => $LANG->line('status'),
												  'class' => 'tableHeadingAlt',
												  'width' => '25%'
												  ),
											array(
												  'text'  => $LANG->line('sort_order'),
												  'class' => 'tableHeadingAlt',
												  'width' => '25%'
												  )
											)
									  );
		
		// -------------------------------------------------------
		//	Display Available Weblogs
		// -------------------------------------------------------
		
		// only fetch weblogs assigned to current user
		$assigned_weblogs = $FNS->fetch_assigned_weblogs();
		
		// -------------------------------------------------------
		// Declare Form
		// -------------------------------------------------------
		
		$weblog_query = $DB->query("SELECT weblog_id, blog_name, blog_title, status_group FROM exp_weblogs WHERE weblog_id IN ('".implode("','", $assigned_weblogs)."')");
		
		$i = 0;
		foreach ($weblog_query->result as $row)
		{
			$weblog_id = $row['weblog_id'];
			$status_group = $row['status_group'];
			
			$status_query = $DB->query("SELECT status_id, status 
										FROM exp_statuses 
										WHERE group_id = $status_group 
										ORDER BY status_order");
			
			$selected_statuses_query = $DB->query("SELECT status 
										FROM exp_reeorder_prefs 
										WHERE weblog_id = $weblog_id");
			
			$selected_statuses = '';
			if ($selected_statuses_query->num_rows > 0)
			{
				$selected_statuses = $selected_statuses_query->row['status'];
			}
			
			// safety measure if status was deleted
			if($selected_statuses == '') {
				$selected_statuses = '0';
			}
			
			$status_selections = explode(',',$selected_statuses);
			
			$statuses = '';
			foreach ($status_query->result as $stat_row)
			{
				$selected = 0;
				if (in_array($stat_row['status_id'], $status_selections))
				{
					$selected = 1;
				}
				$statuses .= '<label>'.$DSP->input_checkbox('status_'.$row['weblog_id'].'[]', $stat_row['status_id'], $selected).NBS.$stat_row['status'].'</label><br />';
			}
			
			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
			
			$DSP->body .= $DSP->table_row(array(
												array(
													  'text'  => $DSP->qdiv('default', $row['weblog_id']),
													  'class' => $style
													  ),
												array(
													  'text'  => $DSP->qdiv('defaultBold', $row['blog_title']),
													  'class' => $style
													  ),
  													array(
													  'text'  => $DSP->qdiv('default', "<select name=\"reeorder_row_".$row['weblog_id']."\" class=\"select\" style=\"width: 175px;\" onchange=\"if(this.selectedIndex != 0)confirm('".$LANG->line('selected_field_warning_1'). "\\n" .$LANG->line('selected_field_warning_2')."');\">\n\t".$this->create_custom_field_menu($row['weblog_id']).$DSP->input_select_footer()),
													  'class' => $style,
													  'width' => 'auto;padding-top:0;padding-bottom:0'
													  ),
												array(
													  'text'  => $DSP->qdiv('default', $statuses),
													  'class' => $style,
													  'width' => 'auto;padding-top:0;padding-bottom:0'
													  ),
												array(
													  'text'  => $DSP->qdiv('default', "<select name=\"sort_order_".$row['weblog_id']."\" class=\"select\" style=\"width: 175px;\">\n\t".$this->create_sort_order_menu($row['weblog_id']).$DSP->input_select_footer()),
													  'class' => $style,
													  'width' => 'auto;padding-top:0;padding-bottom:0'
													  )
												)
										  );
		}
		
		// -------------------------------------------------------
		//	Close Table and Output to $DSP->body
		// -------------------------------------------------------
		
		$DSP->body .= $DSP->table_close();
		
		$DSP->body .= $DSP->div('box', '', '', '', 'style="margin:0 0 3px 0; padding:10px;"');
		$DSP->body .= $DSP->qdiv('default', $DSP->input_submit($LANG->line('bttn_save_prefs')));
		$DSP->body .= $DSP->div_c(); //box
		
		$DSP->body .= $DSP->form_c();
		
		$DSP->body .= $DSP->qdiv('box default', $LANG->line('link_documentation'));
		
	}
	// END
	
	
	// ----------------------------------------
	//	Update Preferences
	// ----------------------------------------
	
	function update_prefs()
	{
		global $DB, $LANG, $FNS;
		
		// only fetch weblogs assigned to current user
		$assigned_weblogs = $FNS->fetch_assigned_weblogs();
		
		$data = array();
		foreach ($assigned_weblogs as $val)
		{
			$data['weblog_id'] = $val;
			$data['field_id'] = $_POST['reeorder_row_'.$val];
			if (isset($_POST['status_'.$val])) {
				$data['status'] = implode(',',$_POST['status_'.$val]);
			}
			$data['sort_order'] = $_POST['sort_order_'.$val];
			$DB->query("DELETE FROM exp_reeorder_prefs WHERE weblog_id = '".$val."' ");
			$DB->query($DB->insert_string('exp_reeorder_prefs', $data));
		}
		
		return $this->preferences($LANG->line('prefs_updated'));
	}
	//--------------------------------------
	// Get Sort Order
	//--------------------------------------
	
	function get_sort_order($weblog_id)
	{
		global $DB, $IN, $DSP, $LANG;
		
		$custom_field_id_query = $DB->query("SELECT sort_order FROM exp_reeorder_prefs WHERE weblog_id = '$weblog_id'");
		
		if ($custom_field_id_query->num_rows == 0)
		{
			return;
		}
		
		$custom_field_id = 'field_id_'.$custom_field_id_query->row['field_id'];
		return $custom_field_id;
		
	}
	// END
	
	
	//--------------------------------------
	// Get ID of 'reeorder_module' field
	//--------------------------------------
	
	function get_field_id($weblog_id)
	{
		global $DB, $IN, $DSP, $LANG;
		
		$custom_field_id_query = $DB->query("SELECT field_id FROM exp_reeorder_prefs WHERE weblog_id = '$weblog_id'");
		
		if ($custom_field_id_query->num_rows == 0)
		{
			return;
		}
		
		$custom_field_id = 'field_id_'.$custom_field_id_query->row['field_id'];
		return $custom_field_id;
		
	}
	// END
	
	
	//--------------------------------------
	// Create order dropdown menu
	//--------------------------------------
	
	function create_drop_menu($arr,$field)
	{
		global $DSP;
		
		$i = 1;
		$dropdown_menu = '';
		foreach ($arr as $val) {
			$trimmed_val = ltrim($val, '0');
			if ($field == $val) {
				$dropdown_menu .= $DSP->input_select_option($i, $trimmed_val, 'y');
			} else {
				$dropdown_menu .= $DSP->input_select_option($i, $trimmed_val);
			}
			$i++;
		}
		
		return $dropdown_menu;
	}
	// END
	
	
	//--------------------------------------
	// Create custom field dropdown menu
	//--------------------------------------
	
	function create_custom_field_menu($weblog_id)
	{
		global $DB, $DSP;
		
		// get field_group for this weblog
		$field_group_query = $DB->query("SELECT field_group 
										FROM exp_weblogs 
										WHERE weblog_id = '$weblog_id'");
		
		$field_group_id = $field_group_query->row['field_group'];
		
		// use group_id to get custom field_id
		$custom_field_id_query = $DB->query("SELECT field_id, field_name, field_label, group_id
											FROM exp_weblog_fields 
											WHERE group_id = '$field_group_id'");
		
		$custom_field_id = $DB->query("SELECT * FROM exp_reeorder_prefs WHERE weblog_id = '$weblog_id'");
		if ($custom_field_id->num_rows == 0)
		{
			$cf_id = '';
		} else {
			$cf_id = $custom_field_id->row['field_id'];
		}
		
		$dropdown_menu = $DSP->input_select_option(0, '---');
		foreach ($custom_field_id_query->result as $row) {
			
			if ($row['field_id'] == $cf_id) {
				$dropdown_menu .= $DSP->input_select_option($row['field_id'], '('.$row['field_id'].') ' .$row['field_name'], 'y');
			} else {
				$dropdown_menu .= $DSP->input_select_option($row['field_id'], '('.$row['field_id'].') ' .$row['field_name']);
			}
			
		}
		
		return $dropdown_menu;
	}
	// END
	
	
	//--------------------------------------
	// Create Sort Order dropdown menu
	//--------------------------------------
	
	function create_sort_order_menu($weblog_id)
	{
		global $DB, $DSP;
		
		$dropdown_menu = '';
		
		$sort_order_query = $DB->query("SELECT * FROM exp_reeorder_prefs WHERE weblog_id = '$weblog_id'");
		
		if ($sort_order_query->num_rows == 0)
		{
			$dropdown_menu .= $DSP->input_select_option('ASC', 'Ascending');
			$dropdown_menu .= $DSP->input_select_option('DESC', 'Descending', 'y');
			return $dropdown_menu;
		}
		
		$sort_order = $sort_order_query->row['sort_order'];
		
		if ($sort_order == 'ASC' || $sort_order == '') {
			$dropdown_menu .= $DSP->input_select_option('ASC', 'Ascending', 'y');
			$dropdown_menu .= $DSP->input_select_option('DESC', 'Descending');
		} else {
			$dropdown_menu .= $DSP->input_select_option('ASC', 'Ascending');
			$dropdown_menu .= $DSP->input_select_option('DESC', 'Descending', 'y');
		}
		
		return $dropdown_menu;
	}
	// END
	
	
}
// END CLASS
?>