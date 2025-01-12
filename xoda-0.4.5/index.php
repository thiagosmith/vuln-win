<?php
error_reporting ('E_ALL');
ini_set ('display_errors','On');

session_start ();
clearstatcache ();

setlocale (LC_CTYPE, 'en_US.UTF-8');
define ('PHP_SELF', basename ($_SERVER['PHP_SELF']));
$d_phpself = dirname ($_SERVER['PHP_SELF']);
if ($d_phpself != '/') { define ('PHPSELF', $d_phpself . '/'); }
else { define ('PHPSELF', '/'); }
if (isset ($_SERVER['HTTPS'])) { $protocol = 'https'; }
else { $protocol = 'http'; }
$redirect = $protocol . '://' . $_SERVER['HTTP_HOST'] . PHPSELF;

if (! file_exists ('config.php')) {
	if (preg_match ("#win#i", strtolower (php_uname ()))) { echo '<small>Windows? I\'m out!</small>'; exit; }
	echo 'Please rename "config.sample.php" to "config.php" before running XODA!'; exit;
}
require_once ('functions.php');
date_default_timezone_set (TIMEZONE);
if (xd_mobile ()) { define ('MOBILE', true); } else { define ('MOBILE', false); }

$icon_dir = 'R0lGODlhEAAQAKUjANnc48zQ2Nba39LW3NTY352ksbvByqCns+/w8sDFzqiuuq60v8nO1tPX3pWdqrq/yePl6sTJ0niAkM/T2tjb4Nba4MPH0M7S2bzBy/z8/eTo6+3w8ujr7vP09tzg5fj5+uHl6Y+XpWZvgf///////////////////////////////////////////////////////////////////////////////////////////////////////////////////yH5BAEAAD8ALAEAAwAPAA0AAAZnwN8oRCSKAL+k8hcagQYDg+G4/AEw2Mfi4CgWC83Mp7PhaEAgT+XSEIXH5XOaEJi4R2KyGe0RXBh3eXF8axGBcHtpawmHenJqFxiNg2l+km+OfHSXIx8IEBQBFqOkCiIFIqmqq6oSQQA7';
$icon_file = 'R0lGODlhEAAQAIQaALm+xaaqtPT39tXb3eHo6dfd4Ovy7+Ty68zT2Nvq49fh4tLS2HN4iNbc4NXc3uPu6ez38XJ4iKWos9rg4aWotM/P1pGWo+Lp6W92h/b7+P///////////////////////yH5BAEAAB8ALAAAAAAQABAAAAVe4CeOZFkuw+A0xYQE5lcljyHcwAWX1YH9mAwgo+NBgpkgYAmQkCrJTDRDICgYzyTwIpVassSLuJv5jqDktFkEBbq9WbFcXs6632tZeD7Ot+8/dWdpaiQZFBaJiooRIQA7';

if (isset ($_REQUEST['subdir'])) { xd_check_traverse ($_REQUEST['subdir']);
	if (! file_exists (xd_get_root_dir() . trim ($_REQUEST['subdir'], '/'))) {
		$_SESSION['subdir'] = $subdir = false;
		$_SESSION['message'] = 'ERROR: This directory does not exist!';
		global $redirect; header ('Location:' . $redirect);
		exit;
	}
	if (strstr ($_REQUEST['subdir'], '..') || $_REQUEST['subdir'] == '.') { $_SESSION['subdir'] = $subdir = false; }
	else { $_SESSION['subdir'] = $subdir = trim ($_REQUEST['subdir'], '/'); }
} else { $_SESSION['subdir'] = $subdir = false; }

if (isset ($_REQUEST['action'])) {
	global $redirect;
	if (! $_SESSION['login']) {
		$_SESSION['message'] = 'ERROR: no permission to access anything!';
		header ('Location:' . $redirect);
		exit;
	}
	xd_check_privileges (6);
	$path = trim ($_REQUEST['action'], '/');
	xd_check_traverse ($path);
	if (file_exists (xd_get_root_dir() . $path)) {
		if (is_dir (xd_get_root_dir() . $path)) {
			$subdir = $path;
		} else if (is_file (xd_get_root_dir() . $path)) {
			xd_download ($path);
			exit;
		}
	} else {
		$_SESSION['message'] = 'ERROR: no such file or directory!';
		header ('Location:' . $redirect);
		exit;
	}
}

if (isset ($_REQUEST['submit']) && strtolower ($_REQUEST['submit']) == 'cancel') {
	$_SESSION['message'] = 'Nothing was changed.';
	header ('Location:' . $_SERVER['HTTP_REFERER']);
	exit;
}

if (! $_SESSION['login'] && isset ($_SERVER['REMOTE_USER'])) {
	global $_users;
	foreach ($_users as $name => $user) {
		if ($_SERVER['REMOTE_USER'] == $name) {
			$_SESSION['login'] = true;
			$_SESSION['username'] = $name;
			$_SESSION['anonymous'] = false;
			$_SESSION['privileges'] = $user['privileges'];
			if ($user['privileges'] == 1) { $_SESSION['settings_tabs'] = "'prefs_tab','layer_tab','style_tab','users_tab'"; }
			if (isset ($user['home'])) { $_SESSION['rootdir'] = ROOT_DIR . $user['home']; }
			else { $_SESSION['rootdir'] = ROOT_DIR;	}
			$_SESSION['remote_login'] = true;
			if (FILTERS) { $_SESSION['filters'] = xd_get_filters (); }
			$_SESSION['message'] = 'Successfully logged in. Welcome, ' . $name . '.';
			global $redirect; header ('Location:' . $redirect);
			exit;
		}
	}
} else if (! $_SESSION['login'] && ANONYMOUS) {
	$_SESSION['login'] = true;
	$_SESSION['rootdir'] = ROOT_DIR . ANONYMOUS;
	$_SESSION['privileges'] = 6;
	$_SESSION['anonymous'] = true;
	if (FILTERS) { $_SESSION['filters'] = xd_get_filters (); }
	global $redirect; header ('Location:' . $redirect);
	exit;
}

