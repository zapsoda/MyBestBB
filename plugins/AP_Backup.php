<?php

//PunBB Backup v1.0 - Last modified: 24/10/05
//Author: Michael Dorman (PMD - michaeldorman@gmail.com)
//Descripton: Allows administrators to generate a backup and store it in a variety of ways.	

/***********************************************************************

  Copyright (C) 2002-2005  Michael Dorman (michaeldorman@gmail.com)

  This file is part of PunBB.

  PunBB is free software; you can redistribute it and/or modify it
  under the terms of the GNU General Public License as published
  by the Free Software Foundation; either version 2 of the License,
  or (at your option) any later version.

  PunBB is distributed in the hope that it will be useful, but
  WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston,
  MA  02111-1307  USA
*/

require PUN_ROOT.'lang/'.$pun_user['language'].'/backup.php';

// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;

// Tell admin_loader.php that this is indeed a plugin and that it is loaded
define('PUN_PLUGIN_LOADED', 1);

// Increase time limit... incase its a big file or something.
set_time_limit(99999999);

if (isset($_POST['make_backup']))
{
	// Make sure something something was entered
	if (!isset($_POST['method']))
		message($lang_Backup['E_NeverDefinedSaveDB']);

	// Weeee! Let's make the dump!
	define('N', "\r\n"); // Saves a few characters each time...
	
	$dump = $lang_Backup['M_SQLDumpStartedAt'].format_time(time()).N.$lang_Backup['M_GeneratedBy'].N.$lang_Backup['M_DumpingDB'].$db_name.$lang_Backup['M_DBType'].$db_type.N;

	switch($db_type)
	{
		case 'mysql':
		case 'mysqli':
			$query = 'SHOW TABLES';
		break;
		case 'sqlite':
			$query = "SELECT * FROM sqlite_master WHERE type='table'";
		break;
		case 'pgsql':
			$query = 'SELECT tablename FROM pg_tables WHERE tableowner = current_user';
		break;
		default:
			message($lang_Backup['E_DBNotSupported']);
		break;
	}

	$tables = $db->query($query);

	while($table = $db->fetch_row($tables))
	{		
			if ($db_type == 'mysql' || $db_type == 'mysqli')
			{
				$output = '';
				$result = $db->query('SHOW CREATE TABLE `'.$table[0].'`');
				while ($ins = $db->fetch_row($result))
				{
					unset($ins[0]);
					$output .= implode(N,$ins);
				}
				$dump .= N.N.'-- '.N.'-- Table structure for '.$table[0].N.'-- '.N.N;
				$dump .= 'DROP TABLE IF EXISTS `'.$table[0].'`;'.N;
				$dump .= $output;
			} else if ($db_type == 'sqlite')
			{
				$table[0] = $table[1];
				$dump .= N.N.'-- '.N.'-- Table structure for '.$table[0].N.'-- '.N.N;
				$dump .= 'DROP TABLE IF EXISTS '.$table[0].';'.N;
				$dump .= str_replace("\t", '', $table[4]);
			}

		$result = $db->query('SELECT * FROM '.$table[0].' WHERE 1=1');
		$table_rows = $db->num_rows($result);
		if($table_rows > 0)
		{
			$dump .= N.N.'-- '.N.'-- Table data for '.$table[0].N.'-- '.N;
			while($row = $db->fetch_row($result))
			{
				$dump .= N.'INSERT INTO '.$table[0].' VALUES (';
				$cols = count($row);
				$i = 0;
				foreach($row as $data)
				{
					$i++;
					$dump .= '\''.$db->escape($data).'\'' . ($i != $cols ? ', ' : '');
				}
				$dump .= ');';
			}
		  }
		}
	$dump .= N.$lang_Backup['M_DumpEndedAt'].format_time(time());	
	// End of Making the dump
	
	$filename = $db_name.gmdate('_j-n-y').'.sql';

	if ($_POST['method'] == 'download')
	{
		ob_end_clean();
		header('Content-type: text/x-sql');
		header("Content-disposition: attachment; filename=".$filename);
		echo $dump;
		die;
    
	}
	else if ($_POST['method'] == 'filesystem')
	{
		if (!isset($_POST['dir']) || empty($_POST['dir']))
			message($lang_Backup['E_NoDirectorySpecified']);

		if (!$fhandle = fopen($_POST['dir'].'/'.$filename, 'w+'))
			message($lang_Backup['E_CantFindDirectory']);

		if (fwrite($fhandle, $dump) === false)
			message($lang_Backup['E_UnableToWriteToDirectory']);
		
		fclose($fhandle);
		
		generate_admin_menu($plugin);
?>
	<div class="block">
		<h2><span>Backup Successful!</span></h2>
		<div class="box">
			<div class="inbox">
				<p><?php echo $lang_Backup['M_BackupSuccessful']; ?></p>
				<p><a href="javascript: history.go(-1)"><?php echo $lang_Backup['O_GoBack']; ?></a></p>
			</div>
		</div>
	</div>
<?php
	}
	else if ($_POST['method'] == 'ftp')
	{
		if (!isset($_POST['host'], $_POST['user'], $_POST['password']))
			message('You forgot to define either a host, username or password.');
		
		if (isset($_POST['ssl']) && $_POST['ssl'] == 1 && function_exists('ftp_ssl_connect'))
			$connect_type = 'ftp_ssl_connect';
		else
			$connect_type = 'ftp_connect';

		if (!$conn = $connect_type($_POST['host']))
			message($lang_Backup['E_CouldNotConnect']);
		
		if (!ftp_login($conn, $_POST['user'], $_POST['password']))
			message($lang_Backup['E_UnableToLogin']);
		
		//Gotta create a temporary file to upload this...
		$tmp = tempnam('','');
		$handle = fopen($tmp, 'w+');
		fwrite($handle, $dump);
		fclose($handle);

		if(!ftp_put($conn, $filename, $tmp, FTP_ASCII))
			message($lang_Backup['E_UnableToUpload']);
		
		//Bit of cleanup
		unlink($tmp);
		ftp_close($conn);
		
		generate_admin_menu($plugin);
?>
	<div class="block">
		<h2><span><?php echo $lang_Backup['M_UploadSuccessful']; ?></span></h2>
		<div class="box">
			<div class="inbox">
				<p><?php echo $lang_Backup['M_BackupUploaded'].pun_htmlspecialchars($_POST['host']) ?>".</p>
				<p><a href="javascript: history.go(-1)"><?php echo $lang_Backup['O_GoBack']; ?></a></p>
			</div>
		</div>
	</div>
<?php
	}
	else
	{
		message($lang_Backup['E_InvalidMethod']);
	}
}
else
{
	generate_admin_menu($plugin);

?>
		<div id="exampleplugin" class="blockform">
		<h2><span><?php echo $lang_Backup['H_Backup']; ?></span></h2>
		<div class="box">
			<div class="inbox">
				<p><?php echo $lang_Backup['Desc_1']; ?></p>
				<p><?php echo $lang_Backup['Desc_2']; ?></p>
				<p><?php echo $lang_Backup['Desc_3']; ?></p>
			</div>
		</div>

		<h2 class="block2"><span><?php echo $lang_Backup['H_BackupDB']; ?></span></h2>
		<div class="box">
			<form id="backup" method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_Backup['H_BackupMethod']; ?></legend>
						<div class="infldset">
						<table class="aligntop" cellspacing="0">
							<tr>
								<th scope="row"><?php echo $lang_Backup['O_Download']; ?></th>
								<td>
									<span><input type="radio" name="method" value="download" checked="checked" />&nbsp;&nbsp;<?php echo $lang_Backup['Desc_Download']; ?></span>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php echo $lang_Backup['O_FileSystem']; ?></th>
								<td>
									<span><input type="radio" name="method" value="filesystem" />&nbsp;&nbsp;<?php echo $lang_Backup['Desc_FileSystem']; ?></span>
									<?php echo $lang_Backup['O_Directory']; ?><input type="text" name="dir" size="25" value="<?php echo getcwd(); ?>" /> <span style="color: red; display:inline;"><?php echo $lang_Backup['E_WriteAccessNeeded']; ?></span>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php echo $lang_Backup['O_FTP']; ?></th>
								<td>
									<span><input type="radio" name="method" value="ftp" />&nbsp;&nbsp;<?php echo $lang_Backup['M_SaveBackup']; ?><acronym title="File Transfer Protocol"><?php echo $lang_Backup['O_FTP']; ?></acronym><?php echo $lang_Backup['M_Server']; ?></span>
									<span><?php echo $lang_Backup['Input_Host']; ?><input type="text" name="host" size="25" style="margin-left: 3em;" /></span>
									<span><?php echo $lang_Backup['Input_User']; ?><input type="text" name="user" size="25" /></span>
									<span><?php echo $lang_Backup['Input_Pass']; ?><input type="text" name="password" size="25" style="margin-left: .7em;" /></span>
									<?php echo function_exists('ftp_ssl_connect') ? '<span><acronym title="Secure Socket Layer">SSL</acronym>? <input type="checkbox" name="ssl" value="1" style="margin-left: 3.2em;"/> <strong>Yes</strong></span>' : '' ?>
								</td>
							</tr>
						</table>
						<p class="topspace"><?php echo $lang_Backup['M_CreatingBackup']; ?></p>
						<div class="fsetsubmit"><input type="submit" name="make_backup" value="Create backup" tabindex="1" /></div>
						</div>
					</fieldset>
				</div>
			</form>
		</div>
	</div>
<?php

}