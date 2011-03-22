<?php
/**
 * Traq
 * Copyright (C) 2009-2011 Jack Polgar
 * 
 * This file is part of Traq.
 * 
 * Traq is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 3 only.
 * 
 * Traq is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Traq. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Used to get the value of the specified setting.
 * 
 * @param string $setting The setting...
 */
function settings($setting)
{
	global $CACHE;
	
	// Check if the setting has already been fetched
	// and return it if it has.
	if(isset($CACHE['settings'][$setting])) return $CACHE['settings'][$setting];
	
	// Looks like the setting isn't in the cache,
	// lets fetch it now...
	$result = Meridian::$db->select()->from('settings')->where(array('setting'=>$setting))->exec()->fetchArray();
	$CACHE['settings'][$setting] = $result['value'];
	
	return $CACHE['settings'][$setting];
}

/**
 * Shortcut for the databases real_escape_string function.
 */
function rescape($string)
{
	return Meridian::$db->real_escape_string($string);
}

/**
 * Used to get an alternate background class.
 * 
 * @param string $even Even class color.
 * @param string $odd Odd class color.
 */
function altbg($even='even',$odd='odd')
{
	static $bg;
	
	if($bg == $odd) return $bg = $even;
	else return $bg = $odd;
}

/**
 * Used to easily add breadcrumbs.
 * 
 * @param string $url The URL.
 * @param string $label The Label.
 */
function addcrumb($url,$label)
{
	global $breadcrumbs;
	
	$breadcrumbs[] = array('url'=>$url, 'label'=>$label);
}

/**
 * Used to display an error message.
 * 
 * @param string $title Error title.
 * @param string $message Error message.
 */
function error($title,$message)
{
	die("<blockquote style=\"border:2px solid darkred;padding:5px;background:#f9f9f9;font-family:arial; font-size: 14px;\"><h1 style=\"margin:0px;color:#000;border-bottom:1px solid #000;margin-bottom:10px;\">".$title." Error</h1><div style=\"padding: 0;\">".$message."</div><div style=\"color:#999;border-top:1px solid #000;margin-top:10px;font-size:small;padding-top:2px;\">Traq ".TRAQVER." &copy; 2009-".date("Y")." Jack Polgar</div></blockquote>");
}

/**
 * Used to format text.
 * 
 * @param string $text The text to format.
 * @return string
 */
function formattext($text, $disablehtml=false)
{
	global $textile;
	
	// Disable HTML
	if($disablehtml) $text = str_replace('<',"&lt;",$text);
	
	// Check if we're on a project page...
	if(isset(Meridian::app()->project['id']))
	{
		// [ticket:x] to ticked URL
		$text = preg_replace("/\[ticket:(.*?)\\]/is",'<a href="'.baseurl(Meridian::app()->project['slug'],'tickets/$1').'">[Ticket #$1]</a>',$text);
		
		// [[WikiPage|Text]]
		$text = preg_replace_callback('/\[\[([^\|\n\]:]+)[\|]([^\]]+)\]\]/','_interwikilinks',$text);
		// [[WikiPage]]
		$text = preg_replace_callback('/\[\[([^\|\n\]:]+)\]\]/','_interwikilinks',$text);
	}
	
	// [code]blarg[/code]
	$text = preg_replace("/\[code\](.*?)\[\/code\]/is", '<code class="prettyprint codeblock">$1</code>', $text);
	
	// Textile
	if(!isset($textile)) $textile = Load::library('Textile');
	$text = $textile->TextileThis($text);
	
	return $text;
}
function _interwikilinks($matches)
{
	$url = $matches[1];
	$text = (empty($matches[2]) ? $matches[1] : $matches[2]);
	return '<a href="'.baseurl(Meridian::app()->project['slug'],'wiki',slugit($url)).'">'.$text.'</a>';
}

/**
 * Get an array of the custom fields.
 * 
 * @return array
 */
function custom_fields()
{
	$fields = array();
	$fetch = $db->query("SELECT * FROM ".DBPF."custom_fields WHERE project_ids LIKE '%[".$project['id']."]%'");
	while($info = $db->fetcharray($fetch))
	{
		$info['code'] = str_replace(
			array(
				'%name%',
				'%value%'
			),
			array(
				'cfields['.$info['id'].']',
				'<?php if(is_array($ticket)) { echo @$ticket[\'extra\']['.$info['id'].']; } ?>'
			),
			$info['code']
		);
		$fields[] = $info;
	}
	return $fields;
}