if (isset ($_REQUEST['log_in'])) {
	global $_users;
	foreach ($_users as $name => $user) {
		if ($_REQUEST['username'] == $name && md5 ($_REQUEST['password']) == $user['password']) {
			$_SESSION = array ();
			$_SESSION['login'] = true;
			$_SESSION['username'] = $name;
			$_SESSION['anonymous'] = false;
			$_SESSION['privileges'] = $user['privileges'];
			if ($user['privileges'] == 1) { $_SESSION['settings_tabs'] = "'prefs_tab','layer_tab','style_tab','users_tab','passw_tab'"; }
			else { $_SESSION['settings_tabs'] = "'passw_tab'"; }
			if (isset ($user['home'])) { $_SESSION['rootdir'] = ROOT_DIR . $user['home']; }
			else { $_SESSION['rootdir'] = ROOT_DIR;	}
			$_SESSION['remote_login'] = false;
			if (FILTERS) { $_SESSION['filters'] = xd_get_filters (); }
			$_SESSION['message'] = 'Successfully logged in. Welcome, ' . $name . '.';
			global $redirect; header ('Location:' . $redirect);
			exit;
		}
	}
	$_SESSION['login'] = false;
	$_SESSION['message'] = 'ERROR: Login failed.';
	global $redirect; header ('Location:' . $redirect);
	exit;
} else if (isset ($_REQUEST['login'])) { ?>
	<h4>Login</h4>
	<hr class="line" />
	<form method="post" action="<?php echo PHPSELF;?>?log_in" name="lform">
		<p>Username:&nbsp;<input type="text" id="un" name="username" /></p>
		<p>Password:&nbsp;<input type="password" name="password" /></p>
		<p><input type="submit" name="submit" value="login" /></p>
	</form>
	<?php exit;
} else if (isset ($_REQUEST['logout'])) {
	if (isset ($_COOKIE[session_name()])) { setcookie (session_name(), '', time()-42000, '/'); }
	$_SESSION = array ();
	session_start ();
	$_SESSION = array ('login' => false);
	global $redirect; header ('Location:' . $redirect);
	exit;
} else if (isset ($_REQUEST['download']) && xd_check_privileges (6)) {
	xd_check_traverse ($_REQUEST['download']);
	$force = false;
	if (isset ($_REQUEST['force'])) { $force = true; }
	xd_download (urlencode ($_REQUEST['download']), xd_basename ($_REQUEST['download']), false, $force);
	exit;
} else if (isset ($_REQUEST['info']) && xd_check_privileges (6)) { ?>
	<p><?php
	xd_check_traverse ($_REQUEST['info']);
	$bn = xd_basename ($_REQUEST['info']); ?>
	<h4>Information about "<em><?php echo $bn; ?></em>"</h4>
	<hr class="line" />
	<?php
	$file_info = xd_file_info (urlencode ($_REQUEST['info']));
	foreach ($file_info as $fkey => $info_item) {
		if ($fkey == 'Name' && xd_check_privileges (2)) { ?><div id="file_rename"><a href="#" title="Rename '<?php echo xd_basename ($info_item);?>'" onclick="load('<?php echo PHPSELF;?>?rename=<?php echo $_REQUEST['info'];?>', 'file_rename'); return false"><?php echo LABEL_EDIT;?></a><?php }
		if ($fkey == 'Last_modified' && xd_check_privileges (3)) { ?> <div id="file_mtime"><a href="#" title="Edit the last modification time of '<?php echo $bn;?>'." onclick="load('<?php echo PHPSELF;?>?edit_mtime=<?php echo $_REQUEST['info'];?>', 'file_mtime'); return false"><?php echo LABEL_EDIT;?></a><?php }
		if ($fkey == 'Description' && xd_check_privileges (3)) { ?> <div id="file_description"><a href="#" title="Edit the description of '<?php echo $bn;?>'" onclick="load('<?php echo PHPSELF;?>?edit_description=<?php echo $_REQUEST['info'];?>', 'file_description'); return false"><?php echo LABEL_EDIT;?></a><?php }
		if ($fkey == 'Filters' && xd_check_privileges (3)) {
			if (FILTERS) { ?> <div id="file_filters"><a href="#" title="Edit the filters of '<?php echo $bn;?>'" onclick="load('<?php echo PHPSELF;?>?edit_filters=<?php echo $_REQUEST['info'];?>', 'file_filters'); return false"><?php echo LABEL_EDIT;?></a>&nbsp;<strong><?php echo str_replace ('_', ' ', $fkey);?></strong>:<?php } ?>
		<?php } else { ?>&nbsp;<strong><?php echo str_replace ('_', ' ', $fkey);?></strong>:<?php } ?>
		<?php if ($fkey == 'Name') { echo $info_item; } else if ($fkey != 'Filters') { echo $info_item; }
		if (FILTERS && $fkey == 'Filters') { ?>
			<?php if (count ($file_info['Filters'])) { ?>
				<ul>
				<?php foreach ($file_info['Filters'] as $k => $filter) { ?>
					<li><?php echo xd_unhtmlentities ($filter);?></li>
				<?php } ?>
				</ul>
			<?php } else { ?>No filters set.<?php }
		} ?>
		</div>
		<?php if (FILTERS || $fkey != 'Filters') { ?><hr class="line" /><?php } ?>
	<?php } ?>
	</p>
	<?php exit;
} else if (isset ($_REQUEST['dirinfo']) && xd_check_privileges (6)) {
	xd_check_traverse ($_REQUEST['dirinfo']);
	if (is_dir (xd_get_root_dir() . $_REQUEST['dirinfo'])) {
		$files_count = @xd_count_files (xd_get_root_dir() . $_REQUEST['dirinfo'], 'f');
		$dirs_count = @xd_count_files (xd_get_root_dir() . $_REQUEST['dirinfo'], 'd');
		if ($files_count != '1') { $files_txt = $files_count . ' Files '; }
		else { $files_txt = $files_count . ' File '; }
		if ($dirs_count != '1') { $dirs_txt = $dirs_count . ' Directories '; }
		else { $dirs_txt = $dirs_count . ' Directory '; }
		echo $files_txt . 'and ' . $dirs_txt;
		echo 'using the total of ' . xd_file_size (xd_get_root_dir() . $_REQUEST['dirinfo'], 'h') . 'B.';
	}
	exit;
} else if (isset ($_REQUEST['edit']) && xd_check_privileges (2)) {
	xd_check_traverse ($_REQUEST['edit']);
	$filename = xd_get_root_dir() . $_REQUEST['edit']; ?>
	<h4>Edit file "<em><?php echo xd_basename ($_REQUEST['edit']); ?></em>"</h4>
	<hr class="line" />
	<form method="post" name="editform" action="<?php echo PHPSELF;?>?edited=<?php echo $_REQUEST['edit'];?>">
		<div style="text-align: right;">
		<?php if (VERSIONING) { ?>Save previous version: <input type="checkbox" name="save_version" checked="checked" /><?php } ?>
		<input type="submit" name="submit" value="Save" />&nbsp;<input type="submit" name="submit" value="Cancel" />
		</div>
		<textarea name="editfile" style="width: 615px; height: 440px;"><?php echo htmlspecialchars (file_get_contents ($filename));?></textarea>
	</form>
	<?php exit;
} else if (isset ($_REQUEST['edited']) && xd_check_privileges (2)) {
	xd_check_traverse ($_REQUEST['edited']);
	$filename = xd_get_root_dir() . $_REQUEST['edited'];
	$new_content = xd_unhtmlentities ($_POST['editfile']);
	if (xd_file_put_content ($filename, $new_content)) {
		if (strtolower ($_REQUEST['submit']) == 'save') { $_SESSION['message'] = 'File saved.'; }
		else { $_SESSION['message'] = '"' . xd_basename ($filename) . '" could be successfully changed.'; }
		if (VERSIONING && $_REQUEST['save_version'] == 'on') { xd_save_version ($_REQUEST['edited'], array ('text' => 'Automatically saved after editing.')); }
	} else { $_SESSION['message'] = 'ERROR: "' . xd_basename ($filename) . '" could not be changed.'; }
	header ('Location:' . $_SERVER['HTTP_REFERER']);
	exit;
} else if (isset ($_REQUEST['create_in']) && xd_check_privileges (4)) { ?>
	<h4>Create new file or directory</h4>
	<hr class="line" />
	<form method="post" action="<?php echo PHPSELF;?>?create">
	<p>Name: <input type="text" name="create" /></p>
	<input type="hidden" name="pwd" value="<?php echo $_REQUEST['create_in'];?>" /><strong>Create</strong>:
	<input type="submit" name="submit" value=" file " /> or 
	<input type="submit" name="submit" value="directory" />
	</form>
	<?php exit;
} else if (isset ($_REQUEST['create']) && xd_check_privileges (4)) {
	xd_check_traverse ($_REQUEST['create']);
	$to_create = trim (xd_clean ($_REQUEST['create']));
	$cd = trim ($_REQUEST['pwd'], '/');
	if (strstr ($to_create, '..') || strstr ($to_create, '/')) { $_SESSION['message'] = 'ERROR: The name ' . $to_create . ' is not correct to be used as is!'; header ('Location:' . $_SERVER['HTTP_REFERER']); exit; }
	$_SESSION['message'] = 'ERROR: "' . $to_create . '" could not be created!';
	if (strlen ($to_create)) {
		if ($_REQUEST['submit'] == ' file ') { if (touch (xd_get_root_dir() . $cd . '/' . $to_create)) { $_SESSION['message'] = 'The file "' . $to_create . '" was successfully created.'; } }
		else { if (xd_mkdirp (xd_get_root_dir() . $cd . '/' . $to_create)) { $_SESSION['message'] = 'The directory "' . $to_create . '" was successfully created.'; } }
	}
	header ('Location:' . $_SERVER['HTTP_REFERER']);
	exit;
} else if (isset ($_REQUEST['transfer_to']) && xd_check_privileges (4)) { ?>
	<h4>Transfer a file from the Web</h4>
	<hr class="line" />
	<form method="post" action="<?php echo PHPSELF;?>?wget">
		<p>URL:<br /><input type="text" name="url" /></p>
		<p>Save as:<br /><input type="text" name="target_name" /></p>
		<input type="hidden" name="pwd" value="<?php echo trim ($_REQUEST['transfer_to'], '/');?>" />
		<p><input type="submit" value="transfer file" /></p>
	</form>
	<?php exit;
} else if (isset ($_REQUEST['wget']) && xd_check_privileges (4)) {
	xd_check_traverse ($_REQUEST['target_name']);
	$_SESSION['message'] = 'ERROR: The file could not be transfered!';
	$cd = trim ($_REQUEST['pwd'], '/');
	$target_name = $_REQUEST['target_name'];

	if (empty ($_REQUEST['target_name'])) {
		$url_arr = parse_url ($_REQUEST['url']);
		$target_name = xd_basename ($url_arr['path']);
	}
	if (xd_wget_system ($_REQUEST['url'], $cd, $target_name)) { $_SESSION['message'] = 'Transfer completed and saved as: "' . $target_name . '".'; }
	header ('Location:' . $_SERVER['HTTP_REFERER']);
	exit;
} else if (isset ($_REQUEST['upload_to']) && xd_check_privileges (4)) { ?>
	<h4>Upload a file</h4>
	<hr class="line" />
	<form method="post" enctype="multipart/form-data" action="<?php echo PHPSELF;?>?upload">
		&nbsp;<small>(The server limit for maximal file size is <strong><?php echo xd_max_upload_size ();?>B</strong>.)</small><br /><br />
		<input type="file" name="files_to_upload[]" id="attachment" onchange="document.getElementById('moreUploadsLink').style.display = 'block';" /><br />
		<div id="moreUploads"></div>
		<div id="moreUploadsLink" style="display:none;"><br /><a href="#" onclick="addFileInput(); return false;">add file</a><br /></div>
		<input type="hidden" name="pwd" value="<?php echo trim ($_REQUEST['upload_to'], '/');?>" />
		<p><input type="submit" value="upload" /></p>
	</form>
	<?php exit;
} else if (isset ($_REQUEST['upload']) && xd_check_privileges (4)) {
	$cd = trim ($_REQUEST['pwd'], '/');
	$_SESSION['message'] = 'ERROR: The file(s) could not be uploaded!';
	if (xd_upload ($cd)) { $_SESSION['message'] = 'Upload completed.'; }
	header ('Location:' . $_SERVER['HTTP_REFERER']);
	exit;
} else if (isset ($_REQUEST['rename']) && xd_check_privileges (2)) { ?>
	<form method="post" enctype="multipart/form-data" action="<?php echo PHPSELF;?>?renamed">
		<strong>Name</strong>:&nbsp;<input class="renameinput" type="text" value="<?php echo xd_basename ($_REQUEST['rename']);?>" name="file_to_rename" />
		<input type="hidden" name="orig_name" value="<?php echo trim ($_REQUEST['rename'], '/');?>" />
		<input type="submit" value="rename" />
	</form>
	<?php exit;
} else if (isset ($_REQUEST['renamed']) && xd_check_privileges (2)) {
	$nf = trim (xd_clean ($_REQUEST['file_to_rename']), '/');
	$on = $_REQUEST['orig_name'];
	$don = dirname ($on);
	$nn = $nf;
	if (strstr ($_REQUEST['file_to_rename'], '/') || strstr ($_REQUEST['file_to_rename'], '..')) {
		$_SESSION['message'] = 'ERROR: The desired new name can not contain a slash ("/") sign or point to upper directories ("..")!';
		header ('Location:' . $_SERVER['HTTP_REFERER']); exit;
	}
	$_SESSION['message'] = 'ERROR: The file/directory could not be renamed!';
	if (xd_rename ($on, $don . '/' . $nn)) {
		if (FILTERS) { $_SESSION['filters'] = xd_get_filters (); }
		$_SESSION['message'] = 'The file/directory was renamed successfully.';
	}
	header ('Location:' . $_SERVER['HTTP_REFERER']);
	exit;
} else if (isset ($_REQUEST['edit_mtime']) && xd_check_privileges (3)) {
	xd_check_traverse ($_REQUEST['edit_mtime']);
	$mtimed = xd_get_root_dir() . $_REQUEST['edit_mtime'];
	if (is_link ($mtimed)) {
		$lsinfo = lstat ($mtimed);
		$ut_filetime = $lsinfo[9];
	} else { $ut_filetime = filemtime ($mtimed); }
	$mDay = date ('d', $ut_filetime);
	$mMonth = date ('F', $ut_filetime);
	$mYear = date ('Y', $ut_filetime);
	$mHour = date ('H', $ut_filetime);
	$mMinute = date ('i', $ut_filetime);
	$days = range (1, 31);
	$months = array (1 => 'January', 'February', 'March', 'April', 'May', 'June','July', 'August', 'September', 'October', 'November', 'December');
	$years = range (2000, date ('Y', time()));
	$hours = range (0, 23);
	$minutes = range (0, 59); ?>
	<h4>Last modified:</h4>
	<form method="post" enctype="multipart/form-data" action="<?php echo PHPSELF;?>?edited_mtime=<?php echo $_REQUEST['edit_mtime'];?>">
		<select name="mMonth" style="display: inline; font: normal 8pt Arial, Helvetica;"><?php foreach ($months as $month) { ?><option value="<?php echo $month;?>"<?php if ($month == $mMonth) { ?> selected="selected"<?php } ?> ><?php echo $month;?></option><?php } ?></select>&nbsp;/
		<select name="mDay" style="display: inline; font: normal 8pt Arial, Helvetica;"><?php foreach ($days as $day) { ?><option value="<?php echo $day;?>"<?php if ($day == $mDay) { ?> selected="selected"<?php } ?> ><?php echo $day;?></option><?php } ?></select>&nbsp;/
		<select name="mYear" style="display: inline; font: normal 8pt Arial, Helvetica;"><?php foreach ($years as $year) { ?><option value="<?php echo $year;?>"<?php if ($year == $mYear) { ?> selected="selected"<?php } ?> ><?php echo $year;?></option><?php } ?></select>,
		<select name="mHour" style="display: inline; font: normal 8pt Arial, Helvetica;"><?php foreach ($hours as $hour) { ?><option value="<?php echo $hour;?>"<?php if ($hour == $mHour) { ?> selected="selected"<?php } ?> ><?php echo $hour;?></option><?php } ?></select> :
		<select name="mMinutes" style="display: inline; font: normal 8pt Arial, Helvetica;"><?php foreach ($minutes as $minute) { ?><option value="<?php echo $minute;?>"<?php if ($minute == $mMinute) { ?> selected="selected"<?php } ?> ><?php echo $minute;?></option><?php } ?></select>
		<input type="submit" value="set" />
	</form>
	<?php exit;
} else if (isset ($_REQUEST['edited_mtime']) && xd_check_privileges (3)) {
	xd_check_traverse ($_REQUEST['edited_mtime']);
	$tmtimed = trim ($_REQUEST['edited_mtime'], '/'); $dtmtimed = dirname ($tmtimed);
	$edited_mtime = xd_get_root_dir() . $tmtimed;
	$months = array ('January' => 1, 'February' => 2, 'March' => 3, 'April' => 4, 'May' => 5, 'June' => 6, 'July' => 7, 'August' => 8, 'September' => 9, 'October' => 10, 'November' => 11, 'December' => 12);
	$new_mtime = mktime ($_REQUEST['mHour'], $_REQUEST['mMinutes'], 0, $months[$_REQUEST['mMonth']], $_REQUEST['mDay'], $_REQUEST['mYear']);
	$_SESSION['message'] = 'ERROR: The modification time could not be set!';
	if (touch ($edited_mtime, $new_mtime)) { $_SESSION['message'] = 'The modification time was successfully set.'; }
	header ('Location:' . $_SERVER['HTTP_REFERER']);
	exit;
} else if (isset ($_REQUEST['edit_filters']) && xd_check_privileges (3)) {
	$edit_filters = trim ($_REQUEST['edit_filters'], '/');
	$_filters = array ();
	$mf = xd_metafile_of ($edit_filters);
	if (file_exists ($mf)) { include ($mf); } ?>
	<strong>Filters (one per line):</strong>
	<form method="post" enctype="multipart/form-data" action="<?php echo PHPSELF;?>?edited_filters=<?php echo $edit_filters;?>">
		<p><textarea name="filters_to_edit" style="width: 300px; height: 100px;"><?php echo implode ("\n", $_filters);?></textarea></p>
		<p><input type="submit" name="submit" value="Set filters" /></p>
	</form>
	<?php exit;
} else if (isset ($_REQUEST['edited_filters']) && xd_check_privileges (3)) {
	$filename = trim ($_REQUEST['edited_filters'], '/');
	$_SESSION['message'] = 'The filters of "' . xd_basename ($filename) . '" were edited successfully.';
	xd_set_filters ($filename, $_REQUEST['filters_to_edit']);
	$_SESSION['filters'] = xd_get_filters ();
	header ('Location:' . $_SERVER['HTTP_REFERER']);
	exit;
} else if (isset ($_REQUEST['edit_description']) && xd_check_privileges (3)) {
	$edit_description = trim ($_REQUEST['edit_description'], '/'); ?>
	<form method="post" enctype="multipart/form-data" action="<?php echo PHPSELF;?>?edited_description=<?php echo $edit_description;?>">
		<?php xd_edit_description ($edit_description); ?>
	</form>
	<?php exit;
} else if (isset ($_REQUEST['edited_description']) && xd_check_privileges (3)) {
	$filename = trim ($_REQUEST['edited_description'], '/');
	$_SESSION['message'] = 'The description of "' . xd_basename ($filename) . '" was edited successfully.';
	xd_set_description ($filename, array ('text' => $_REQUEST['description_to_edit'], 'descrbackcolor' => $_REQUEST['descrbackcolor'], 'descrfontcolor' => $_REQUEST['descrfontcolor']));
	header ('Location:' . $_SERVER['HTTP_REFERER']);
	exit;
} else if (isset ($_REQUEST['edit_vdescription']) && xd_check_privileges (2)) {
	xd_check_traverse ($_REQUEST['file']);
	$edit_vdescription = trim ($_REQUEST['edit_vdescription'], '/'); ?>
	<form method="post" enctype="multipart/form-data" action="<?php echo PHPSELF;?>?edited_vdescription=<?php echo $edit_vdescription;?>&amp;file=<?php echo $_REQUEST['file'];?>">
		<?php xd_edit_description ($_REQUEST['file'], $edit_vdescription); ?>
		<input type="submit" name="submit" value="Save" />
		<input type="submit" name="submit" value="Cancel" />
	</form>
	<?php exit;
} else if (isset ($_REQUEST['edited_vdescription']) && xd_check_privileges (2)) {
	xd_check_traverse ($_REQUEST['file']);
	$filename = trim ($_REQUEST['edited_vdescription'], '/');
	$_SESSION['message'] = 'The version description was edited successfully.';
	xd_set_description ($_REQUEST['file'], array ('text' => $_REQUEST['description_to_edit'], 'descrbackcolor' => $_REQUEST['descrbackcolor'], 'descrfontcolor' => $_REQUEST['descrfontcolor']), $_REQUEST['edited_vdescription']);
	header ('Location:' . $_SERVER['HTTP_REFERER']);
	exit;
} else if (isset ($_REQUEST['save_version']) && VERSIONING && xd_check_privileges (2)) {
	xd_check_traverse ($_REQUEST['save_version']);
	$file = trim ($_REQUEST['save_version'], '/'); ?>
	<form method="post" enctype="multipart/form-data" action="<?php echo PHPSELF;?>?saved_version=<?php echo $file;?>">
		<?php xd_edit_description (false, true); ?>
		<input type="submit" name="submit" value="Save Version" />
		<a href="#" onclick="document.getElementById('versioning').innerHTML=''; return false;">cancel</a>
	</form>
	<?php exit;
} else if (isset ($_REQUEST['saved_version']) && VERSIONING && xd_check_privileges (2)) {
	xd_check_traverse ($_REQUEST['saved_version']);
	$filename = trim ($_REQUEST['saved_version'], '/');
	$bn = xd_basename ($filename);
	$_SESSION['message'] = 'ERROR: Current version of "' . $bn . '" could not be saved!';
	if (xd_save_version ($filename, array ('text' => $_REQUEST['description_to_edit'], 'descrbackcolor' => $_REQUEST['descrbackcolor'], 'descrfontcolor' => $_REQUEST['descrfontcolor']))) {
		$_SESSION['message'] = 'The current version of "' . $bn . '" was successfully saved.';
	}
	header ('Location:' . $_SERVER['HTTP_REFERER']);
	exit;
} else if (isset ($_REQUEST['versions_of']) && VERSIONING && xd_check_privileges (2)) {
	$file = xd_basename ($_REQUEST['versions_of']);
	$subdir_url = '';
	if (dirname ($_REQUEST['versions_of']) != '.') { $subdir_url = dirname ($_REQUEST['versions_of']); }
	$versions = glob (xd_version_dir_of ($_REQUEST['versions_of']) . '/*-' . $file);
	if (! empty ($versions)) {
		rsort ($versions);
		foreach ($versions as $version) {
			$bn = xd_basename ($version); 
			$n = str_replace ($file, '', $bn);?>
			<p class="version">
				<a href="<?php echo PHPSELF;?>?version=<?php echo $n;?>">
				<?php echo date (DATE_FORMAT, filectime ($version)); ?>
				</a>
				<?php if (xd_check_privileges (2)) { ?><a href="<?php echo PHPSELF;?>?delete_version=<?php echo $n; ?>&amp;file=<?php echo $version; ?>" onclick="return checkDelete('this version');" title="Delete this version."><?php echo LABEL_DELETE;?></a><?php } ?>
				<span id="<?php echo $bn;?>-description">
				<?php echo xd_description_of (str_replace (META_DIR, '', $version), false, true, $n); ?>
				<?php if (xd_check_privileges (3)) { ?><a href="#" title="Edit the description of this version" onclick="load('<?php echo PHPSELF;?>?edit_vdescription=<?php echo $n; ?>&amp;file=<?php echo $version; ?>', '<?php echo $bn; ?>-description'); return false"><?php echo LABEL_EDIT; ?></a><?php } ?>
				</span>
			</p>
		<?php }
	} else { ?><h4>No old versions saved.</h4><?php }
	exit;
} else if (isset ($_REQUEST['version']) && VERSIONING && xd_check_privileges (2)) {
	$bn = xd_basename ($_REQUEST['file']);
	xd_download (urlencode (xd_version_dir_of ($_REQUEST['file']) . '/' . $_REQUEST['version'] . '-' . $bn), $_REQUEST['version'] . '-' . $bn, true);
	exit;
} else if (isset ($_REQUEST['delete_version']) && VERSIONING && xd_check_privileges (2)) {
	$_SESSION['message'] = 'ERROR: The version could not be deleted!';
	if (xd_rmrf (xd_version_dir_of ($_REQUEST['file']) . '/' . $_REQUEST['delete_version'] . '-' . xd_basename ($_REQUEST['file']))) {
		$_SESSION['message'] = 'The version was successfully deleted!';
		$metafile = xd_metafile_of ($_REQUEST['file'], $_REQUEST['delete_version']);
		if (file_exists ($metafile)) { xd_rmrf ($metafile); }
		$mfs = glob (dirname ($metafile) . '/*');
		if (empty ($mfs)) { xd_rmrf (dirname ($metafile)); }
	}
	header ('Location:' . $_SERVER['HTTP_REFERER']);
	exit;
} else if (isset ($_REQUEST['selection']) && xd_check_privileges (6)) { ?>
	<hr class="line" />
	<?php if ($_REQUEST['selection'] == 'copy' && xd_check_privileges (2)) { ?>
		<strong>Enter the directory to copy the selected files to:</strong> /<input type="text" name="edit_copy_to" />
	<?php } else if ($_REQUEST['selection'] == 'move' && xd_check_privileges (2)) { ?>
		<strong>Enter the directory to move the selected files to:</strong> /<input type="text" name="edit_move_to" />
	<?php } else if ($_REQUEST['selection'] == 'zip') { ?>
		<strong>Enter the name of the zip-archive:</strong> <input type="text" name="edit_zip" />
	<?php } else if ($_REQUEST['selection'] == 'delete' && xd_check_privileges (2)) { ?>
		<strong>Are you sure you want to <u>delete</u> all of the selected files?</strong>
	<?php } else if ($_REQUEST['selection'] == 'filters' && xd_check_privileges (3)) { ?>
		<strong>Filters (one per line):</strong>
		<div style="display: block; width: 100%;"><textarea style="margin-top: 0; width: 100%;" name="selected_filters"></textarea></div>
		<input type="submit" name="submit" value="Set filters" />
		<input type="submit" name="submit" value="Add filters" />
	<?php } else if ($_REQUEST['selection'] == 'description' && xd_check_privileges (3)) { ?>
		<h4>Set description for the selected files</h4>
		<?php xd_edit_description (); ?>
	<?php }
	if ($_REQUEST['selection'] != 'description' && $_REQUEST['selection'] != 'filters') { ?><input type="submit" name="submit" value="<?php echo $_REQUEST['selection'];?>" /><?php } ?>
	<a href="#" onclick="xdivhide('actions'); return false;">cancel</a>
	<hr class="line" />
	<?php exit;
} else if (isset ($_REQUEST['selected']) && xd_check_privileges (6)) {
	$action = $_REQUEST['submit'];
	$ok = true;
	if ($action == 'copy' && xd_check_privileges (2)) {
		$target_dir = trim ($_REQUEST['edit_copy_to'], '/');
		if (strstr ($target_dir, '..')) {
			$_SESSION['message'] = 'ERROR: The target directory can not contain \'..\'!';
			$ok = false;
		}
		if (! is_dir (xd_get_root_dir() . $target_dir)) {
			$_SESSION['message'] = 'ERROR: The directory "/' . $target_dir . '" does not exist!';
			$ok = false;
		}
		if ($ok) {
			foreach ($_REQUEST['sfiles'] as $selected) {
				$selected = trim ($selected, '/');
				xd_copy ($selected, $target_dir . '/' . xd_basename ($selected));
			}
			$_SESSION['message'] = 'Copies of the selected files were successfully created.';
		}
	} else if ($action == 'move' && xd_check_privileges (2)) {
		$target_dir = trim ($_REQUEST['edit_move_to'], '/');
		if (strstr ($target_dir, '..')) {
			$_SESSION['message'] = 'ERROR: The target directory can not contain \'..\'!';
			$ok = false;
		}
		if (! is_dir (xd_get_root_dir() . $target_dir)) {
			$_SESSION['message'] = 'ERROR: The directory "/' . $target_dir . '" does not exist!';
			$ok = false;
		}
		if ($ok) {
			foreach ($_REQUEST['sfiles'] as $selected) {
				$selected = trim ($selected, '/');
				xd_move ($selected, $target_dir);
			}
			$_SESSION['message'] = 'The selected files were successfully moved.';
		}
	} else if ($action == 'delete' && xd_check_privileges (2)) {
		$_SESSION['message'] = 'Selected files were successfully deleted.';
		foreach ($_REQUEST['sfiles'] as $selected) { xd_delete ($selected); }
	} else if ($action == 'zip' && xd_check_privileges (6)) {
		$zip_name = $_REQUEST['edit_zip'];
		if (xd_extension_of ($zip_name) != 'zip') { $zip_name .= '.zip'; }
		xd_zip ($_REQUEST['sfiles'], $zip_name);
	} else if ($action == 'Edit description' && xd_check_privileges (3)) {
		$_SESSION['message'] = 'The description of the selected files was edited successfully.';
		foreach ($_REQUEST['sfiles'] as $selected) {
			xd_set_description ($selected, array ('text' => $_REQUEST['description_to_edit'], 'descrbackcolor' => $_REQUEST['descrbackcolor'], 'descrfontcolor' => $_REQUEST['descrfontcolor']));
		}
	} else if ($action == 'Add filters' && FILTERS && xd_check_privileges (3)) {
		$_SESSION['message'] = 'The filters of the selected files were edited successfully.';
		foreach ($_REQUEST['sfiles'] as $selected) { xd_set_filters ($selected, $_REQUEST['selected_filters'], true); }
	} else if ($action == 'Set filters' && FILTERS && xd_check_privileges (3)) {
		$_SESSION['message'] = 'The filters of the selected files were edited successfully.';
		foreach ($_REQUEST['sfiles'] as $selected) { xd_set_filters ($selected, $_REQUEST['selected_filters']); }
	}
	if (FILTERS) { $_SESSION['filters'] = xd_get_filters (); }
	header ('Location:' . $_SERVER['HTTP_REFERER']);
	exit;
} else if (isset ($_REQUEST['filters']) && xd_check_privileges (6)) { ?>
	<h4>Filters</h4>
	<hr class="line" />
	<ul style="list-style-type: none;">
	<?php xd_show_filters (xd_explode_tree ($_SESSION['filters'], '/')); ?>
	</ul>
	<?php exit;
} else if (isset ($_REQUEST['filter']) && xd_check_privileges (6)) {
	if (isset ($_SESSION['filters'][$_REQUEST['filter']])) { $_SESSION['files'] = $_SESSION['filters'][$_REQUEST['filter']]; }
	else { $_SESSION['message'] = 'ERROR: No files filtered by "' . $_REQUEST['filter'] . '"'; }
} else if (isset ($_REQUEST['simple_search']) && xd_check_privileges (6)) {
	$_SESSION['files'] = array ();
	$what = str_replace ('"', '', $_REQUEST['_simple_search_for']);
	if (! strlen (trim ($what))) {
		$_SESSION['message'] = 'WARNING: Searching for nothing returns everything! Why would you want that?';
		unset ($_SESSION['search']);
		header ('Location:' . $_SERVER['HTTP_REFERER']);
		exit;
	}
	if ($_found = xd_search ($what, $_REQUEST['_simple_search_in'])) { foreach ($_found as $found) { $_SESSION['files'][] = xd_derootify ($found); } }
	$_SESSION['search'] = $what;
	$_SESSION['message'] = 'Search for "' . $what . '".';
	header ('Location:' . $_SERVER['HTTP_REFERER']);
	exit;
} else if (isset ($_REQUEST['advanced_search']) && xd_check_privileges (6)) { ?>
	<h4>Advanced Search</h4>
	<small>(Wildcards [*] are supported)</small>
	<hr class="line" />
	<form method="post" action="<?php echo PHPSELF;?>?advanced_find" style="text-align: right;">
		<p>Search for 
		<select name="_advanced_search_type" style="display: inline;">
			<option value="files_and_directories" selected="selected">Files and Directories</option>
			<option value="files">Regular Files only</option>
			<option value="directories">Directories only</option>
		</select>: <input name="_advanced_search_for" type="text" /></p>
		<p>Case sensitive: <input type="checkbox" name="_advanced_search_case" /></p>
		<p>Search in descriptions: <input type="checkbox" name="_advanced_search_in_descriptions" /></p>
		<input type="hidden" name="_advanced_search_in" value="<?php echo $_REQUEST['advanced_search'];?>" />
		<hr class="line" />
		<input type="submit" name="submit" value="search" />
	</form>
	<?php exit;
} else if (isset ($_REQUEST['advanced_find']) && xd_check_privileges (6)) {
	$_SESSION['files'] = array ();
	$what = str_replace ('"', '', $_REQUEST['_advanced_search_for']);
	if (! strlen (trim ($what))) {
		$_SESSION['message'] = 'WARNING: Searching for nothing returns everything! Why would you want that?';
		unset ($_SESSION['search']);
		header ('Location:' . $_SERVER['HTTP_REFERER']);
		exit;
	}
	$cm = '';
	if ($_REQUEST['_advanced_search_case'] == 'on') { $cm = '(case sensitive) '; }
	if ($_found = xd_search ($what, $_REQUEST['_advanced_search_in'], true, $type = $_REQUEST['_advanced_search_type'], $_REQUEST['_advanced_search_case'], $_REQUEST['_advanced_search_in_descriptions'])) { foreach ($_found as $found) { $_SESSION['files'][] = xd_derootify ($found); } }
	$_SESSION['search'] = $what;
	$_SESSION['message'] = 'Search ' . $cm . 'for ' . str_replace ('_', ' ', $_REQUEST['_advanced_search_type']) . ': "' . $what . '".';
	header ('Location:' . $_SERVER['HTTP_REFERER']);
	exit;
} else if (isset ($_REQUEST['settings']) && xd_check_privileges (5)) {
	if (! strlen ($_REQUEST['settings'])) { ?>
		<br />
		<?php if (xd_check_privileges (1)) { ?>
			&nbsp;
			<div class="tab" id="prefs_tab" style="cursor: pointer;" onclick="stab('prefs_tab'); load('<?php echo PHPSELF;?>?settings=preferences', 'settingsblock'); return false;">Preferences</div>
			<div class="tab" id="layer_tab" style="cursor: pointer;" onclick="stab('layer_tab'); load('<?php echo PHPSELF;?>?settings=layers', 'settingsblock'); return false;">Layers</div>
			<div class="tab" id="style_tab" style="cursor: pointer;" onclick="stab('style_tab'); load('<?php echo PHPSELF;?>?settings=style', 'settingsblock'); return false;">Style</div>
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			<div class="tab" id="users_tab" style="cursor: pointer;" onclick="stab('users_tab'); load('<?php echo PHPSELF;?>?settings=users', 'settingsblock'); return false;">Users</div>
		<?php }
		if (! $_SESSION['remote_login']) { ?>
		<div class="tab" id="passw_tab" style="cursor: pointer;" onclick="stab('passw_tab'); load('<?php echo PHPSELF;?>?settings=password', 'settingsblock'); return false;">Password</div>
		<?php } ?>
		<div id="settingsblock">
			<?php if ($_SESSION['privileges'] == 1) { ?>
				<h4>You have <em>administrator</em> privileges and can make any changes to the system.</h4>
				<h4>Please click on the tab above to display a form for the desired properties.</h4>
			<?php } else if ($_SESSION['privileges'] == 2) { ?>
				<h4>You have <em>editor</em> privileges and may upload, transfer, create, copy, move, rename, delete and edit files, directories, and meta-information.</h4>
				<h4>Here you can change your password by clicking on the tab above.</h4>
			<?php } else if ($_SESSION['privileges'] == 3) { ?>
				<h4>You have <em>meta-user</em> privileges and may upload, transfer, create files and directories, and edit meta-information.</h4>
				<h4>Here you can change your password by clicking on the tab above.</h4>
			<?php } else if ($_SESSION['privileges'] == 4) { ?>
				<h4>You have <em>user</em> privileges and may upload, transfer, and create files and directories.</h4>
				<h4>Here you can change your password by clicking on the tab above.</h4>
			<?php } else if ($_SESSION['privileges'] == 5) { ?>
				<h4>You have <em>visitor</em> privileges and may view and download files and directories.</h4>
				<h4>Here you can change your password by clicking on the tab above.</h4>
			<?php } ?>
		</div>
	<?php } else if ($_REQUEST['settings'] == 'password') { ?>
		<form method="post" action="<?php echo PHPSELF;?>?change=pass">
			<input type="hidden" name="_set_pwd" value="<?php echo xd_pwd ($subdir);?>" />
			<div class="settingsusername">Username: </div><?php echo $_SESSION['username'];?>
			<div class="settingsrow"><strong>Old password</strong>: <input type="password" name="_set_old_password" /></div>
			<div class="settingsrow"><strong>New password</strong>: <input type="password" name="_set_new_password1" /></div>
			<div class="settingsrow"><strong>New password (retype)</strong>: <input type="password" name="_set_new_password2" /></div>
			<p><input type="submit" name="submit" value="change" /></p>
		</form>
	<?php } else if ($_REQUEST['settings'] == 'users' && xd_check_privileges (1)) { global $_users; ?>
		<form method="post" action="<?php echo PHPSELF;?>?change=users">
		<input type="hidden" name="_set_pwd" value="<?php echo xd_pwd ($subdir);?>" />
		<table style="width: 100%;" class="table">
		<thead>
		<tr>
		<th>Username</th>
		<th style="width: 200px;">Privileges <a href="#" onclick="toggle('levels_help'); return false;">(?)</a></th>
		<th>Home directory</th>
		</tr>
		</thead>
		<tbody>
		<?php foreach ($_users as $name => $user) { ?>
			<tr>
			<td class="settingsrow">
				<?php if ($name == $_SESSION['username']) { ?><strong><?php echo $name;?></strong><?php }
				else {
					echo $name; ?>
					<a href="<?php echo PHPSELF;?>?change=userdel&amp;name=<?php echo $name;?>" onclick="return checkDelete('user \'<?php echo $name;?>\'');" title="Delete the user '<?php echo $name;?>'!"><?php echo LABEL_DELETE;?></a>
				<?php } ?>
			</td>
			<td class="settingsrow">
				<select name="<?php echo $name;?>_privileges" onchange="document.getElementById('<?php echo $name; ?>_home').style.display = (this.selectedIndex == 0) ? 'none' : 'inline';"<?php if ($name == $_SESSION['username']) {?> disabled="disabled"<?php } ?>>
					<option value="1"<?php if ($user['privileges'] == 1) {?> selected="selected"<?php } ?>>Root (Full Privileges)</option>
					<option value="2"<?php if ($user['privileges'] == 2) {?> selected="selected"<?php } ?>>Editor</option>
					<option value="3"<?php if ($user['privileges'] == 3) {?> selected="selected"<?php } ?>>Meta-User</option>
					<option value="4"<?php if ($user['privileges'] == 4) {?> selected="selected"<?php } ?>>User</option>
					<option value="5"<?php if ($user['privileges'] == 5) {?> selected="selected"<?php } ?>>Visitor</option>
				</select>
			</td>
			<td class="settingsrow" style="text-align: left;">
				/<input type="text" id="<?php echo $name;?>_home" name="<?php echo $name;?>_home" value="<?php if ($user['privileges'] != 1) { echo trim ($user['home'], '/'); } ?>" style="width: 130px; display: <?php if ($user['privileges'] != 1) { echo 'inline'; } else { echo 'none'; } ?>;" />
			</td>
			</tr>
		<?php } ?>
		</tbody>
		<tfoot>
			<tr>
				<td colspan="3">
					Number of users: <?php echo count ($_users);?>
				</td>
			</tr>
		</tfoot>
		</table>
		<div id="levels_help" class="levels_help" style="display: none;">
			<h4>Levels of Privileges:</h4>
			<ol style="text-align: left;">
				<li>Root: full privileges.</li>
				<li>Editor: Upload, Transfer, Create, Copy, Move, Rename, Delete and Edit files/directories/meta-information.</li>
				<li>Meta-User: Upload, Transfer, Create files/directories, and Edit meta-information.</li>
				<li>User: Upload, Transfer, Create files/directories.</li>
				<li>Visitor: only view files/directories.</li>
			</ol>
		</div>

		<hr class="line" />
		Anonymous access<input type="checkbox" name="_set_anonymous" <?php if (ANONYMOUS) { ?>checked="checked " <?php } ?> onclick="document.getElementById('a_dir').style.display = this.checked ? 'block' : 'none';" />
		<div id="a_dir" style="display: <?php if (ANONYMOUS) { echo 'block'; } else { echo 'none'; } ?>">Public directory: <input type="text" name="_set_anonymous_dir" value="<?php if (ANONYMOUS) { echo ANONYMOUS; } ?>" /></div>

		<hr class="line" />
		<h4><a href="#" onclick="toggle('add_new_user'); return false;">Add new user</a></h4>
		<div id="add_new_user" style="display: none;">
			<div class="settingsrow">Username: <input type="text" name="_set_new_username" /></div>
			<div class="settingsrow">Password: <input type="password" name="_set_new_username_password1" /></div>
			<div class="settingsrow">Password (retype): <input type="password" name="_set_new_username_password2" /></div>
			<div class="settingsrow">Privileges: 
				<select name="_set_new_username_privileges" onchange="document.getElementById('_set_new_username_home').style.display = (this.selectedIndex == 0) ? 'none' : 'inline';">
					<option value="1" selected="selected">Root (Full Privileges)</option>
					<option value="2">Editor</option>
					<option value="3">Meta-User</option>
					<option value="4">User</option>
					<option value="5">Visitor</option>
				</select>
				Home directory:&nbsp;&nbsp;/<input type="text" id="_set_new_username_home" name="_set_new_username_home" style="display: none;" />
			</div>
		</div>
		<p><input type="submit" name="submit" value="submit" /></p>
		</form>

		<?php } else if ($_REQUEST['settings'] == "preferences" && xd_check_privileges (1)) { ?>
		<form method="post" action="<?php echo PHPSELF;?>?change=prefs">
		<input type="hidden" name="_set_pwd" value="<?php echo xd_pwd ($subdir);?>" />
		<div class="settingsrow"><acronym title="Set the title to be displayed."><strong>Title</strong></acronym>: <input type="text" name="_set_title" value="<?php echo TITLE;?>" /></div>
		<div class="settingsrow"><acronym title="Set your preferred date format. See php.net/date for details."><strong>Date format</strong></acronym>: <input type="text" name="_set_date_format" value="<?php echo DATE_FORMAT;?>"></div>
		<?php $zones = array ('America/Anchorage', 'America/Los_Angeles', 'America/Denver', 'America/Chicago', 'America/New_York', 'America/Caracas', 'America/Halifax', 'America/St_Johns', 'America/Argentina/Buenos_Aires', 'America/Sao_Paulo', 'Atlantic/South_Georgia', 'Atlantic/Azores', 'Europe/London', 'Europe/Berlin', 'Europe/Sofia', 'Europe/Moscow', 'Asia/Kuwait', 'Asia/Tehran', 'Asia/Muscat', 'Asia/Yekaterinburg', 'Asia/Kolkata', 'Asia/Katmandu', 'Asia/Dhaka', 'Asia/Rangoon', 'Asia/Krasnoyarsk', 'Asia/Brunei', 'Asia/Seoul', 'Australia/Darwin', 'Australia/Canberra', 'Asia/Magadan', 'Kwajalein', 'Pacific/Midway', 'Pacific/Honolulu', 'Pacific/Fiji', 'Pacific/Tongatapu'); ?>
		<div class="settingsrow"><acronym title="Set your Time Zone."><strong>Timezone</strong></acronym>: <select name="_set_timezone" style="display: inline; font: normal 8pt Arial, Helvetica;"><?php foreach ($zones as $zone) { ?><option value="<?php echo $zone;?>"<?php if ($zone == TIMEZONE) { ?> selected="selected"<?php } ?> ><?php echo $zone;?></option><?php } ?></select></div>
		<div class="settingsrow"><acronym title="Define the extensions of the files, which should be editable."><strong>Editable files</strong></acronym>: <input type="text" name="_set_editable" value="<?php echo EDITABLE;?>"></div>
		<div class="settingsrow"><acronym title="Define the width a picture should be previewed with."><strong>Image preview width</strong></acronym> <small>(in pixel)</small>: <input type="text" name="_set_img_width" value="<?php echo IMG_WIDTH;?>"></div>
		<div class="settingsrow"><acronym title="Enable tracking changes of files by saving older versions.">Enable Versioning</acronym>&nbsp;<input type="checkbox" name="_set_enable_versioning" <?php if (VERSIONING) { ?>checked="checked"<?php } ?> /></div>
		<div class="settingsrow"><acronym title="Turn on search options.">Enable search</acronym>&nbsp;<input type="checkbox" name="_set_search" <?php if (SEARCH) { ?>checked="checked"<?php } ?> /></div>
		<div class="settingsrow"><acronym title="Turn on the option to filter the files by additional criteria.">Enable filters</acronym>&nbsp;<input type="checkbox" name="_set_filters" <?php if (FILTERS) { ?>checked="checked"<?php } ?> /></div>
		<div class="settingsrow"><acronym title="Allow login by server authentication.">Enable server authentication</acronym>&nbsp;<input type="checkbox" name="_set_server_auth" <?php if (SERVER_AUTH) { ?>checked="checked"<?php } ?> /></div>
		<div class="settingsrow"><acronym title="Show hidden files and directories.">Show hidden</acronym>&nbsp;<input type="checkbox" name="_set_show_hidden" <?php if (SHOW_HIDDEN) { ?>checked="checked"<?php } ?> /></div>
		<div class="settingsrow"><acronym title="Define if the total size of all files/directories should be displayed as well as the number of files and directories contained.">Show total size</acronym>&nbsp;<input type="checkbox" name="_set_show_filesize" <?php if (SHOW_FILESIZE) { ?>checked="checked"<?php } ?> /></div>
		<strong>Columns view:</strong>
		<div class="settingsrow">
			Description of the file/directory <input type="checkbox" name="_set_description" <?php if (in_array ('description', explode (' ', TABLE_COLUMNS))) { ?>checked="checked"<?php } ?> /><br />
			Creation time <input type="checkbox" name="_set_ctime" <?php if (in_array ('ctime', explode (' ', TABLE_COLUMNS))) { ?>checked="checked"<?php } ?> /><br />
			Time of the last modification <input type="checkbox" name="_set_mtime" <?php if (in_array ('mtime', explode (' ', TABLE_COLUMNS))) { ?>checked="checked"<?php } ?> /><br />
			Time of the last access <input type="checkbox" name="_set_atime" <?php if (in_array ('atime', explode (' ', TABLE_COLUMNS))) { ?>checked="checked"<?php } ?> /><br />
			Size of the file/directory <input type="checkbox" name="_set_size" <?php if (in_array ('size', explode (' ', TABLE_COLUMNS))) { ?>checked="checked"<?php } ?> />
		</div>
		<p><input type="submit" name="submit" value="change" /></p>
		</form>

		<?php } else if ($_REQUEST['settings'] == 'layers' && xd_check_privileges (1)) { ?>
		<form method="post" action="<?php echo PHPSELF;?>?change=layers">
		<input type="hidden" name="_set_pwd" value="<?php echo xd_pwd ($subdir);?>" />
		<div style="text-align: left;">&nbsp;<acronym title="Set the content of the 'top' layer.">Top layer</acronym>:</div><textarea style="width: 500px; height: 360px;" name="_set_top_content"><?php echo htmlentities ($_top_content);?></textarea>
		<p><input type="submit" name="submit" value="change" /></p>
		</form>

		<?php } else if ($_REQUEST['settings'] == 'style' && xd_check_privileges (1)) { global $_style; ?>
		<form method="post" action="<?php echo PHPSELF;?>?change=style">
			<input type="hidden" name="_set_pwd" value="<?php echo xd_pwd ($subdir);?>" />
			<textarea name="_set_style" style="width: 500px; height: 380px; font: normal 8pt Courier;"><?php if (file_exists ('style.css')) { echo file_get_contents ('style.css'); } else { echo $_style; } ?></textarea>
			<p><input type="submit" name="submit" value="change" /></p>
		</form>
	<?php }
	exit;
} else if (isset ($_REQUEST['change']) && xd_check_privileges (5)) {
	global $_users;
	if ($_REQUEST['change'] == 'pass') {
		if (md5 ($_REQUEST['_set_old_password']) != $_users[$_SESSION['username']]['password']) {
			$_SESSION['message'] = "ERROR: Wrong password! Please identify yourself with the old password.";
			global $redirect; header ('Location:' . $redirect);
			exit;
		} else if ($_REQUEST['_set_new_password1'] != $_REQUEST['_set_new_password2']) {
			$_SESSION['message'] = "ERROR: New password incorrect retyped!";
			global $redirect; header ('Location:' . $redirect);
			exit;
		}
	} else if ($_REQUEST['change'] == 'users' && xd_check_privileges (1)) {
		global $_users;
		foreach ($_users as $name => $user) {
			$home = xd_clean (trim ($_REQUEST[$name . '_home'], '/'), true);
			xd_check_traverse ($home);
			if (! file_exists (ROOT_DIR . $home) || ! is_dir (ROOT_DIR . $home)) {
				$_SESSION['message'] = 'ERROR: the specified home directory "' . $home . '" does not exist or is not a directory!';
				header ('Location:' . $_SERVER['HTTP_REFERER']);
				exit;
			}
			$_REQUEST[$name . '_home'] = $home;
		}
		if (strlen (trim ($_REQUEST['_set_new_username']))) {
			if (strlen (trim ($_REQUEST['_set_new_username_password1']))) {
				foreach ($_users as $name => $user) {
					if ($_REQUEST['_set_new_username'] == $name) {
						$_SESSION['message'] = 'ERROR: A user with this name already exists!';
						global $redirect; header ('Location:' . $redirect);
						exit;
					}
				}
				if ($_REQUEST['_set_new_username_password1'] != $_REQUEST['_set_new_username_password2']) {
					$_SESSION['message'] = 'ERROR: New password incorrect retyped!';
					global $redirect; header ('Location:' . $redirect);
					exit;
				}
				if ($_REQUEST['_set_new_username_privileges'] != 1) {
					$home = xd_clean ($_REQUEST['_set_new_username_home'], true);
					xd_check_traverse ($home);
					if (! file_exists (ROOT_DIR . $home) || ! is_dir (ROOT_DIR . $home)) {
						$_SESSION['message'] = 'ERROR: the specified home directory "' . $home . '" does not exist or is not a directory!';
						header ('Location:' . $_SERVER['HTTP_REFERER']);
						exit;
					}
					$_REQUEST['_set_new_username_home'] = $home;
				}
			} else {
				$_SESSION['message'] = 'ERROR: The new user needs a password!';
				global $redirect; header ('Location:' . $redirect);
				exit;
			}
		}
	} else if ($_REQUEST['change'] == 'userdel' && xd_check_privileges (1)) {
		$post['todel'] = $_REQUEST['name'];
		if (xd_file_put_content ('config.php', xd_change_settings ($_REQUEST['change'], $post))) { $_SESSION['message'] = 'Successfully deleted the user \'' . $_REQUEST['name'] . '\'.'; }
		else { $_SESSION['message'] = 'ERROR: Deleting the user \'' . $_REQUEST['name'] . '\'!'; }
		header ('Location:' . $_SERVER['HTTP_REFERER']);
		exit;
	} else if ($_REQUEST['change'] == 'style' && xd_check_privileges (1)) {
		if (file_exists ("style.css")) {
			if (xd_file_put_content ("style.css", $_REQUEST['_set_style'])) { $_SESSION['message'] = 'Successfully edited the style.'; }
			else { $_SESSION['message'] = 'ERROR: Editing the settings!'; }
			global $redirect; header ('Location:' . $redirect);
			exit;
		}
	}
	$_SESSION['message'] = 'ERROR: Editing the settings!';
	if (xd_file_put_content ('config.php', xd_change_settings ($_REQUEST['change'], $_REQUEST))) { $_SESSION['message'] = 'Successfully edited the settings.'; }
	header ('Location:' . $_SERVER['HTTP_REFERER']);
	exit;
}
/* Actions end here. */

