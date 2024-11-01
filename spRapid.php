<?php
/*
 Plugin Name: spRapid
 Plugin URI: http://www.scriptpara.de/skripte/sprapid/
 Description: Rapidshare file manager
 Author: Sebastian Klaus
 Version: 0.1
 Author URI: http://www.scriptpara.de
 */

@ini_set('max_execution_time', 0);
@ini_set('upload_max_filesize', '200M');
@ini_set('post_max_size', '200M');
@ini_set('memory_limit', '200M');

// Define plugin Path
define('SP_PLUGIN_URI_3', '/wp-content/plugins/sprapid');

// Include the settings class
require_once(dirname(__FILE__).'/lib/spMainClass.php');
require_once(dirname(__FILE__).'/lib/spRapidshare.php');

// init the plugin
add_action('init', 'spRapidInit');

// init the plugin
function spRapidInit(){
	spMainClass::setLanguage('spRapid');

	// Create a master category and its sub-pages
	add_action('admin_menu', 'spRapidMenu');

	// Add entry to config file
	add_option('spRapid','', 'spRapid settings');

	// add javascript
	add_action('wp_head', 'spRapidHeader');

	// install the plugin
	require_once(dirname(__FILE__).'/lib/install.php');
}

// Function to deal with adding the spRapid menus
function spRapidMenu(){
	$settings = spMainClass::getSettings('spRapid');

	// Set admin as the only one who can use spRapid for security
	$allowed_group = 'manage_options';

	// Add the admin panel pages for spRapid. Use permissions pulled from above
	if (function_exists('add_menu_page')){
		add_menu_page(__('spRapid', 'spRapid'), __('spRapid', 'spRapid'), $allowed_group, 'spRapid', 'spRapid', SP_PLUGIN_URI_3.'/images/discs.gif');
	}
	if (function_exists('add_submenu_page')){
		add_submenu_page('spRapid', __('Manage files', 'spRapid'), __('Manage files', 'spRapid'), $allowed_group, 'spRapid', 'spRapid');
		add_submenu_page('spRapid', __('Upload file', 'spRapid'), __('Upload file', 'spRapid'), $allowed_group, 'spRapidUpload', 'spRapidUpload');
		add_submenu_page('spRapid', __('My account', 'spRapid'), __('My account', 'spRapid'), $allowed_group, 'spRapidAccount', 'spRapidAccount');
		add_submenu_page('spRapid', __('Settings', 'spRapid'), __('Settings', 'spRapid'), $allowed_group, 'spRapidSettings', 'spRapidSettings');
		add_submenu_page('spRapid', __('Uninstall', 'spRapid'), __('Uninstall', 'spRapid'), $allowed_group, 'spRapidUninstall', 'spRapidUninstall');
	}
}

