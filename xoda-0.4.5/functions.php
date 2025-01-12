<?php
require_once ('config.php');
setlocale (LC_CTYPE, 'en_US.UTF-8');

function xd_init () {
	if (! file_exists ('.htaccess')) {
		$htaccess = 'RewriteEngine On' . "\n" . 'RewriteCond %{REQUEST_FILENAME} !-f' . "\n" . 'RewriteCond %{REQUEST_FILENAME} !-d' . "\n" . 'RewriteRule ^(.*)$ ' . PHPSELF . '?action=$1 [L,QSA]' . "\n" . 'Options All -Indexes' . "\n";
		if (! xd_file_put_content ('.htaccess', $htaccess)) {
			echo '<h1>The file \'.htaccess\' could not be written!</h1>' . "\n" . 'This means that you most probably do not have write permissions to the web directory as web user. This is not a bad idea and should not discourage you once you have set ROOT_DIR and META_DIR to paths outside the web directory where the server (Apache) has write permissions (e.g. you home directory, like recommended in the <a href="http://xoda.org/article/xoda-manual" target="_blank">XODA-Manual</a>). In this case you should place the following in a file named \'.htaccess\' in the directory where XODA was installed:' . '<pre>' . $htaccess . '</pre>';
			exit;
		}
	}
	if (! file_exists (xd_get_root_dir())) { xd_mkdirp (xd_get_root_dir()); }
	if (! file_exists (META_DIR)) { xd_mkdirp (META_DIR); }
	return true;
}

function xd_check_privileges ($level=false) {
	if ($level && $_SESSION['privileges'] <= $level) { return true; }
	return false;
}

// Return the current rootdir
function xd_get_root_dir() {
	if (isset ($_SESSION['rootdir']) ) { return rtrim ($_SESSION['rootdir'], '/') . '/'; }
	return ROOT_DIR;
}

function xd_basename ($path) { return end (explode ('/', trim ($path, '/'))); }

function xd_derootify ($path) { return str_replace (xd_get_root_dir(), '', $path); }

function xd_mkdirp ($path, $mode = 0755) {
	xd_check_traverse ($path);
	return mkdir ($path, $mode, true);
}

function xd_pwd ($subdir = false) {
	if ($subdir) {
		if (strstr ($subdir, '..') || $subdir == '.') { return ''; }
		return trim ($subdir, '/');
	}
	return '';
}

function xd_count_files ($dir, $what, &$count) {
	xd_check_traverse ($dir);
	if ($files = glob ($dir . '/*')) {
		foreach ($files as $file) {
			if (!is_dir ($file)) { ++$count['f']; }
			else { ++$count['d']; xd_count_files ($file . '/', $what, $count); }
		}
	}
	if (!empty ($count["$what"])) { return $count["$what"]; }
	return 0;
}

function xd_get_dir_content ($dir) {
	xd_check_traverse ($dir);
	$dir_content = array (); 
	if ( $dh = opendir ($dir) ) {
		while (($file = readdir($dh)) !== false) { if (($file != '.') && ($file != '..')) { $dir_content[] = $file; } }
	}
	return $dir_content;
}

function xd_get_dirs ($dir) {
	static $dirs = array();
	$_dirs = glob (trim ($dir, '/') . '/*', GLOB_ONLYDIR);
	if (count ($_dirs) > 0) {
		foreach ($_dirs as $_dir) {
			$dirs[] = $_dir;
			xd_get_dirs ($_dir);
		}
	}
	return $dirs;
}

function xd_get_files ($dir) {
	xd_check_traverse ($dir);
	$_files = xd_get_dir_content (xd_get_root_dir() . $dir);
	$files = array ();
	foreach ($_files as $_file) { if ($_file[0] != '.' || SHOW_HIDDEN) { $files[] = $_file; } }
	return $files;
}

function xd_rmrf ($o) {
	if (is_dir ($o)) {
		$objects = scandir ($o);
		foreach ($objects as $object) {
			if ($object != "." && $object != "..") {
				if (is_dir ($o . '/' . $object)) { xd_rmrf ($o . '/' . $object); }
				else { if (! unlink ($o . '/' . $object)) { return false; } }
			}
		}
		reset ($objects);
		return rmdir ($o);
	} else { return unlink ($o); }
}

function xd_cpr ($from, $to) {
	if (file_exists ($to)) { xd_rmrf ($to); }
	if (is_dir ($from)) {
		if (! mkdir ($to)) { return false; }
		$files = scandir ($from);
		foreach ($files as $file) {
			if ($file != "." && $file != "..") {
				if (! xd_cpr ("$from/$file", "$to/$file")) { return false; }
			} 
		}
	} else if (file_exists ($from)) { return copy ($from, $to); }
	return true;
}

function xd_file_content_type ($filename) {
	xd_check_traverse ($filename);
	if (is_dir ($filename)) { return 'directory'; }
	else {
		if (UNIX_FILEINFO_TYPE && $type = exec ("file -b '" . htmlspecialchars ($filename) . "'")) { return trim ($type); }
		else if ($finfo = finfo_open (FILEINFO_MIME_TYPE) && $mime = finfo_file ($finfo, $file) && finfo_close ($finfo)) { return $mime; }
		else if ($ext = substr (strrchr ($filename, '.'), 1)) { return $ext . '-File'; }
		else { return 'Unknown Filetype'; }
	}
}

function xd_file_size ($filename, $unit) {
	xd_check_traverse ($filename);
	$size = 0; 
	if (is_dir ($filename)) { foreach (new RecursiveIteratorIterator (new RecursiveDirectoryIterator ($filename)) as $file) { $size+=$file->getSize(); } }
	else { $size = filesize ($filename); }
	if ($size < 1024) { return $size . ' '; }
	elseif ($size < 1048576) { return round ($size / 1024) .' K'; }
	elseif ($size < 1073741824) { return round ($size / 1048576) . ' M'; }
	elseif ($size < 1099511627776) { return round ($size / 1073741824) . ' G'; }
}

function xd_max_upload_size () {
	$umf = ini_get ('upload_max_filesize');
	$pms = ini_get ('post_max_size');
	if ($umf > $pms) { return $umf; }
	return $pms;
}