/* Check for important directories and create them if necessary. */
if (! xd_init ()) { die ('Can not start'); }

if (isset ($_SESSION['files'])) {
	$files = $_SESSION['files'];
	unset ($_SESSION['files']);
} else { $files = xd_get_files (xd_pwd ($subdir)); }

$p = '';
if (isset ($subdir) && strlen ($subdir)) { $p = $subdir . '/'; }
if ((FILTERS && isset ($_REQUEST['filter'])) || (SEARCH && isset ($_SESSION['search']))) { $p = ''; }
$allfiles = array ();
foreach ($files as $file) { $allfiles[] = $p . urlencode ($file); }

/* Start the HTML at last! :) */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php echo TITLE;?></title>
	<?php if (MOBILE) { ?>
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <meta name="HandheldFriendly" content="true"/>
        <meta name="MobileOptimized" content="320"/>
	<?php } ?>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<?php if (isset ($_REQUEST['parent'])) { ?><meta http-equiv="window-target" content="_top" /><meta http-equiv="refresh" content="0;url=<?php echo PHPSELF?>" /><?php exit; }
	if (xd_check_privileges (6)) { ?>
		<script language="JavaScript" type="text/javascript">
			//<![CDATA[
			var countselected=0;
			function stab(id){var _10=new Array(<?php echo $_SESSION['settings_tabs'];?>);for(i=0;i<_10.length;i++){document.getElementById(_10[i]).className="tab";}document.getElementById(id).className="stab";}var allfiles=new Array('<?php echo implode ("','", $allfiles);?>');
			//]]>
		</script>
		<script language="JavaScript" type="text/javascript" src="<?php echo PHPSELF?>js/xoda.js"></script>
		<script language="JavaScript" type="text/javascript" src="<?php echo PHPSELF?>js/sorttable.js"></script>
	<?php } ?>
	<?php if (isset ($_SESSION['message']) && ! strstr ($_SESSION['message'], 'ERROR')) { ?>
		<script language="JavaScript" type="text/javascript">
			//<![CDATA[
			setTimeout("xdivhide('status');", 10000);
			//]]>
		</script>
	<?php }
	if (MOBILE) { ?><link rel="stylesheet" href="<?php echo PHPSELF?>mobile.css" type="text/css" /><?php } else 
{ ?><link rel="stylesheet" href="<?php echo PHPSELF?>style.css" type="text/css" /><?php } ?>