function spRapid(){
	global $wpdb;

	$settings = spMainClass::getSettings('spRapid');

	if($_GET['action'] == 'delete'){
		$delete = new spRapidshare();
		$delete->config($settings->spRapidType, $settings->spRapidId, $settings->spRapidPass);
		$retour = $delete->deleteFiles($_GET['file']);
		if($retour == 'OK'){
			echo spMainClass::showMessage(__('File deleted'));
		}
	}

	if(spRapidshare::isUser($settings->spRapidType, $settings->spRapidId, $settings->spRapidPass)){
		// get the files for premium or collectors
		$files = new spRapidshare();
		$files->config($settings->spRapidType, $settings->spRapidId, $settings->spRapidPass);
		$order = (!empty($_GET['orderby'])) ? $_GET['orderby'] : 'filename';
		$retour = $files->getFiles($order);
		if($retour == 'NONE'){
			echo spMainClass::showMessage(__('No files listed', 'spRapid'));
			die();
		}

		// work with object
		?>
		<div class="wrap"><h2><?= __('Manage files','spRapid'); ?></h2></div>
		<a href="admin.php?page=spRapidUpload"><?= __('Upload new file', 'spRapid'); ?></a>
		<table style="margin-top: 1em;width:99%;" class="widefat">
			<thead>
				<tr>
					<th class="manage-column" scope="col"><a href="admin.php?page=spRapid&amp;orderby=filename"><?= __('Name', 'spRapid'); ?></a></th>
					<th class="manage-column" scope="col"><a href="admin.php?page=spRapid&amp;orderby=size"><?= __('Size', 'spRapid'); ?></a></th>
					<th class="manage-column" scope="col"><a href="admin.php?page=spRapid&amp;orderby=downloads"><?= __('Downloads', 'spRapid'); ?></a></th>
					<th class="manage-column" scope="col"><a href="admin.php?page=spRapid&amp;orderby=lastdownload"><?= __('Last download', 'spRapid'); ?></a></th>
					<th class="manage-column" scope="col"><?= __('Status', 'spRapid'); ?></th>
					<th class="manage-column" scope="col"></th>
				</tr>
			</thead>
			<tbody>
				<?
				$x = 1;
				foreach ($retour as $entry) {
					$class = '';
					if($x % 2 == 0){
						$class = 'alternate';
					}
					?>
					<tr class="<?= $class; ?> iedit">
						<td><?= $entry->fileName; ?></td>
						<td><?= spMainClass::getRightSize($entry->size); ?></td>
						<td><?= $entry->downloads; ?></td>
						<td><?= date('Y-m-d H:i:s',$entry->lastDownload); ?></td>
						<td><?= spRapidshare::getStatus(spRapidshare::checkFiles($entry->fileId, $entry->fileName)->status); ?></td>
						<td><a href="admin.php?page=spRapid&amp;action=delete&file=<?= $entry->fileId; ?>"><?= __('Delete', 'spRapid'); ?></a></td>
					</tr>
					<?
					$x++;
				}
				?>
			</tbody>
		</table>
		<?
	}

	// get all free user uploads from db
	$result = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.'rapid_files');
	#var_dump($result);
	#die();

	?>
	<div class="wrap"><h2><?= __('Free-user uploads','spRapid'); ?></h2></div>
	<a href="admin.php?page=spRapidUpload"><?= __('Upload new file', 'spRapid'); ?></a>
	<table style="margin-top: 1em;width:99%;" class="widefat">
		<thead>
			<tr>
				<th class="manage-column" scope="col"><a href="admin.php?page=spRapid&amp;orderby=filename"><?= __('Name', 'spRapid'); ?></a></th>
				<th class="manage-column" scope="col"><a href="admin.php?page=spRapid&amp;orderby=size"><?= __('Size', 'spRapid'); ?></a></th>
				<th class="manage-column" scope="col"><a href="admin.php?page=spRapid&amp;orderby=downloads"><?= __('Downloads', 'spRapid'); ?></a></th>
				<th class="manage-column" scope="col"><a href="admin.php?page=spRapid&amp;orderby=lastdownload"><?= __('Last download', 'spRapid'); ?></a></th>
				<th class="manage-column" scope="col"><?= __('Status', 'spRapid'); ?></th>
				<th class="manage-column" scope="col"></th>
			</tr>
		</thead>
		<tbody>
			<?
			$x = 1;
			foreach ($result as $entry) {
				$class = '';
				if($x % 2 == 0){
					$class = 'alternate';
				}
				?>
				<tr class="<?= $class; ?> iedit">
					<td><?= $entry->filename; ?></td>
					<td><?= spMainClass::getRightSize($entry->size); ?></td>
					<td><?= $entry->downloads; ?></td>
					<td><?= ($entry->lastdownload != 0) ? date('Y-m-d H:i:s',$entry->lastdownload) : __('Not yet downloaded', 'spRapid'); ?></td>
					<td><?= spRapidshare::getStatus(spRapidshare::checkFiles($entry->fileid, $entry->filename)->status); ?></td>
					<td><a href="admin.php?page=spRapid&amp;action=delete&file=<?= $entry->fileid; ?>"><?= __('Delete', 'spRapid'); ?></a></td>
				</tr>
				<?
				$x++;
			}
			?>
		</tbody>
	</table>
	<?
}