function xd_file_info ($_filename) {
	$_filename = urldecode ($_filename);
	xd_check_traverse ($_filename);
	$filename = xd_get_root_dir() . $_filename;

	if (! file_exists ($filename)) {
		echo "File not found";
		return;
	}
	$fileinfo = array();

	if (in_array (xd_extension_of ($_filename), explode (' ', IMG_EXTENSIONS))) {
		$fileinfo['Preview'] = '<p><a href="' . PHPSELF . '?download=' . $_filename . '" target="_blank"><img src="' . PHPSELF . '?download=' . $_filename . '" width="' . IMG_WIDTH . '" /></a></p>';
//	} else if (xd_extension_of ($_filename) == 'pdf') {
//		global $redirect;
//		$fileinfo['Preview'] = '<p><embed name="acrodoc" src="' . $redirect . '?download=' . $_filename . '" hidden="false" width="450" height="200" /></p>';
	} else if (in_array (xd_extension_of ($_filename), explode (' ', EDITABLE))) {
		$content = htmlspecialchars (file_get_contents ($filename));
		$fileinfo['Preview'] = '<div class="bq">' . $content . '</div>';
	}

	$fileinfo['Name'] = xd_basename ($filename);
	$fileinfo['Location'] = '/' . trim (xd_derootify (dirname ($filename) . '/'), '/');
	$fileinfo['Type'] = xd_file_content_type ($filename);
	$fileinfo['Size'] = xd_file_size ($filename, 'h') . 'B';

	if ($isdir = is_dir ($filename)) {
		$fileinfo['Files'] = @xd_count_files ($filename, 'f');
		$fileinfo['Subdirectories'] = @xd_count_files ($filename, 'd');
	}

	$fileinfo['Created'] = date (DATE_FORMAT, filectime ($filename));
	$fileinfo['Last_modified'] = date (DATE_FORMAT, filemtime ($filename));

	$fileinfo['Description'] = xd_unhtmlentities (xd_description_of ($_filename));

	$meta_file = xd_metafile_of ($_filename);

	$fileinfo['Filters'] = array ();
	if (FILTERS && file_exists ($meta_file) && include ($meta_file)) { $fileinfo['Filters'] = $_filters; }

	if (VERSIONING && ! $isdir && $fileinfo['Name'][0] != '.' && xd_check_privileges (2)) {
		$file = xd_basename ($_filename);
		$versions = glob (xd_version_dir_of ($_filename) . '/*-' . $file);
		if (! empty ($versions)) {
			rsort ($versions);
			$v = "";
			foreach ($versions as $version) {
				$bn = xd_basename ($version);
				$n = str_replace ('-' . $file, '', $bn);
				$v .= '<p class="version">';
				$v .= '<a href="' . PHPSELF . '?version=' . $n . '&amp;file=' . $_filename . '">' . date (DATE_FORMAT, filectime ($version)) . '</a>';
				$v .= '<a href="' . PHPSELF . '?delete_version=' . $n . '&amp;file=' . $_filename . '" onclick="return checkDelete(\'this version\');" title="Delete this version.">' . LABEL_DELETE . '</a>';
				$v .= '<span id="' . $bn . '-description">' . xd_description_of ($_filename, false, true, $n);
				$v .= '<a href="#" title="Edit the description of this version" onclick="load(\'' . PHPSELF . '?edit_vdescription=' . $n . '&amp;file=' . $_filename . '\', \'' . $bn . '-description\'); return false">' . LABEL_EDIT . '</a>';
				$v .= '</span>';
				$v .= '</p>';
			}
		} else { $v = '<h4>No old versions saved.</h4>'; }
		$fileinfo['Versions'] = '<a href="#" onclick="load(\'' . PHPSELF . '?save_version=' . $_filename . '\', \'versioning\'); return false;">Save Version</a><div id="versioning"></div>' . $v;
	}
	return $fileinfo;
}

function xd_col_description ($f, $h=false) {
	if ($h) { return '<th class="head_description">Description</th>'; }
	return xd_unhtmlentities (xd_description_of ($f));
}

function xd_col_size ($f, $h=false, $sc=false) {
	if ($h) { return '<th class="head_size">Size</th>'; }
	if (is_link ($f)) { $lsinfo = lstat ($f); }
	if ($sc) { if (is_link ($f)) { return $lsinfo[7]; } return xd_file_size ($f, 'b'); }
	if (is_link ($f)) { return $lsinfo[7] . ' B'; } return xd_file_size ($f, 'h') . 'B';
}

function xd_col_atime ($f, $h=false, $sc=false) {
	if ($h) { return '<th class="head_time">Last Access</th>'; }
	if (is_link ($f)) { $lsinfo = lstat ($f); }
	if ($sc) { if (is_link ($f)) { return $lsinfo[8]; } return fileatime ($f); }
	if (is_link ($f)) { return date (DATE_FORMAT, $lsinfo[8]); } return date (DATE_FORMAT, fileatime ($f));
}

function xd_col_mtime ($f, $h=false, $sc=false) {
	if ($h) { return '<th class="head_time">Last Modification</th>'; }
	if (is_link ($f)) { $lsinfo = lstat ($f); }
	if ($sc) { if (is_link ($f)) { return $lsinfo[9]; } return filemtime ($f); }
	if (is_link ($f)) { return date (DATE_FORMAT, $lsinfo[9]); } return date (DATE_FORMAT, filemtime ($f));
}

function xd_col_ctime ($f, $h=false, $sc=false) {
	if ($h) { return '<th class="head_time">Creation</th>'; }
	if (is_link ($f)) { $lsinfo = lstat ($f); }
	if ($sc) { if (is_link ($f)) { return $lsinfo[10]; } return filectime ($f); }
	if (is_link ($f)) { return date (DATE_FORMAT, $lsinfo[10]); } return date (DATE_FORMAT, filectime ($f));
}