</head>

<?php if ($_SESSION['login']) {
	global $subdir;
	if (isset ($_REQUEST['filter']) || ! SHOW_FILESIZE) { ?><body><?php }
	else if (isset ($_REQUEST['edit'])) { ?><body onload="document.editform.editfile.focus();"><?php }
	else if (! $_SESSION['search']) { ?><body onload="load('<?php echo PHPSELF;?>?dirinfo=<?php echo $subdir;?>', 'bottom');"><?php }
	else { ?><body><?php }
} else { ?><body onload="document.lform.username.focus();">
	<div id="top">
		<a href="<?php echo PHPSELF;?>" title="<?php echo TITLE;?>"><?php echo $_top_content;?></a>
		<?php if (isset ($_SESSION['message'])) { ?><div id="status" style="display: block;" class="highlight"><?php echo $_SESSION['message'];?></div><?php } ?>
	</div>
	<form method="post" action="<?php echo PHPSELF;?>?log_in" name="lform" id="login">
		<p>Username:&nbsp;<input type="text" id="un" name="username" /></p>
		<p>Password:&nbsp;<input type="password" name="password" /></p>
		<p><input type="submit" name="submit" value="login" /></p>
	</form>
</body>
</html>
	<?php unset ($_SESSION['message'], $_SESSION['search'], $_style, $redirect, $_loading, $_colors, $_top_content, $_files, $_file_list, $subdir, $_filters); exit;
} ?>