function spRapidUpload(){
	$settings = spMainClass::getSettings('spRapid');

	if (isset($_POST['hidFileID']) && $_POST['hidFileID'] != '') {
		if($_POST['hidFileID'] == 'OK'){
			echo spMainClass::showMessage(__('File successfully uploaded'));
		}else{
			echo spMainClass::showMessage($_POST['hidFileID'], 'error');
		}
	}
	?>
	<link href="<?= SP_PLUGIN_URI_3; ?>/css/default.css" rel="stylesheet" type="text/css" />
	<script type="text/javascript" src="<?= SP_PLUGIN_URI_3; ?>/swfupload/swfupload.js"></script>
	<script type="text/javascript" src="<?= SP_PLUGIN_URI_3; ?>/js/fileprogress.js"></script>
	<script type="text/javascript" src="<?= SP_PLUGIN_URI_3; ?>/js/handlers.js"></script>
	<script type="text/javascript">
		var swfu;

		window.onload = function () {
			swfu = new SWFUpload({
				// Backend settings
				upload_url: "<?= SP_PLUGIN_URI_3; ?>/lib/upload.php",
				file_post_name: "resume_file",

				// Flash file settings
				file_size_limit : "200 MB",
				file_types : "*.*",			// or you could use something like: "*.doc;*.wpd;*.pdf",
				file_types_description : "All Files",
				file_upload_limit : "0",
				file_queue_limit : "10",

				// Event handler settings
				swfupload_loaded_handler : swfUploadLoaded,

				file_dialog_start_handler: fileDialogStart,
				file_queued_handler : fileQueued,
				file_queue_error_handler : fileQueueError,
				file_dialog_complete_handler : fileDialogComplete,

				//upload_start_handler : uploadStart,	// I could do some client/JavaScript validation here, but I dont need to.
				upload_progress_handler : uploadProgress,
				upload_error_handler : uploadError,
				upload_success_handler : uploadSuccess,
				upload_complete_handler : uploadComplete,

				// Button Settings
				button_image_url : "<?= SP_PLUGIN_URI_3; ?>/images/XPButtonUploadText_61x22.png",
				button_placeholder_id : "spRapidUploadButton",
				button_width: 61,
				button_height: 22,

				// Flash Settings
				flash_url : "<?= SP_PLUGIN_URI_3; ?>/swfupload/swfupload.swf",

				custom_settings : {
					progress_target : "fsUploadProgress",
					upload_successful : false
				},

				// Debug settings
				debug: false
			});

		};
	</script>
	<div class="wrap"><h2><?= __('Upload new file','spRapid'); ?></h2></div>
	<form action="<?= $_SERVER['REQUEST_URI']; ?>" method="post" enctype="multipart/form-data">
	<table class="form-table">
		<tbody>
			<tr>
				<td colspan="2">
					<div class="flash" id="fsUploadProgress"></div>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="spRapidFile"><?= __('File', 'spRapid'); ?></label></th>
				<td>
					<input type="text" id="spRapidFile" disabled="true" style="border: solid 1px; background-color: #FFFFFF;" /> <span id="spRapidUploadButton"></span>
					<input type="hidden" name="hidFileID" id="hidFileID" value="" />
					<!-- <span class="description"><?= __('File to upload', 'spDescChanger'); ?> (Max <?= ini_get('upload_max_filesize'); ?>)</span> -->
				</td>
			</tr>
		</tbody>
	</table><br/><br/>
	<input type="hidden" name="spRapidDoUpload" value="1" />
	<span id="spRapidUploadButton">
		<input class="button-primary" type="submit" value="<?= __('Upload file'); ?>" onclick="stopRapidUploadButton();" id="btnSubmit" />
	</span>
	<span id="spRapidUploadText" style="display:none;">
		<img src="/wp-content/plugins/spRapid/images/indicator.gif" alt="Indicator" title="Indicator"> <?= __('Uploading... Please wait', 'spRapid'); ?> <a href="admin.php?page=spRapidUpload"><?= __('Cancel', 'spRapid'); ?></a>
	</span>
	</form>
	<?
}

function spRapidAccount(){
	$settings = spMainClass::getSettings('spRapid');

	$myAccount = new spRapidshare();
	$myAccount->config($settings->spRapidType, $settings->spRapidId, $settings->spRapidPass);
	$account = $myAccount->getAccountInfo();

	if(is_string($account)){
		if(substr($account, 0, 5) == 'ERROR'){
			echo spMainClass::showMessage($account, 'error');
			die();
		}
	}

	$rest = ($account->premkbleft)/($account->premkb)*100;
	$used = 100-$rest;
	?>
	<div class="wrap"><h2><?= __('My account','spRapid'); ?></h2></div>
	<table width="100%">
		<tr>
			<td valign="top">
				<table style="margin-top: 1em;width:99%;" class="widefat">
					<thead>
						<tr>
							<th class="manage-column" scope="col">Account details</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>
								<table>
									<tr>
										<td>Account:</td>
										<td><?= ($account->type == 'prem') ? __('Premium', 'spRapid') : __('Collector', 'spRapid'); ?></td>
									</tr>
									<tr>
										<td>Bestehend seit:</td>
										<td><?= date('Y-m-d',$account->addtime); ?></td>
									</tr>
									<tr>
										<td>UserId:</td>
										<td><?= $account->accountid; ?></td>
									</tr>
									<tr>
										<td>Login:</td>
										<td><?= $account->username; ?></td>
									</tr>
									<tr>
										<td>Email:</td>
										<td><?= $account->email; ?></td>
									</tr>
									<tr>
										<td>RapidPoints:</td>
										<td><?= $account->points; ?></td>
									</tr>
									<tr>
										<td>Gültig bis:</td>
										<td><?= date('Y-m-d H:i:s',$account->validuntil); ?></td>
									</tr>
									<tr>
										<td>Dateien</td>
										<td><?= $account->curfiles; ?></td>
									</tr>
									<tr>
										<td>Belegter Speicher:</td>
										<td><?= spMainClass::getRightSize($account->curspace); ?></td>
									</tr>
								</table>
							</td>
						</tr>
					</tbody>
				</table>
			</td>
			<td valign="top">
				<table style="margin-top: 1em;width:99%;" class="widefat">
					<thead>
						<tr>
							<th class="manage-column" scope="col"><?= __('Memory usage', 'spRapid'); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>
								<img src="http://chart.apis.google.com/chart?chs=500x248&amp;chd=t:<?= $rest; ?>,<?= $used; ?>&amp;cht=p3&amp;hco=00ff00,ff0000&amp;chdlp=t&amp;chdl=Free (<?= spMainClass::getRightSize($account->premkbleft*1024); ?>)|Used (<?= spMainClass::getRightSize(($account->premkb-$account->premkbleft)*1024); ?>)&amp;chco=00ff00,ff0000" alt="Sample chart" />
							</td>
						</tr>
					</tbody>
				</table>
			</td>
		</tr>
	</table>
	<?
}