function xd_create_row ($filename) {
	global $subdir, $icon_dir, $icon_file;
	$base_name = xd_basename ($filename);
	$file_ext = xd_extension_of ($filename);
	if ($subdir && ! isset ($_SESSION['search'])) { $filename = trim ($subdir, '/') . '/' . $base_name; }
	$filepath = xd_get_root_dir() . $filename;

	$ed = '';
	if (is_dir ($filepath)) {
		if (isset ($subdir)) { }
		$dl = '<a href="' . PHPSELF . $filename . '" >' . $base_name . '/</a>';
		if (file_exists (ICONS_DIR . 'dir.png')) { $li = '<img src="' . PHPSELF . ICONS_DIR . 'dir.png" alt="Directory" />'; }
		else { $li = '<img src="data:image/gif;base64,' . $icon_dir . '" alt="Directory" />'; }
		$fcs = 'sorttable_customkey="dir"';
	} else {
		$dl = '<a href="' . PHPSELF . '?download=' . urlencode ($filename) . '&amp;force" title="Download \'' . $base_name . '\'">' . LABEL_DOWNLOAD . '</a>&nbsp;';
		$dl .= '<a href="' . PHPSELF . '?download=' . urlencode ($filename) . '" target="_blank">' . $base_name . '</a>';
		if (xd_check_privileges (2) && in_array ($file_ext, explode (' ', EDITABLE))) { $ed = '<a href="#" onclick="xdoverlay(\'620\');load(\'' . PHPSELF . '?edit=' . $filename . '\', \'action\'); return false;" title="Edit file \'' . $base_name . '\'">' . LABEL_EDIT . '</a>'; }
		if ($file_ext) { $fcs = 'sorttable_customkey="file-' . $file_ext . '"'; }
		else { $fcs = 'sorttable_customkey="file"'; }
		if (file_exists (ICONS_DIR . $file_ext . '.png')) { $li = '<img src="' . PHPSELF . ICONS_DIR . $file_ext . '.png" alt="' . $file_ext . '-file" />'; }
		else if (file_exists (ICONS_DIR . 'file.png')) { $li = '<img src="' . PHPSELF . ICONS_DIR . 'file.png" alt="' . $file_ext . '-file" />'; }
		else { $li = '<img src="data:image/gif;base64,' . $icon_file . '" alt="file" />'; }
	}
	$ftype = '<a href="#" onclick="xdoverlay(\'500\');load(\'' . PHPSELF . '?info=' . $filename . '\', \'action\'); return false;">' . $li . '</a>';
	$efn = urlencode ($filename);?>
	<tr id="row_<?php echo $efn;?>" class="row">
		<td class="col_type" <?php echo $fcs;?>>
			<?php echo $ftype . '&nbsp;' . $ed;?>
		</td>
		<td class="col_filename" sorttable_customkey="<?php echo $base_name;?>">
			<?php if (xd_check_privileges (6)) { ?>
				<input type="checkbox" id="cb_<?php echo $efn;?>" name="sfiles[]" value="<?php echo $filename;?>" onchange="if(this.checked) { selectFile('<?php echo $efn;?>'); } if(!this.checked) { removeSelected('<?php echo $efn;?>'); }" />
			<?php } ?>
			<?php echo $dl;?>
			<?php if (MOBILE) { echo '<div class="m_desc">' . xd_col_description ($filename) . '</div>'; } ?>
		</td>
		<?php if (! MOBILE) {
			$columns = explode (' ', TABLE_COLUMNS);
			if (in_array ('description', $columns)) { ?><td class="col_description"><?php echo xd_col_description ($filename);?></td><?php }
			if (in_array ('atime', $columns)) { ?><td class="col_time" sorttable_customkey="<?php echo xd_col_atime ($filepath, false, true);?>"><?php echo xd_col_atime ($filepath);?></td><?php }
			if (in_array ('mtime', $columns)) { ?><td class="col_time" sorttable_customkey="<?php echo xd_col_mtime ($filepath, false, true);?>"><?php echo xd_col_mtime ($filepath);?></td><?php }
			if (in_array ('ctime', $columns)) { ?><td class="col_time" sorttable_customkey="<?php echo xd_col_ctime ($filepath, false, true);?>"><?php echo xd_col_ctime ($filepath);?></td><?php }
			if (in_array ('size', $columns)) { ?><td class="col_size" sorttable_customkey="<?php echo xd_col_size ($filepath, false, true);?>"><?php echo xd_col_size ($filepath);?></td><?php }
		} ?>
	</tr>
	<?php
}

function xd_list_them ($files) {
	if (!empty ($files)) {
		$columns = 0;
		if (! MOBILE) { $columns = explode (' ', TABLE_COLUMNS); }
		usort ($files, 'xd_cmp');
		$tc = false;
		if (strlen (TABLE_COLUMNS) > 2) { $tc = true; }
		global $subdir; $subdir_url = ''; if ($subdir) { $subdir_url = $subdir; } ?>
		<form method="post" id="sform" name="selected" style="display: inline;" action="<?php echo PHPSELF;?><?php echo $subdir_url;?>?selected">
		<div id="selection" style="display: none;">
			&nbsp;&nbsp;&nbsp;<span id="sfNumber"></span>&nbsp;&nbsp;&nbsp; Action:
			<?php if (xd_check_privileges (2)) { ?>
				<a href="#" onclick="xdivshow('actions'); load('<?php echo PHPSELF;?>?selection=copy', 'actions'); return false;">copy</a>,
				<a href="#" onclick="xdivshow('actions'); load('<?php echo PHPSELF;?>?selection=move', 'actions'); return false;">move</a>,
				<a href="#" onclick="xdivshow('actions'); load('<?php echo PHPSELF;?>?selection=delete', 'actions'); return false;">delete</a>,
			<?php } ?>
			<?php if (xd_check_privileges (6)) { ?>
				<a href="#" onclick="xdivshow('actions'); load('<?php echo PHPSELF;?>?selection=zip', 'actions'); return false;">zip</a>
			<?php } ?>
			<?php if (xd_check_privileges (3)) { ?> | Set:
				<a href="#" onclick="xdivshow('actions'); load('<?php echo PHPSELF;?>?selection=description', 'actions'); return false;">description</a><?php if (FILTERS) {?>,
				<a href="#" onclick="xdivshow('actions'); load('<?php echo PHPSELF;?>?selection=filters', 'actions'); return false;">filters</a><?php } ?>.
			<?php } ?>
		</div>
		<div id="actions" style="width: 550px; display: none;"></div>
		<table class="sortable">
		<thead>
			<tr>
				<th class="head_type">Type</th>
				<th class="head_filename">Filename</th>
				<?php if (! MOBILE && $tc) { foreach ($columns as $col) { $c = 'xd_col_' . $col; echo $c (false, true); } } ?>
			</tr>
		</thead>
		<tfoot><tr><td colspan="<?php if (! MOBILE && $tc) { echo count ($columns) + 2; } else if (MOBILE) { echo "2"; } else { echo "0"; } ?>" id="bottom"></td></tr></tfoot>
		<tbody>
			<?php foreach ($files as $single_file) { xd_create_row ($single_file); } ?>
		</tbody>
		</table>
		</form><?php
	} else {
		echo "<p><h3>No files.</h3></p>";
	}
}