<div id="top">
	<a href="<?php echo PHPSELF;?>" title="<?php echo TITLE;?>"><?php echo $_top_content;?></a>
	<?php if (isset ($_SESSION['message'])) { ?><div id="status" style="display: block;" class="highlight"><?php echo htmlentities($_SESSION['message']);?></div><?php } ?>
</div>

<div id="nav">
	<?php if (SEARCH) { ?>
		<form method="post" style="display: inline;" action="<?php echo PHPSELF;?>?simple_search">
			<input name="_simple_search_in" type="hidden" value="<?php echo xd_pwd ($subdir);?>" />
			<input name="_simple_search_for" type="text" /><input type="submit" name="submit" value="go" />
			<?php if (MOBILE) { ?><br /><?php } ?>
			<a href="#" onclick="xdoverlay('450');load('<?php echo PHPSELF;?>?advanced_search=<?php echo xd_pwd ($subdir);?>', 'action'); return false;" title="Advanced Search">Advanced Search</a>
		</form>
	<?php } ?>&nbsp;&nbsp;&nbsp;
	<?php if (! $_SESSION['remote_login'] && xd_check_privileges (5)) { ?><a href="#" onclick="xdoverlay('510');load('<?php echo PHPSELF;?>?settings', 'action'); return false;" title="Edit settings">Settings</a><?php } ?>
	<?php if (isset ($_SESSION['remote_login']) && ! $_SESSION['remote_login']) { ?><a href="<?php echo PHPSELF;?>?logout" title="Logout">Logout</a><?php } ?>
	<?php if ($_SESSION['anonymous']) { ?><a href="#" onclick="xdoverlay('220');load('<?php echo PHPSELF;?>?login', 'action'); return false;" title="Login">Login</a><?php } ?>