/**
 * Returns the custom field name.
 * 
 * @return string
 */
function custom_field_name($field_id)
{
	global $db;
	
	$field = $db->fetcharray($db->query("SELECT name FROM ".DBPF."custom_fields WHERE id='".$db->res($field_id)."' LIMIT 1"));
	return $field['name'];
}

/**
 * Creates a slug / URI safe string.
 *
 * @param string $text The string to change.
 * @return string
 */
function slugit($text)
{
	$text = strip_tags($text);
	$text = remove_accents($text);
	$text = strtolower($text);
	$text = preg_replace('/&.+?;/', '', $text);
	$text = preg_replace('/[^%a-z0-9 _-]/', '', $text);
	$text = preg_replace('/\s+/', '_', $text);
	$text = preg_replace('|-+|', '-', $text);
	$text = trim($text, '_');
	return $text;
}

/**
 * Gets the specified locale string for the set language.
 *
 * @param string $string String name/key
 * @param mixed $vars
 * @return string
 */
function l($string, $vars=array())
{
	global $lang;
	
	// Check if the string exists
	if(!isset($lang[$string])) return '['.$string.']';
	
	// If the string in the $lang array is true, just return
	// what we have.
	if($lang[$string] === true) return $string;
	
	// Get the locale string
	$string = $lang[$string];
	
	// Check if the $vars is an array or use the function args.
	if(!is_array($vars)) $vars = array_slice(func_get_args(),1);
	
	// Loop through the vars and replace the the {x} stuff
	foreach($vars as $var)
	{
		if(!isset($v)) $v = 0;
		++$v;
		$string = str_replace('{'.$v.'}', $var, $string);
	}
	
	// Now return it...
	return $string;
}

/**
 * Shortcut to echo a localised string.
 * 
 * @param string $string
 * @param mixed $vars
 */
function _l($string, $vars=array())
{
	if(!is_array($vars)) $vars = array_slice(func_get_args(),1);
	echo l($string, $vars);
}

/**
 * Function to make source code safe in a template.
 *
 * @param string $code The code.
 * @return string
 */
function source_code($code, $disablehtml=true)
{
	return ($disablehtml ? htmlspecialchars($code) : $code);
}

/**
 * Check if the supplied string is a project.
 *
 * @param string $string String to check if a project exists with that slug.
 * @return integer
 * @since 0.1
 */
function is_project($string)
{
	return Meridian::$db->select(array('slug'))->from('projects')->where(array('slug'=>rescape($string)))->exec()->numRows();
}

/**
 * Check's if the project has a repository.
 *
 * @param integer $project_id The project ID.
 * @return integer
 */
function has_repo($project_id='')
{
	global $project,$db;
	if(empty($project_id)) $project_id = $project['id'];
	
	return $db->numrows($db->query("SELECT id FROM ".DBPF."repositories WHERE project_id='".$db->res($project_id)."' LIMIT 1"));
}

/**
 * Fetches the projects repositories.
 * 
 * @param integer $project_id The project ID.
 * @return array
 */
function project_repos($project_id='')
{
	global $project,$db;
	if(empty($project_id)) $project_id = $project['id'];
	
	$repos = array();
	$fetch = $db->query("SELECT id,name,slug FROM ".DBPF."repositories WHERE project_id='".$project_id."' ORDER BY name ASC");
	while($repo = $db->fetcharray($fetch))
		$repos[] = $repo;
	
	return $repos;
}

/**
 * Used to easy execute a condition.
 * 
 * @param condition $condition The condition to check.
 * @param mixed $true Returned if condition is true.
 * @param mixed $false Returned if condition is false.
 * @return mixed
 */
function iif($condition, $true, $false='')
{
	return ($condition ? $true : $false);
}

/**
 * Used to create the sort URL for the tickets listing.
 * 
 * @return string
 */