function xd_extension_of ($filename) {
	return strtolower (substr (strrchr ($filename, '.'), 1));
}

function xd_metafile_of ($filename, $version=false) {
	if ($version) { return (xd_version_dir_of ($filename) . '/.' . $version . '-' . xd_basename ($filename) . '.php'); }
	return (META_DIR . trim (xd_get_root_dir(), '/') . '/' . trim ($filename, '/') . '.php');
}

function xd_version_dir_of ($filename) {
	$bn = trim (dirname ($filename), '/') . '/.';
	if ($bn == './.') { $bn = '.'; }
	return (META_DIR . xd_get_root_dir() . $bn . xd_basename ($filename));
}

function xd_description_of ($filename, $more=false, $long=true, $version=false) {
	$filename = trim ($filename, '/');
	$base_name = xd_basename ($filename);
	$meta_file = xd_metafile_of ($filename, $version);
	if (file_exists ($meta_file)) {
		include ($meta_file);
		if (is_string ($_description)) { return $_description; }
		if (! $long) { return $_description[$more]; }
		if ($more) { return $_description[$more]; }
		return '&nbsp;<span style="background: ' . $_description['descrbackcolor'] . '; color: ' . $_description['descrfontcolor'] . '; padding: 2px;">' . $_description['text'] . '</span>';
	}
	return false;
}

function xd_edit_description ($filename=false, $version=false) {
	if ($filename) {
		$_file = trim ($filename, '/');
		if (! ($descrfontcolor = xd_description_of ($_file, 'descrfontcolor', false, $version))) { $descrfontcolor = 'inherit'; }
		if (! ($descrbackcolor = xd_description_of ($_file, 'descrbackcolor', false, $version))) { $descrbackcolor = 'inherit'; }
		$text = xd_unhtmlentities (xd_description_of ($_file, 'text', false, $version));
	} else {
		$descrfontcolor = $descrbackcolor = 'inherit';
		$text = '';
	}
	$curcolors = 'background: ' . $descrbackcolor . '; color: ' . $descrfontcolor . ';';
	global $_colors;
	$id = 'colorboxes'; if ($version) { $id = 'vcolorboxes'; }
	$spreviewid = 'spreview'; if ($version) { $spreviewid = 'vspreview'; } ?>
	<strong>Description:</strong>
	<p><span id="<?php echo $spreviewid; ?>" style="padding: 1px; border: 1px solid #011223; <?php echo $curcolors;?> cursor: pointer;" onclick="toggle('<?php echo $id;?>'); return false;">Style</span></p>
	<div id="<?php echo $id; ?>" style="display: none; padding: 5px;">
	<?php foreach ($_colors as $b => $f) {
		if ($descrfontcolor == $f) { $marker = '&nbsp;&radic;&nbsp;'; }
		else { $marker = '&nbsp;&bull;&nbsp;'; }
		if ($b != 'inherit') { ?><span onclick="document.getElementById('descrfontcolor').value='<?php echo $f;?>'; document.getElementById('descrbackcolor').value='<?php echo $b;?>'; document.getElementById('<?php echo $spreviewid; ?>').style.background='<?php echo $b;?>'; document.getElementById('<?php echo $spreviewid; ?>').style.color='<?php echo $f;?>'; xdivhide('<?php echo $id;?>'); return false;" style="background: <?php echo $b;?>; color: <?php echo $f;?>; padding: 3px; cursor: pointer;"><?php echo $marker;?></span>&nbsp;<?php }
		else { ?><span onclick="document.getElementById('descrfontcolor').value='<?php echo $f;?>'; document.getElementById('descrbackcolor').value='<?php echo $b;?>'; document.getElementById('<?php echo $spreviewid; ?>').style.background=''; document.getElementById('<?php echo $spreviewid; ?>').style.color=''; xdivhide('<?php echo $id;?>'); return false;" style="background: <?php echo $b;?>; color: <?php echo $f;?>; padding: 3px; cursor: pointer;"><?php echo $marker;?></span>&nbsp;<?php }
	} ?>
	<br /><br />
	<?php foreach ($_colors as $b => $f) {
		if ($b == 'inherit') { continue; }
		if ($descrfontcolor == $b) { $marker = '&nbsp;&radic;&nbsp;'; }
		else { $marker = '&nbsp;&bull;&nbsp;'; } ?>
		<span onclick="document.getElementById('descrfontcolor').value='<?php echo $b;?>'; document.getElementById('descrbackcolor').value='<?php echo $f;?>'; document.getElementById('<?php echo $spreviewid; ?>').style.background='<?php echo $f;?>'; document.getElementById('<?php echo $spreviewid; ?>').style.color='<?php echo $b;?>'; xdivhide('<?php echo $id;?>'); return false;" style="background: <?php echo $f;?>; color: <?php echo $b;?>; padding: 3px; cursor: pointer;"><?php echo $marker;?></span>&nbsp;
	<?php } ?>
	</div>
	<p><textarea style="margin-top: 0; width: 400px; height: 100px;" name="description_to_edit"><?php echo $text;?></textarea></p>
	<input type="hidden" id="descrbackcolor" name="descrbackcolor" value="<?php echo $descrbackcolor;?>" />
	<input type="hidden" id="descrfontcolor" name="descrfontcolor" value="<?php echo $descrfontcolor;?>" />
	<?php if (! $version) { ?><input type="submit" name="submit" value="Edit description" /><?php }
	return true;
}

function xd_set_description ($filename, $_d, $version=false) {
	xd_check_traverse ($filename);
	$meta_file = xd_metafile_of ($filename, $version);
	if (file_exists ($meta_file)) { include ($meta_file); }
	$f = '';
	if (! $version) { if (count ($_filters)) { $f = '$_filters = ' . var_export ($_filters, true) . ";\n"; } }
	if ($_d['descrbackcolor'] != 'none' && $_d['descrfontcolor'] != 'none') {
		$d['descrbackcolor'] = $_d['descrbackcolor'];
		$d['descrfontcolor'] = $_d['descrfontcolor'];
	}
	$d['text'] = str_replace (array ('&gt;', '&lt;', '&quot;', '&amp;'), array ('>', '<', '"', '&'), xd_htmlentities ($_d['text']));
	if (strlen ($d['text'])) { $meta_file_content = "<?php\n\$_description = " . var_export ($d, true) . ";\n" . $f . '?>'; }
	else { $meta_file_content = "<?php\n" . $f . '?>'; }
	if (! xd_file_put_content ($meta_file, $meta_file_content)) {
		$_SESSION['message'] = 'ERROR: The description of "' . $filename . '" could not be edited!';
		global $redirect; header ('Location:' . $redirect . $subdir_url);
	}
	return true;
}