function spRapidSettings(){
	if($_POST['spRapidSettings']){
		echo spMainClass::showMessage(__('Settings saved. <a href="admin.php?page=spRapidAccount">Show account details</a>','spRapid'));
		spMainClass::setSettings('spRapid');
	}
	$settings = spMainClass::getSettings('spRapid');
	?>
	<div class="wrap"><h2><? _e('spRapid settings','spRapid'); ?></h2></div>
	<form action="<?= $_SERVER['REQUEST_URI']; ?>" method="post">
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row"><label for="spRapidType"><?= __('Account type', 'spRapid'); ?></label></th>
				<td>
					<select name="spRapidType" id="spRapidType">
						<option><?= __('Please choose', 'spRapid'); ?></option>
						<?
						$premium = ($settings->spRapidType == 'prem') ? 'selected="selected"' : '';
						$collector = ($settings->spRapidType == 'col') ? 'selected="selected"' : '';
						?>
						<option value="prem" <?= $premium; ?>><?= __('Premium', 'spRapid'); ?></option>
						<option value="col" <?= $collector; ?>><?= __('Collector', 'spRapid'); ?></option>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="spRapidId"><? _e('User ID', 'spRapid'); ?></label></th>
				<td>
					<input type="text" class="regular-text" value="<?= $settings->spRapidId; ?>" id="spRapidId" name="spRapidId"/>
					<span class="description"><? _e('The ID or alias of your account', 'spRapid'); ?></span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="spRapidPass"><? _e('Password', 'spRapid'); ?></label></th>
				<td>
					<input type="password" class="regular-text" value="<?= $settings->spRapidPass; ?>" id="spRapidPass" name="spRapidPass"/>
					<span class="description"><? _e('Your Rapidshare password', 'spRapid'); ?></span>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="spRapidShort"><? _e('Tiny url off', 'spRapid'); ?></label></th>
				<td>
					<input type="checkbox" name="spRapidShort" id="spRapidShort" value="off" /> <?= __('', 'spRapid') ?>
					<span class="description"><? _e('Switch off? spRapid uses <a href="http://shorted.de" target="_blank">shorted.de</a> service to shorten your rapidshare urls', 'spRapid'); ?></span>
				</td>
			</tr>
		</tbody>
	</table><br/><br/>
	<input type="hidden" name="spRapidSettings" value="1" />
	<input class="button-primary" type="submit" value="<? _e('Save Changes'); ?>" />
	</form>
	<?
}

function spRapidHeader(){
	echo '<script type="text/javascript" src="'  . get_option('siteurl') . SP_PLUGIN_URI_3.'/js/jquery.js"></script>'."\n";
	echo '<script type="text/javascript" src="'  . get_option('siteurl') . SP_PLUGIN_URI_3.'/js/scripts.js"></script>'."\n";
}

function spRapidUninstall(){
	global $wpdb;
	if($_POST['uninstall']){
		spMainClass::deleteSettings('spRapid');
		$wpdb->query('DROP TABLE '.$wpdb->prefix.'rapid_files');
		$wpdb->query('DROP TABLE '.$wpdb->prefix.'rapid_links');
		echo spMainClass::showMessage(__('spRapid is now uninstalled completely. Please deactivate this plugin.','spRapid'));
		die();
	}
	if($_POST['cancel']){
		echo spMainClass::showMessage(__('Good choice. Would you <a href="admin.php?page=spRapidUpload">upload</a> a new file? ;-)','spRapid'));
	}
	?>
	<div class="wrap"><h2><?= __('Really uninstall spRapid ?','spRapid'); ?></h2></div>
	<form action="<?= $_SERVER['REQUEST_URI']; ?>" method="post">
		<?= __('All your uploads as a free user will be deleted in this wordpress database. They will further be stored at rapidshare!'); ?><br/><br/>
		<input class="button-primary" type="submit" name="uninstall" value="<? _e('Uninstall', 'spRapid'); ?>" /> <input class="button-primary" type="submit" name="cancel" value="<? _e('Cancel', 'spRapid'); ?>" />
	</form>
	<?
}