function ticket_sort_url($field)
{
	$_SERVER['QUERY_STRING'] = str_replace(array('&sort='.@$_REQUEST['sort'],'&order='.@$_REQUEST['order'],'sort='.@$_REQUEST['sort'],'order='.@$_REQUEST['order']),'',$_SERVER['QUERY_STRING']);
	return '?'.($_SERVER['QUERY_STRING'] != '' ? $_SERVER['QUERY_STRING'].'&' : '').'sort='.$field.'&order='.(@$_REQUEST['order'] == 'desc' ? 'asc' : 'desc');
}

/**
 * Fetches the requred type of ticket status options in an array.
 * 
 * @param integer $getstatus Status type to fetch (1 for open, 0 for closed)
 * @return array
 */
function ticket_status_list($getstatus=1)
{
	global $db;
	
	$status = array();
	$fetch = $db->query("SELECT * FROM ".DBPF."ticket_status ".(is_numeric($getstatus) ? "WHERE status='".$getstatus."'" :'')." ORDER BY name ASC");
	while($info = $db->fetcharray($fetch))
		$status[] = $info;
	
	($hook = FishHook::hook('function_ticket_statuses')) ? eval($hook) : false;
	return $status;
}

/**
 * Fetches the Ticket Types specified in the AdminCP.
 * 
 * @return array
 */
function ticket_types()
{
	global $db;
	
	$types = array();
	$fetch = $db->query("SELECT * FROM ".DBPF."ticket_types ORDER BY id ASC");
	while($info = $db->fetcharray($fetch))
		$types[] = $info;
	
	($hook = FishHook::hook('function_ticket_types')) ? eval($hook) : false;
	return $types;
}

/**
 * Fetches the Ticket Priorities specified in the AdminCP.
 * 
 * @return array
 */
function ticket_priorities()
{
	global $db;
	
	$priorities = array();
	$fetch = $db->query("SELECT * FROM ".DBPF."priorities ORDER BY id DESC");
	while($info = $db->fetcharray($fetch))
		$priorities[] = $info;
	
	($hook = FishHook::hook('function_ticket_priorities')) ? eval($hook) : false;
	return $priorities;
}

/**
 * Fetches the Ticket Severities specified in the AdminCP.
 * 
 * @return array
 */
function ticket_severities()
{
	global $db;
	
	$severities = array();
	$fetch = $db->query("SELECT * FROM ".DBPF."severities ORDER BY id ASC");
	while($info = $db->fetcharray($fetch))
		$severities[] = $info;
	
	($hook = FishHook::hook('function_ticket_severities')) ? eval($hook) : false;
	return $severities;
}

/**
 * Gets the ticket status.
 * 
 * @return array
 */
function ticket_status($status_id)
{
	global $db;
	$status = $db->queryfirst("SELECT * FROM ".DBPF."ticket_status WHERE id='".$db->res($status_id)."' LIMIT 1");
	return $status['name'];
}

/**
 * Gets the ticket type.
 * 
 * @return array
 */
function ticket_type($type_id)
{
	global $db;
	$status = $db->queryfirst("SELECT * FROM ".DBPF."ticket_types WHERE id='".$db->res($type_id)."' LIMIT 1");
	return $status['name'];
}

/**
 * Gets the ticket priority.
 * 
 * @return array
 */
function ticket_priority($priority_id)
{
	global $db;
	$priority = $db->queryfirst("SELECT * FROM ".DBPF."priorities WHERE id='".$db->res($priority_id)."' LIMIT 1");
	return $priority['name'];
}

/**
 * Gets the ticket severity.
 * 
 * @return array
 */
function ticket_severity($severity_id)
{
	global $db;
	$severity = $db->queryfirst("SELECT * FROM ".DBPF."severities WHERE id='".$db->res($severity_id)."' LIMIT 1");
	return $severity['name'];
}

/**
 * Returns an array of the ticket columns that can be displayed on the view tickets page.
 * 
 * @return array
 */
function ticket_columns()
{
	$columns = array(
		'ticket',
		'summary',
		'status',
		'owner',
		'type',
		'severity',
		'component',
		'milestone',
		'version',
		'assigned_to',
		'updated'
	);
	($hook = FishHook::hook('function_ticket_columns')) ? eval($hook) : false;
	return $columns;
}

