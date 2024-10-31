<?php
/*
Plugin Name: Prune Users
Plugin URI: http://www.justechn.com/wordpress-plugins/prune-users
Description: Prune users that have no posts or comments. Can filter users by age of registration
Author: Ryan McLaughlin
Version: 1.1
Author URI: http://www.justechn.com/
*/

// Special thanks to Dagon Design. This plugin is based off their plugin. To see their plugin go here http://www.dagondesign.com/articles/clean-up-users-plugin-for-wordpress/

/*  Copyright 2010

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

function pu_add_options_pages() {
	if (function_exists('add_options_page')) {
		add_options_page("Prune Users", 'Prune Users', 8, __FILE__, 'pu_options_page');
	}
}

function pu_options_page() {
	// user roles
	$skip_admins = TRUE;
	$skip_editors = TRUE;
	$skip_authors = TRUE;
	$skip_contributors = TRUE;
	
	global $wpdb;
	$tp = $wpdb->prefix;
	
	$result = "";
	
	if (isset($_POST['info_update'])) {
		
		// start processing
		
		?><div id="message" class="updated fade"><p><strong><?php 
		
		echo "Action Complete - View Results Below";
		
		?></strong></p></div><?php
		
		$result = '';
		$pu_pruneOption = (int)$_POST['pu_pruneOption'];
		$pu_preview = (bool)$_POST['pu_preview'];
		$pu_showUserNames = (bool)$_POST['pu_showUserNames'];
		$pu_sql = (bool)$_POST['pu_sql'];
		$pu_age = (bool)$_POST['pu_age'];
		$pu_ageNum = (int)$_POST['pu_ageNum'];
		$pu_ageSelect = (int)$_POST['pu_ageSelect'];
		
		// delete users that don't have any comments or posts.
		if ($pu_pruneOption == 1) {
			$skip_check = '';
			if ($skip_admins) $skip_check .= " AND LOCATE('administrator', um.meta_value) = 0 ";
			if ($skip_editors) $skip_check .= " AND LOCATE('editor', um.meta_value) = 0 ";
			if ($skip_authors) $skip_check .= " AND LOCATE('author', um.meta_value) = 0 ";
			if ($skip_contributors) $skip_check .= " AND LOCATE('contributor', um.meta_value) = 0 ";
			
			// list of users with no posts and no comments
			$sql = "SELECT u.ID, u.user_login
				FROM  {$tp}users u 
					LEFT JOIN {$tp}posts p ON u.ID = p.post_author 
					LEFT JOIN {$tp}comments c ON u.ID  = c.user_id 
					LEFT JOIN {$tp}usermeta um ON u.ID  = um.user_id 
				WHERE p.post_author is NULL 
				AND c.user_id is NULL 
				AND um.meta_key = 'wp_capabilities' 
				{$skip_check}";
			
			// if a date is specified, make sure you use it
			if ($pu_age) {
				$numDays = 0;
				// days
				if($pu_ageSelect == 1) {
					$numDays = $pu_ageNum;
				}
				// weeks
				elseif($pu_ageSelect == 2) { 
					$numDays = $pu_ageNum*7;
				}
				// months
				elseif($pu_ageSelect == 3) {
						$date1 = mktime(0, 0, 0, date("m")+$pu_ageNum, date("d"), date("Y"));
						$date2 = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
						$numDays = ($date1-$date2)/86400;
				}
				// years
				elseif($pu_ageSelect == 4) {
					$numDays = $pu_ageNum*365;
				}
				
				$sql .= " AND DATEDIFF (CURDATE(), u.user_registered) > {$numDays} ";
			}
			
			$userlist = (array)$wpdb->get_results($sql);
			
			if (!$pu_preview) {
				$result = 'Users deleted: ' . count($userlist);
				foreach ($userlist as $u) {
					wp_delete_user($u->ID);
					if ($pu_showUserNames) $result .= $u->user_login."<br />";
				}
				
			}
			else {
				$result = 'Users who will be deleted: ' . count($userlist)."<br />";
				if ($pu_showUserNames) {
					foreach ($userlist as $u) {
						$result .= $u->user_login."<br />";
					}
				}
			}
			
			if ($pu_sql) {
				$result = $sql."<br />".$result;
			}
		} 
		else {
			$result = 'No option selected!';
		}
		
		// end processing
	} ?>
	
	<div class=wrap>
	
	<h2>Prune Users</h2>
		
	<p>Like Prune Users? Help support it <a href="http://www.justechn.com/wordpress-plugins/prune-users">by donating to the developer</a>. This helps cover the cost of maintaining the plugin and development time toward new features. Every donation, no matter how small, is appreciated.<br /></p>
	
	<?php 
	if ($result != "") { 
		echo '<div style="border: 1px solid #888888; padding: 5px;height:200px;overflow:auto;">';
		echo '<strong>Results</strong>:<br /> ' . trim($result) . '</div>';
	} 
	?>
	
	<script type="text/javascript">
		function submitForm() {
			var form = document.pruneUserForm;
			var skip_submit = 0;
			
			if (form.pu_age.checked) {
				if (form.pu_ageNum.value <= 0) {
					alert("You have selected to skip users by date, but you didn't select a date.");
					skip_submit = 1;
				}
			}
			
			if (skip_submit == 0) {
				// if the preview checkbox is not checked make sure they really want to delete the users
				if (!form.pu_preview.checked) {
					if (confirm("You are about to delete some users, this cannot be reversed. Are you sure you want to proceed?")) {
						form.submit();
					}
				}
				// if the preview checkbox is checked then proceed without confirmation
				else {
					form.submit();
				}
			}
		}
	</script>
	
	<form enctype="multipart/form-data" method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>" name="pruneUserForm">
	<input type="hidden" name="info_update" id="info_update" value="true" />
	
	<div style="padding: 0 0 15px 12px;">
		<?php print $formatinfo; ?>
		<h3>Options</h3>
		<ul>
			<li><input type="radio" name="pu_pruneOption" id="pu_pruneOption" value="1" checked /> Delete users with no comments or posts
			<li><input type="checkbox" name="pu_age" id="pu_age" <?php if ($pu_age) { ?> checked <?php } ?> />
				Skip users that registered less than <input type="input" name="pu_ageNum" id="pu_ageNum" size="1" value="<?php echo $pu_ageNum; ?>"/> 
				<select id="pu_ageSelect" name="pu_ageSelect">
					<option value="1" <?php if ($pu_ageSelect == 1) { ?> selected <?php } ?>>Day(s)</option>
					<option value="2" <?php if ($pu_ageSelect == 2) { ?> selected <?php } ?>>Week(s)</option>
					<option value="3" <?php if ($pu_ageSelect == 3) { ?> selected <?php } ?>>Months(s)</option>
					<option value="4" <?php if ($pu_ageSelect == 4) { ?> selected <?php } ?>>Year(s)</option>
				</select> ago</li>
			<li><input type="checkbox" name="pu_showUserNames" id="pu_showUserNames" <?php if ($pu_showUserNames) { ?> checked <?php } ?> /> Show Usernames</li>
			<li><input type="checkbox" name="pu_preview" id="pu_preview" <?php if ($pu_preview) { ?> checked <?php } ?> /> Preview (don't actually delete anyone)</li>
			<li><input type="checkbox" name="pu_sql" id="pu_sql" <?php if ($pu_sql) { ?> checked <?php } ?> /> Show SQL (useful in debugging)</li>
		</ul>
	</div>
	
	<div class="submit">
		<input type="button" name="info_update" value="Submit" onclick="submitForm()" />
	</div>
	</form>
	</div><?php
}

add_action('admin_menu', 'pu_add_options_pages');

?>