function xd_set_filters ($filename, $_f, $add=false) {
	$meta_file = xd_metafile_of ($filename);
	if (file_exists ($meta_file)) { include ($meta_file); }
	$d = ''; if (count ($_description)) { $d = '$_description = ' . var_export ($_description, true) . ";\n"; }
	$filters = explode ("\n", $_f);
	foreach ($filters as $filter) {
		$_filter = trim (trim ($filter), '/');
		if (strlen ($_filter)) { $f[] = str_replace (array (' /', '/ '), '/', $_filter); }
	}
	if ($add) { foreach ($_filters as $_filter) { $f[] = $_filter; } }
	sort ($f);
	if (count ($f)) { $meta_file_content = "<?php\n" . $d . '$_filters = ' . var_export (array_unique ($f), true) . ";\n?>"; }
	else { $meta_file_content = "<?php\n" . $d . '?>'; }
	if (! xd_file_put_content ($meta_file, $meta_file_content)) {
		$_SESSION['message'] = 'ERROR: The description of "' . $filename . '" could not be edited!';
		global $redirect; header ('Location:' . $redirect . $subdir_url);
	}
	return true;
}

function xd_delete ($file) {
	xd_check_traverse ($file);
	$selected = trim ($file, '/');
	if (is_dir (xd_get_root_dir() . $selected)) { xd_rmrf (META_DIR . xd_get_root_dir() . $selected); }
	else {
		$ovmetafile = xd_version_dir_of ($selected);
		if (file_exists ($ovmetafile)) { xd_rmrf ($ovmetafile); }
	}

	if (! xd_rmrf (xd_get_root_dir() . $selected)) {
		$_SESSION['message'] = 'ERROR: "' . xd_basename ($selected) . '" could not be deleted.';
		global $redirect; header ('Location:' . $redirect . $subdir_url);
	} else { xd_rmrf (xd_metafile_of ($selected)); }
	return true;
}

function xd_copy ($oldname, $newname) {
	xd_check_traverse ($newname);
	xd_check_traverse ($oldname);
	$on = trim ($oldname, '/');
	$nn = trim ($newname, '/');
	if (file_exists (xd_get_root_dir() . $nn)) {
		$_SESSION['message'] = 'ERROR: A file with this name exists already in the destination directory!';
		header ('Location:' . $_SERVER['HTTP_REFERER']);
		exit;
	}
	if (! xd_cpr (xd_get_root_dir() . $on, xd_get_root_dir() . $nn)) {
		$_SESSION['message'] = 'ERROR: The copy of "' . xd_basename ($on) . '" could not be created!';
		header ('Location:' . $_SERVER['HTTP_REFERER']);
		exit;
	}
	$dnn = dirname ($nn);
	$bnr = xd_get_root_dir();
	if (! file_exists (META_DIR . $bnr . '/' . $dnn)) { xd_mkdirp (META_DIR . $bnr . '/' . $dnn); }
	if (is_dir (META_DIR . $bnr . '/' . $on)) { xd_cpr (META_DIR . $bnr . '/' . $on, META_DIR . $bnr . '/' . $nn); }
	if (file_exists (xd_metafile_of ($on))) { xd_cpr (xd_metafile_of ($on), xd_metafile_of ($nn)); }
	if (! is_dir (xd_get_root_dir() . $on)) {
		$ovmetafile = xd_version_dir_of ($on);
		if (file_exists ($ovmetafile)) {
			$nvmetafile = xd_version_dir_of ($nn);
			$dnv = (dirname ($nvmetafile));
			if (! file_exists ($dnv)) { xd_mkdirp ($dnv); }
			xd_cpr ($ovmetafile, $nvmetafile);
		}
	}
	return true;
}

function xd_move ($oldname, $target) {
	$newname = xd_basename ($oldname);
	if (strlen (xd_clean ($target))) { $newname = $target . '/' . $newname; }
	xd_check_traverse ($target);
	xd_check_traverse ($oldname);
	if (! xd_copy ($oldname, $newname) || ! xd_delete ($oldname)) { return false; }
	return true;
}

function xd_rename ($oldname, $newname) {
	xd_check_traverse ($newname);
	xd_check_traverse ($oldname);
	$on = trim ($oldname, '/');
	$nn = trim ($newname, '/');
	if (file_exists (xd_get_root_dir() . $nn)) {
		$_SESSION['message'] = 'ERROR: A file with this name exists already!';
		header ('Location:' . $_SERVER['HTTP_REFERER']);
		exit;
	}
	if (! rename (xd_get_root_dir() . $on, xd_get_root_dir() . $nn)) {
		$_SESSION['message'] = 'ERROR: "' . xd_basename ($on) . '" could not be renamed!';
		header ('Location:' . $_SERVER['HTTP_REFERER']);
		exit;
	}
	$bnr = xd_get_root_dir();
	if (is_dir (META_DIR . $bnr . '/' . $on)) { rename (META_DIR . $bnr . '/' . $on, META_DIR . $bnr . '/' . $nn); }
	if (file_exists (xd_metafile_of ($on))) { rename (xd_metafile_of ($on), xd_metafile_of ($nn)); }
	if (! is_dir (xd_get_root_dir() . $on)) {
		$ovmetafile = xd_version_dir_of ($on);
		if (file_exists ($ovmetafile)) {
			$nvmetafile = xd_version_dir_of ($nn);
			$dnv = (dirname ($nvmetafile));
			if (! file_exists ($dnv)) { xd_mkdirp ($dnv); }
			rename ($ovmetafile, $nvmetafile);
		}
	}
	return true;
}