/**
 * Returns an array of ticket filters
 * 
 * @return array
 */
function ticket_filters()
{
	$filters = array(
		'component',
		'milestone',
		'version',
		'status',
		'type',
		'severity',
		'priority',
		'owner',
		'summary',
		'description'
	);
	($hook = FishHook::hook('function_ticket_filters')) ? eval($hook) : false;
	return $filters;
}

/**
 * Fetches the project milestones.
 * 
 * @return array
 */
function project_milestones($project_id=NULL)
{
	global $project, $db;
	$project_id = ($project_id == NULL ? $project['id'] : $project_id);
	
	$milestones = array();
	$fetch = $db->query("SELECT * FROM ".DBPF."milestones WHERE project_id='".$db->res($project_id)."' ORDER BY displayorder ASC");
	while($info = $db->fetcharray($fetch))
		$milestones[] = $info;
	
	($hook = FishHook::hook('function_project_milestones')) ? eval($hook) : false;
	return $milestones;
}

/**
 * Fetches the project verions.
 * 
 * @return array
 */
function project_versions($project_id=NULL)
{
	global $project, $db;
	$project_id = ($project_id == NULL ? $project['id'] : $project_id);
	
	$versions = array();
	$fetch = $db->query("SELECT * FROM ".DBPF."versions WHERE project_id='".$db->res($project_id)."' ORDER BY version ASC");
	while($info = $db->fetcharray($fetch))
		$versions[] = $info;
	
	($hook = FishHook::hook('function_project_verions')) ? eval($hook) : false;
	return $versions;
}

/**
 * Fetches the project components.
 * 
 * @return array
 */
function project_components($project_id=NULL)
{
	global $project, $db;
	$project_id = ($project_id == NULL ? $project['id'] : $project_id);
	
	$components = array();
	$fetch = $db->query("SELECT * FROM ".DBPF."components WHERE project_id='".$db->res($project_id)."' ORDER BY name ASC");
	while($info = $db->fetcharray($fetch))
		$components[] = $info;
	
	($hook = FishHook::hook('function_project_components')) ? eval($hook) : false;
	return $components;
}

/**
 * Fetches the project managers.
 * 
 * @return array
 */
function project_managers($project_id=NULL)
{
	global $project, $db;
	$project_id = ($project_id == NULL ? $project['id'] : $project_id);
	
	if(!isset($project))
	{
		$info = $db->queryfirst("SELECT managers FROM ".DBPF."projects WHERE id='".$db->res($project_id)."' LIMIT 1");
		$managers = array();
		$manager_ids = explode(',',$info['managers']);
	}
	else
	{
		$manager_ids = $project['managers'];
	}
	
	
	foreach($manager_ids as $id)
		$managers[] = $db->queryfirst("SELECT id,username,name FROM ".DBPF."users WHERE id='".$db->res($id)."' LIMIT 1");
	
	($hook = FishHook::hook('function_project_managers')) ? eval($hook) : false;
	
	return $managers;
}

/**
 * Checks if the user is subscribed/watching something.
 *
 * @param string $type The type of subscription (project,ticket,etc).
 * @param mixed $data The data for the subscription.
 * @return bool
 */
function is_subscribed($type,$data='')
{
	global $db,$user,$project;
	
	if($db->numrows($db->query("SELECT id FROM ".DBPF."subscriptions WHERE type='".$type."' AND user_id='".$user->info['id']."' AND project_id='".$project['id']."' AND data='".$data."' LIMIT 1")))
	{
		($hook = FishHook::hook('function_is_subscribed')) ? eval($hook) : false;
		return true;
	}
	return false;
}

/**
 * Adds a subscription for the user.
 *
 * @param string $type The type of subscription.
 * @param mixed $data The subscription data.
 */