</div>

<div id="current">
	<?php if ($subdir) {
		$links = $subdir . '&nbsp;<a href="' . PHPSELF . $subdir . '" title="Reload" class="tab">Reload</a>&nbsp;';
		if (count (explode ('/', trim ($subdir, '/'))) < 2) { $links .= '<a href="' . PHPSELF . '" class="tab">' . LABEL_UP_DIR . '</a>'; }
		else { $links .= '<a href="' . PHPSELF . dirname ($subdir) . '" class="tab">' . LABEL_UP_DIR . '</a>'; }
	} else { $links = '&nbsp;<a href="' . PHPSELF . '" title="Reload" class="tab">Reload</a>'; } ?>
	<?php if (FILTERS && count ($_SESSION['filters']) && isset ($_SESSION['filters'][$_REQUEST['filter']])) { ?>Search for filter "<?php echo $_REQUEST['filter'];?>"<?php }
	else if (SEARCH && isset ($_SESSION['search'])) { ?>Results for search "<?php echo htmlentities($_SESSION['search']); ?>" in /<?php echo $links;?><?php }
	else { ?>Current directory: /<?php echo $links;?>&nbsp;<?php } ?>
</div>

<div id="act">
	<?php if (FILTERS && count ($_SESSION['filters'])) { ?><a href="#" onclick="xdoverlay('350');load('<?php echo PHPSELF;?>?filters', 'action'); return false;" title="Filters" style="margin-right: 30px;"><span class="tab">Filters</span></a><?php } ?>
	<?php if (xd_check_privileges (4) && ! (SEARCH && isset ($_SESSION['search'])) && (! FILTERS || ! isset ($_REQUEST['filter']) || ! isset ($_SESSION['filters'][$_REQUEST['filter']]))) { ?>
		<a href="#" onclick="xdoverlay('350');load('<?php echo PHPSELF;?>?upload_to=<?php echo xd_pwd ($subdir);?>', 'action'); return false;" title="Upload file(s) to this directory"><span class="tab">Upload</span></a>
		<a href="#" onclick="xdoverlay('200');load('<?php echo PHPSELF;?>?transfer_to=<?php echo xd_pwd ($subdir);?>', 'action'); return false;" title="Transfer a file from the web" ><span class="tab">Transfer</span></a>
		<a href="#" onclick="xdoverlay('210');load('<?php echo PHPSELF;?>?create_in=<?php echo xd_pwd ($subdir);?>', 'action'); return false;" title="Create a file or a directory"><span class="tab">Create</span></a>
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<?php } ?>
</div>

<div id="main">
	<div class="table"><?php
		global $files;
		if (count ($files)) {
			if (xd_check_privileges (6)) { ?><span id="lectall" style="display: inline;">Select: <a href="#" onclick="xd_selectAll(); return false;">All</a>, <a href="#" onclick="xd_deselectAll(); return false;">None</a></span><?php }
		}
		if (! empty ($files)) { asort ($files); }
		xd_list_them ($files); ?>
	</div>
</div>

<div id="overlay"></div>
<div id="overoverlay">
	<div id="oclose"><a href="#" onclick="xdunderlay();return false;" title="Close">(x)</a>&nbsp;&nbsp;</div>
	<div id="action"></div>
</div>

</body>
</html>
<?php unset ($_SESSION['message'], $_SESSION['search'], $_style, $redirect, $_loading, $_colors, $_top_content, $_files, $_file_list, $subdir, $_filters); exit; ?>