function xd_save_version ($filename, $d=false) {
	$bn = xd_basename ($filename);
	$vdn = xd_version_dir_of ($filename);
	$n = date ('YmdHis');
	$version_file = $n . '-' . $bn;
	if (! file_exists ($vdn)) { xd_mkdirp ($vdn); }
	if (xd_cpr (xd_get_root_dir() . $filename, $vdn . '/' . $version_file)) {
		if ($d) { xd_set_description ($filename, $d, $n); }
		return true;
	}
	return false;
}

function xd_get_filters ($dir='') {
	xd_check_traverse ($dir);
	$fl = shell_exec ('find "' . META_DIR . xd_get_root_dir () . $dir . '" -name "*.php" -exec grep -H \'$_filters\' {} \\; | sed -e "s/.php:.*$//g"');
	$files = explode ("\n", $fl);
	foreach ($files as $f) { if (strlen ($f)) { $_files[] = $f; } }
	foreach ($_files as $mf) {
		$file = str_replace (META_DIR, '', str_replace (xd_get_root_dir(), '', $mf));
		if (file_exists (xd_get_root_dir() . $file)) {
			include ($mf . '.php');
			if (isset ($_filters)) {
				foreach ($_filters as $_f) {
					$filters[$_f][] = $file;
				}
			}
			unset ($_filters);
		}
	}
	return $filters;
}

function xd_show_filters ($filters, $pre = '') {
	ksort ($filters);
	foreach ($filters as $filter => $v) {
		if (is_array ($v)) {
			echo '<li>';
			$on = false;
			foreach ($v as $sv) { if (is_array ($sv)) { $on = true; } }
			if ($on) { echo '<a href="#" onclick="toggle_filter(\'' . $pre . $filter . '\'); toggle (\'sub' . $pre . $filter . '\'); return false;" id="plus' . $pre . $filter . '">[+]</a>&nbsp;'; }
			if (isset ($_SESSION['filters'][$pre . $filter])) { echo '<a href="' . PHPSELF . '?filter=' . $pre . $filter . '">' . $filter . '</a></li>'; }
			else { echo $filter; }
			echo '<ul id="sub' . $pre . $filter . '" style="display: none; list-style-type: none; margin-left: -25px;">';
			xd_show_filters ($v, $pre . $filter . '/');
			echo '</ul>';
		}
	}
}

function xd_file_put_content ($filename, $content) {
	$df = dirname ($filename);
	xd_check_traverse ($filename);
	if (! file_exists ($df)) { xd_mkdirp ($df); }
	if (! file_exists ($filename)) { if (! $fp = fopen ($filename, 'x')) { return false; } }
	if (is_writable ($filename)) {
		if (! $fp = fopen ($filename, 'w')) { return false; }
		if (fwrite ($fp, $content) == FALSE) { return false; }
		fclose ($fp);
		return true;
	}
	return false;
}

function xd_search ($what, $where, $advanced = false, $type = '', $case = '', $descriptions = false) {
	xd_check_traverse ($where);
	$_case = '-iname';
	$_type = '';
	$w = escapeshellcmd ($what);
	$found = $d_all = array ();
	if ($advanced) {
		if ($case == 'on') { $_case = '-name'; }
		if ($type == 'files') { $_type = '-type f'; }
		else if ($type == 'directories') { $_type = '-type d'; }
		if ($descriptions == 'on') {
			exec ('find "' . xd_get_root_dir() . escapeshellcmd ($where) . '/" ' . $_type, $_all, $r);
			foreach ($_all as $one) {
				$co = trim (xd_derootify ($one), '/');
				$mf = xd_metafile_of ($co);
				if (file_exists ($mf)) { include $mf; }
				if ($case != 'on') { $_w = '|' . $w . '|i'; }
				if (preg_match ($_w, strip_tags (xd_unhtmlentities ($_description['text']))) && strlen (trim ($co))) { $d_all[] = xd_get_root_dir() . $co; }
				unset ($_description);
			}
		}
	}
	exec ('find "' . xd_get_root_dir() . escapeshellcmd ($where) . '" ' . $_type . ' ' . $_case . ' "*' . trim ($w, '\*') . '*"', $_found, $r);
	if ($r != 0) {
		$_SESSION['message'] = 'ERROR: The search returned an error!';
		global $redirect; header ('Location:' . $redirect . $where);
		exit;
	}
	foreach ($_found as $_f) { if ($_f != xd_get_root_dir ()) { $found[] = trim ($_f, '/'); } }
	return array_unique (array_merge ($found, $d_all));
}