function add_subscription($type,$data='')
{
	global $db,$user,$project;
	
	$db->query("INSERT INTO ".DBPF."subscriptions
	(type,user_id,project_id,data)
	VALUES(
	'".$type."',
	'".$user->info['id']."',
	'".$project['id']."',
	'".$data."'
	)");
	
	($hook = FishHook::hook('function_add_subscription')) ? eval($hook) : false;
}

/**
 * Removes a subscription for the user.
 *
 * @param string $type The type of subscription.
 * @param mixed $data The subscription data.
 */
function remove_subscription($type,$data='')
{
	global $db,$user,$project;
	
	$db->query("DELETE FROM ".DBPF."subscriptions WHERE type='".$type."' AND user_id='".$user->info['id']."' AND project_id='".$project['id']."' AND data='".$data."' LIMIT 1");
	
	($hook = FishHook::hook('function_remove_subscription')) ? eval($hook) : false;
}

/**
 * Adds a subscription for the user.
 *
 * @param string $type The type of subscription.
 * @param array $data The subscription data.
 */
function send_notification($type,$data=array())
{
	global $project, $db;
	
	static $sent = array();
	
	// Project notification
	if($type == 'project')
	{
		// Ticket Created
		if($data['type'] == 'ticket_created')
		{
			$fetch = $db->query("SELECT ".DBPF."subscriptions.*,".DBPF."users.username,".DBPF."users.email FROM ".DBPF."subscriptions JOIN ".DBPF."users ON (".DBPF."users.id = ".DBPF."subscriptions.user_id) WHERE type='project' AND project_id='".$project['id']."'");
			while($info = $db->fetcharray($fetch))
			{
				// Check to make sure we havn't already emailed the user.
				if(in_array($info['username'],$sent)) continue;
				$sent[] = $info['username'];
				
				mail($info['email'],
					l('x_x_notification',settings('title'),$project['name']),
					l('notification_'.$data['type'],$info['username'],$project['name'],$data['tid'],$data['summary'],$data['url']),
					"From: ".settings('title')." <noreply@".$_SERVER['HTTP_HOST'].">"
				);
			}
		}
		// Milestone Completed
		elseif($data['type'] == 'milestone_completed')
		{
			$fetch = $db->query("SELECT ".DBPF."subscriptions.*,".DBPF."users.username,".DBPF."users.email FROM ".DBPF."subscriptions JOIN ".DBPF."users ON (".DBPF."users.id = ".DBPF."subscriptions.user_id) WHERE type='project' AND project_id='".$data['project_id']."' AND data='".$data['project_id']."'");
			while($info = $db->fetcharray($fetch))
			{
				// Check to make sure we havn't already emailed the user.
				if(in_array($info['username'],$sent)) continue;
				$sent[] = $info['username'];
				
				mail($info['email'],
					l('x_x_notification',settings('title'),$project['name']),
					l('notification_'.$data['type'],$info['username'],$project['name'],$data['name'],$data['url']),
					"From: ".settings('title')." <noreply@".$_SERVER['HTTP_HOST'].">"
				);
			}
		}
	}
	// Ticket notification
	elseif($type == 'ticket')
	{
		// Ticket Updated
		if($data['type'] == 'ticket_updated')
		{
			$fetch = $db->query("SELECT ".DBPF."subscriptions.*,".DBPF."users.username,".DBPF."users.email FROM ".DBPF."subscriptions JOIN ".DBPF."users ON (".DBPF."users.id = ".DBPF."subscriptions.user_id) WHERE type='ticket' AND project_id='".$project['id']."' AND data='".$data['id']."'");
			while($info = $db->fetcharray($fetch))
			{
				// Check to make sure we havn't already emailed the user.
				if(in_array($info['username'],$sent)) continue;
				$sent[] = $info['username'];
				
				mail($info['email'],
					l('x_x_notification',settings('title'),$project['name']),
					l('notification_'.$data['type'],$info['username'],$project['name'],$data['id'],$data['summary'],$data['url']),
					"From: ".settings('title')." <noreply@".$_SERVER['HTTP_HOST'].">"
				);
			}
		}
	}
	// Milestone notification
	elseif($type == 'milestone')
	{
		// Ticket Updated
		if($data['type'] == 'ticket_created')
		{
			$fetch = $db->query("SELECT ".DBPF."subscriptions.*,".DBPF."users.username,".DBPF."users.email FROM ".DBPF."subscriptions JOIN ".DBPF."users ON (".DBPF."users.id = ".DBPF."subscriptions.user_id) WHERE type='milestone' AND project_id='".$project['id']."' AND data='".$data['milestone']."'");
			while($info = $db->fetcharray($fetch))
			{
				// Check to make sure we havn't already emailed the user.
				if(in_array($info['username'],$sent)) continue;
				$sent[] = $info['username'];
				
				mail($info['email'],
					l('x_x_notification',settings('title'),$project['name']),
					l('notification_'.$data['type'],$info['username'],$project['name'],$data['id'],$data['summary'],$data['url']),
					"From: ".settings('title')." <noreply@".$_SERVER['HTTP_HOST'].">"
				);
			}
		}
		// Milestone Completed
		elseif($data['type'] == 'milestone_completed')
		{
			$fetch = $db->query("SELECT ".DBPF."subscriptions.*,".DBPF."users.username,".DBPF."users.email FROM ".DBPF."subscriptions JOIN ".DBPF."users ON (".DBPF."users.id = ".DBPF."subscriptions.user_id) WHERE type='milestone' AND project_id='".$data['project_id']."' AND data='".$data['id']."'");
			while($info = $db->fetcharray($fetch))
			{
				// Check to make sure we havn't already emailed the user.
				if(in_array($info['username'],$sent)) continue;
				$sent[] = $info['username'];
				
				mail($info['email'],
					l('x_x_notification',settings('title'),$project['name']),
					l('notification_'.$data['type'],$info['username'],$project['name'],$data['name'],$data['url']),
					"From: ".settings('title')." <noreply@".$_SERVER['HTTP_HOST'].">"
				);
			}
		}
	}
	($hook = FishHook::hook('function_send_notification')) ? eval($hook) : false;
}

/**
 * Used to calculate the percent of two numbers,
 * if both numbers are the same, 100(%) is returned.
 * 
 * @param integer $min Lowest number
 * @param integer $max Highest number
 * @return integer
 */
function getpercent($min,$max)
{
	if($min == $max) return 100;
	
	$calculate = ($min/$max*100);
	$split = explode('.',$calculate);
	return $split[0];
}

/**
 * Takes a timestamp and turns it into something
 * like 5 days, 2 hours ago.
 * 
 * @param integer $original Original Timestamp
 * @param integer $detailed Detailed format or not
 * @return string
 */
function timesince($original, $detailed = false)
{
	$now = time(); // Get the time right now...
	
	// Time chunks...
	$chunks = array(
		array(60 * 60 * 24 * 365, 'year', 'years'),
		array(60 * 60 * 24 * 30, 'month', 'months'),
		array(60 * 60 * 24 * 7, 'week', 'weeks'),
		array(60 * 60 * 24, 'day', 'days'),
		array(60 * 60, 'hour', 'hours'),
		array(60, 'minute', 'minutes'),
		array(1, 'second', 'seconds'),
	);
	
	// Get the difference
	$difference = ($now - $original);
	
	// Loop around, get the time since
	for($i = 0, $c = count($chunks); $i < $c; $i++)
	{
		$seconds = $chunks[$i][0];
		$name = $chunks[$i][1];
		$names = $chunks[$i][2];
		if(0 != $count = floor($difference / $seconds)) break;
	}
	
	// Format the time since
	//$since = $count." ".((1 == $count) ? $name : $names);
	$since = l('x_'.((1 == $count) ? $name : $names),$count);
	
	// Get the detailed time since if the detaile variable is true
	if($detailed && $i + 1 < $c)
	{
		$seconds2 = $chunks[$i + 1][0];
		$name2 = $chunks[$i + 1][1];
		$names2 = $chunks[$i + 1][2];
		if(0 != $count2 = floor(($difference - $seconds * $count) / $seconds2))
			$since = l('x_and_x',$since,l('x_'.((1 == $count2) ? $name2 : $names2),$count2));
	}
	
	// Return the time since
	return $since;
}

/**
 * Takes a timestamp and turns it into something
 * like 2 days, 9 hours from now.
 * 
 * @param integer $original Original Timestamp
 * @param integer $detailed Detailed format or not
 * @return string
 */
function timefrom($original, $detailed = false)
{
	$now = time(); // Get the time right now...
	
	// Time chunks...
	$chunks = array(
		array(60 * 60 * 24 * 365, 'year', 'years'),
		array(60 * 60 * 24 * 30, 'month', 'months'),
		array(60 * 60 * 24 * 7, 'week', 'weeks'),
		array(60 * 60 * 24, 'day', 'days'),
		array(60 * 60, 'hour', 'hours'),
		array(60, 'minute', 'minutes'),
		array(1, 'second', 'seconds'),
	);
	
	// Get the difference
	$difference = ($original - $now);
	
	// Loop around, get the time from
	for($i = 0, $c = count($chunks); $i < $c; $i++)
	{
		$seconds = $chunks[$i][0];
		$name = $chunks[$i][1];
		$names = $chunks[$i][2];
		if(0 != $count = floor($difference / $seconds)) break;
	}
	
	// Format the time from
	$from = l('x_'.((1 == $count) ? $name : $names),$count);
	
	// Get the detailed time from if the detaile variable is true
	if($detailed && $i + 1 < $c)
	{
		$seconds2 = $chunks[$i + 1][0];
		$name2 = $chunks[$i + 1][1];
		$names2 = $chunks[$i + 1][2];
		if(0 != $count2 = floor(($difference - $seconds * $count) / $seconds2))
			$from = l('x_and_x',$from,l('x_'.((1 == $count2) ? $name2 : $names2),$count2));
	}
	
	// Return the time from
	return $from;
}

/**
 * Removes accents from the string.
 *
 * @param string $text The string to remove accents from.
 * @return string
 */
function remove_accents($text)
{
	$from = array(
		'À','Á','Â','Ã','Ä','Å','Æ','Ç','È','É','Ê','Ë','Ì','Í',
		'Î','Ï','Ð','Ñ','Ò','Ó','Ô','Õ','Ö','Ø','Ù','Ú','Û','Ü',
		'Ý','ß','à','á','â','ã','ä','å','æ','ç','è','é','ê','ë',
		'ì','í','î','ï','ñ','ò','ó','ô','õ','ö','ø','ù','ú','û',
		'ü','ý','ÿ','A','a','A','a','A','a','C','c','C','c','C',
		'c','C','c','D','d','Ð','d','E','e','E','e','E','e','E',
		'e','E','e','G','g','G','g','G','g','G','g','H','h','H',
		'h','I','i','I','i','I','i','I','i','I','i','?','?','J',
		'j','K','k','L','l','L','l','L','l','?','?','L','l','N',
		'n','N','n','N','n','?','O','o','O','o','O','o','Œ','œ',
		'R','r','R','r','R','r','S','s','S','s','S','s','Š','š',
		'T','t','T','t','T','t','U','u','U','u','U','u','U','u',
		'U','u','U','u','W','w','Y','y','Ÿ','Z','z','Z','z','Ž',
		'ž','?','ƒ','O','o','U','u','A','a','I','i','O','o','U',
		'u','U','u','U','u','U','u','U','u','?','?','?','?','?','?'
	);
	$to = array(
		'A','A','A','A','A','A','AE','C','E','E','E','E','I','I',
		'I','I','D','N','O','O','O','O','O','O','U','U','U','U',
		'Y','s','a','a','a','a','a','a','ae','c','e','e','e','e',
		'i','i','i','i','n','o','o','o','o','o','o','u','u','u',
		'u','y','y','A','a','A','a','A','a','C','c','C','c','C',
		'c','C','c','D','d','D','d','E','e','E','e','E','e','E',
		'e','E','e','G','g','G','g','G','g','G','g','H','h','H',
		'h','I','i','I','i','I','i','I','i','I','i','IJ','ij','J',
		'j','K','k','L','l','L','l','L','l','L','l','l','l','N',
		'n','N','n','N','n','n','O','o','O','o','O','o','OE','oe',
		'R','r','R','r','R','r','S','s','S','s','S','s','S','s',
		'T','t','T','t','T','t','U','u','U','u','U','u','U','u',
		'U','u','U','u','W','w','Y','y','Y','Z','z','Z','z','Z',
		'z','s','f','O','o','U','u','A','a','I','i','O','o','U',
		'u','U','u','U','u','U','u','U','u','A','a','AE','ae','O','o'
	);
	return str_replace($from,$to,$text);
} 