function xd_change_settings ($what, $post_array) {
	$columns_possible = array ('description', 'ctime', 'mtime', 'atime', 'size');

	global $_users, $_top_content, $_style, $_colors;
	$xd_anonymous = ANONYMOUS;
	$xd_editable = EDITABLE;
	$xd_date_format = DATE_FORMAT;
	$xd_timezone = TIMEZONE;
	$xd_img_width = IMG_WIDTH;
	$xd_show_filesize = SHOW_FILESIZE;
	$xd_title = TITLE;
	$xd_table_columns = TABLE_COLUMNS;
	$xd_filters = FILTERS;
	$xd_search = SEARCH;
	$xd_enable_versioning = VERSIONING;
	$xd_set_server_auth = SERVER_AUTH;
	$xd_show_hidden = SHOW_HIDDEN;
	$xd_top_content = $_top_content;
	$xd_style = $_style;

	foreach ($post_array as $key => $val) { $post_array[$key] = addcslashes ($val,"'"); }

	if ($what == 'users') {
		foreach ($_users as $name => $user) {
			if ($name != $_SESSION['username']) {
				if ($post_array[$name.'_privileges'] == 1) { if (isset ($_users[$name]['home'])) { unset ($_users[$name]['home']); } }
				else {
					$home = xd_clean (trim ($post_array[$name.'_home'], '/'), true) . '/';
					if (! file_exists (ROOT_DIR . $home) || ! is_dir (ROOT_DIR . $home)) {
						$_SESSION['message'] = 'ERROR: the specified home directory "' . $home . '" does not exist or is not a directory!';
						header ('Location:' . $_SERVER['HTTP_REFERER']);
						exit;
					}
					if (strlen (trim ($home, '/'))) { $_users[$name]['home'] = $home; } else { unset ($_users[$name]['home']); }
				}
			}
		}
		if ($post_array['_set_anonymous'] == 'on') {
			$pub = xd_clean (trim ($post_array['_set_anonymous_dir'], '/'), true);
			xd_check_traverse ($pub);
			if (! file_exists (ROOT_DIR . $pub) || ! is_dir (ROOT_DIR . $pub) || ! strlen ($pub)) {
				$_SESSION['message'] = 'ERROR: the specified public directory "' . $pub . '" does not exist or is not a directory!';
				header ('Location:' . $_SERVER['HTTP_REFERER']); exit;
			}
			$xd_anonymous = $pub . '/';
		} else { $xd_anonymous = false; }
		$_new_username = xd_clean ($post_array['_set_new_username']);
		if (strlen ($_new_username)) {
			$_users[$_new_username] = array ('password' => md5 ($post_array['_set_new_username_password1']), 'privileges' => $post_array['_set_new_username_privileges']);
			if ($post_array['_set_new_username_privileges'] != 1) {
				$home = xd_clean (trim ($post_array['_set_new_username_home'], '/'), true) . '/';
				if (! file_exists (ROOT_DIR . $home) || ! is_dir (ROOT_DIR . $home)) {
					$_SESSION['message'] = 'ERROR: the specified home directory "' . $home . '" does not exist or is not a directory!';
					header ('Location:' . $_SERVER['HTTP_REFERER']);
					exit;
				}
				if (strlen (trim ($home, '/'))) { $_users[$_new_username]['home'] = $home; }
			}
		}
	} else if ($what == 'prefs') {
		$xd_editable = $post_array['_set_editable'];
		$xd_date_format = $post_array['_set_date_format'];
		$xd_timezone = $post_array['_set_timezone'];
		$xd_img_width = $post_array['_set_img_width'];
		$xd_show_filesize = $xd_show_hidden = false;
		if ($_REQUEST['_set_show_filesize'] == 'on') { $xd_show_filesize = true; }
		else { $xd_show_filesize = false; }
		if ($_REQUEST['_set_enable_versioning'] == 'on') { $xd_enable_versioning = true; }
		else { $xd_enable_versioning = false; }
		if ($_REQUEST['_set_server_auth'] == 'on') { $xd_set_server_auth = true; }
		else { $xd_set_server_auth = false; }
		if ($_REQUEST['_set_show_hidden'] == 'on') { $xd_show_hidden = true; }
		else { $xd_show_hidden = false; }
		$xd_title = $post_array['_set_title'];
		$xd_table_columns = '';
		foreach ($columns_possible as $c) { if ($_REQUEST['_set_' . $c] == 'on') { $xd_table_columns .= $c . ' '; } }
		$xd_filters = false;
		if ($_REQUEST['_set_filters'] == 'on') { if ($xd_filters != true) { $_SESSION['filters'] = xd_get_filters (); } $xd_filters = true; }
		if ($_REQUEST['_set_search'] == 'on') { $xd_search = true; }
		else { $xd_search = false; }
	} else if ($what == 'layers') { $xd_top_content = $post_array['_set_top_content']; }
	else if ($what == 'style') { $xd_style = $post_array['_set_style']; }
	else if ($what == 'userdel') { unset ($_users[$post_array['todel']]); }
	else if ($what == 'pass') { $_users[$_SESSION['username']]['password'] = md5 ($post_array['_set_new_password1']); }

	$content_parse =
'<?php
$_users = ' . var_export ($_users, true) . ';
define(\'ROOT_DIR\', \'' . ROOT_DIR . '\');
define(\'META_DIR\', \'' . META_DIR . '\');
define(\'ANONYMOUS\', \'' . $xd_anonymous . '\');
define(\'EDITABLE\', \'' . $xd_editable . '\');
define(\'IMG_EXTENSIONS\', \'' . IMG_EXTENSIONS . '\');
define(\'IMG_WIDTH\', \'' . $xd_img_width . '\');
define(\'ICONS_DIR\', \'' . ICONS_DIR . '\');
define(\'SHOW_FILESIZE\', \'' . $xd_show_filesize . '\');
define(\'TABLE_COLUMNS\', \'' . trim ($xd_table_columns) . '\');
define(\'FILTERS\', \'' . $xd_filters . '\');
define(\'SEARCH\', \'' . $xd_search . '\');
define(\'SHOW_HIDDEN\', \'' . $xd_show_hidden . '\');
define(\'VERSIONING\', \'' . $xd_enable_versioning . '\');
define(\'SERVER_AUTH\', \'' . $xd_set_server_auth . '\');
define(\'UNIX_FILEINFO_TYPE\', \'' . UNIX_FILEINFO_TYPE . '\');
define(\'TITLE\', \'' . $xd_title . '\');
define(\'DATE_FORMAT\', \'' . $xd_date_format . '\');
define(\'TIMEZONE\', \'' . $xd_timezone . '\');
define(\'LABEL_EDIT\', \'' . LABEL_EDIT . '\');
define(\'LABEL_DELETE\', \'' . LABEL_DELETE . '\');
define(\'LABEL_DOWNLOAD\', \'' . LABEL_DOWNLOAD . '\');
define(\'LABEL_UP_DIR\', \'' . LABEL_UP_DIR . '\');
$_top_content = \'' . $xd_top_content . '\';
$_colors = ' . var_export ($_colors, true) . ';
?>';
	return $content_parse;
}

function xd_wget_system ($url, $target_dir, $target_name = '') {
	xd_check_traverse ($target_dir);
	if (empty ($target_name)) {
		$url_arr = parse_url ($url);
		$target_name = xd_basename ($url_arr['path']);
	}
	$target = xd_get_root_dir() . $target_dir . '/' . $target_name;
	if (file_exists ($target)) { $target = $target_dir . '/' . 'Transfered_at-' . date ('Y-m-d_H-i-s') . '-' . $target_name; }
	system ('wget -O "' . escapeshellcmd($target) . '" "' . escapeshellcmd(str_replace (' ', '%20', html_entity_decode ($url))) . '"', $r);
	if ($r == 0) { return true; }
	return false;
}

function xd_upload ($target_dir) {
	xd_check_traverse ($target_dir);
	$k = 0;
	foreach ($_FILES['files_to_upload']['name'] as $file) {
		$target_path = $target_dir . '/' . xd_clean ($file);
		if (file_exists (xd_get_root_dir() . $target_path)) { xd_save_version ($target_path, array ('text' => 'Newer version uploaded!')); }
		if (! move_uploaded_file ($_FILES['files_to_upload']['tmp_name'][$k], xd_get_root_dir() . $target_path)) { return false; }
		++$k;
	}
	return true;
}

function xd_download ($_file, $filename=false, $version=false, $force=false, $rate=0) {
	xd_check_traverse ($_file);
	xd_check_privileges (6);
	$file = xd_get_root_dir () . urldecode ($_file);
	if ($version) { $file = urldecode ($_file); }
	if (! is_file ($file)) {
		header ('Status: 404 Not Found');
		die ('404 File not found!');
	}
	if ($filename == false) { $filename = xd_basename ($_file); }

	header ('Cache-Control: private');
	exec ('file -bi "' . $file . '"', $ctype);
	if ($force) { header ('Content-Type: application/force-download'); }
	else { header ('Content-Type: ' . $ctype[0]); }

	if ($version || $force) { header ('Content-Disposition: attachment; filename="' . urldecode ($filename) . '";'); }
	else { header ('Content-Disposition: inline; filename="' . urldecode ($filename) . '";'); }
	header ('Content-Transfer-Encoding: binary');

	$size = filesize ($file); 
 
	header ('Accept-Ranges: bytes');
 	$fp = fopen ($file, 'rb');
 
	if (isset ($_SERVER['HTTP_RANGE'])) {
		$seek_range = substr ($_SERVER['HTTP_RANGE'], 6);
		$range = explode ('-', $seek_range);
 
		if ($range[0] > 0) { $seek_start = intval ($range[0]); }
		if ($range[1] > 0) { $seek_end = intval ($range[1]); }
 
		fseek ($fp, $seek_start);
 
		header ('HTTP/1.1 206 Partial Content');
		header ('Content-Length: ' . ($seek_end - $seek_start + 1));
		header (sprintf ('Content-Range: bytes %d-%d/%d', $seek_start, $seek_end, $size));
	} else { header ('Content-Length: ' . $size); }
 
	$block_size = 1024;
	if ($rate > 0) { $block_size *= $rate; }
 
	set_time_limit (0);
 
	while (! feof ($fp)) {
		print (fread ($fp, $block_size));
		flush ();
		if ($rate > 0) { sleep (1); }
	}
	fclose($fp);
	exit;
}

function xd_check_traverse ($path) {
	if (strstr ($path, '../')) {
		$_SESSION['message'] = 'ERROR: Relative path is supported only for subdirectories!';
		header ('Location:' . $_SERVER['HTTP_REFERER']);
		exit;
	}
	return true;
}

function xd_htmlentities ($var) {
	return str_replace (array ('>', '<', '"', '&'), array ('&gt;', '&lt;', '&quot;', '&amp;'), $var);
}

function xd_unhtmlentities ($var) {
	return str_replace (array ('&gt;', '&lt;', '&quot;', '&amp;'), array ('>', '<', '"', '&'), $var);
}

function xd_clean ($str, $path = false) {
	$bad_chars = array ('"', '\'', '!', '@', '#', '$', '%', '^', '&', '*', '(', ')', '{', '}', '+', '=', '\\', '|', '~', '`', '\xc2', '\x80', '\x98', '\x99', '\x8c', '\x9d');
	if (! $path) { $bad_chars[] = '/'; }
	return str_replace ($bad_chars, '', strip_tags ($str));
}

function xd_explode_tree ($array, $delimiter = '_', $baseval = false) {
	if (!is_array ($array)) return false;
	$splitRE = '/' . preg_quote ($delimiter, '/') . '/';
	$returnArr = array();
	foreach ($array as $key => $val) {
		$parts = preg_split ($splitRE, $key, -1, PREG_SPLIT_NO_EMPTY);
		$leafPart = array_pop ($parts);
		$parentArr = &$returnArr;
		foreach ($parts as $part) {
			if (! isset ($parentArr[$part])) { $parentArr[$part] = array (); }
			elseif (! is_array ($parentArr[$part])) {
				if ($baseval) { $parentArr[$part] = array ('__base_val' => $parentArr[$part]); }
				else { $parentArr[$part] = array (); }
			}
			$parentArr = &$parentArr[$part];
		}
		if (empty ($parentArr[$leafPart])) { $parentArr[$leafPart] = $val; }
		elseif ($baseval && is_array ($parentArr[$leafPart])) {	$parentArr[$leafPart]['__base_val'] = $val; }
	}
	return $returnArr;
}

function xd_rglob ($dir) {
	$files = array ();
	$file_tmp = glob ($dir . '*', GLOB_MARK);

	foreach ($file_tmp as $item) {
		if (! is_dir ($item)) { $files[] = $item; }
		else { $files = array_merge ($files, xd_rglob ($item)); }
	}
	return $files;
}

function xd_zip ($files, $name) {
	xd_check_traverse ($name);
	require 'zipstream.php';
	$pwd = xd_get_root_dir ();

	foreach ($files as $key => $file) {
		$path = $pwd . '/' . $file;
		xd_check_traverse ($path . '/');
		if (is_dir ($path)) {
			unset ($files[$key]);
			foreach (xd_rglob ($path) as $_file) {
				$files[] = trim (xd_derootify ($_file), '/');
			}
		}
	}
	$zip = new ZipStream ($name, array ('comment' => 'Zip-archive created by XODA, using the excellent ZipStream library by Paul Duncan: http://pablotron.org/software/zipstream-php'));
	$file_opt = array ('time' => time(), 'comment' => '');

	foreach ($files as $file) {
		$path = $pwd . '/' . $file;
		$data = file_get_contents ($path);
		$zip->add_file($file, $data, $file_opt);
	}
	$zip->finish();
}

function xd_cmp ($a, $b) {
	global $subdir;
	return xd_file_content_type (xd_get_root_dir() . $subdir . '/' .$a) < xd_file_content_type (xd_get_root_dir() . $subdir . '/' .$b) ? 1 : -1;		// sorting by file type
//	return xd_file_content_type (xd_basename ($a)) < xd_file_content_type (xd_basename ($b)) ? -1 : 1; 							// sorting by file name
}

function xd_mobile () {
	$ua = $_SERVER['HTTP_USER_AGENT'];
	if (stristr ($ua, 'AvantGo') || stristr ($ua,'Mazingo') || stristr ($ua, 'Mobile') || stristr ($ua, 'T68') || stristr ($ua,'Syncalot') || stristr ($ua, 'Blazer')) { return true; }
	return false;
}
?>
