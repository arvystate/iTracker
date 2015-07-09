<?
/*
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// We enter the detail in the database
if (isset($_POST['id']) == true)
{
	define('IN_APP', true);

	include_once('mysql.php');

	$db = new sql_db();
	$db->sql_open('localhost', 'db_user', 'db_password', 'db_name', true, '');

	// Escape everything
	foreach ($_POST as $key => $value)
	{
		if (is_array($_POST[$key]) == false)
		{
			$_POST[$key] = mysql_real_escape_string($value);
		}
	}
	
	$data = 0;
	
	// Enter the details in database
	for ($i = 0; $i < count ($_POST['time']); $i++)
	{		
		$db->sql_insert('markers', 
						'user_id, phone_id, locked, time, lat, lon, acc, spd',
						"'1', '{$_POST['id']}', '{$_POST['lock']}', '{$_POST['time'][$i]}', '{$_POST['lat'][$i]}', '{$_POST['lon'][$i]}', '{$_POST['acc'][$i]}', '{$_POST['spd'][$i]}'");
						
		$data++;
	}

	echo ('Data received (' . $data . ') ' . time());
}
else
{
	?>
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>404 Not Found</title>
</head><body>
<h1>Not Found</h1>
<p>The requested URL <?=$_SERVER['PHP_SELF']?> was not found on this server.</p>
<p>Additionally, a 404 Not Found
error was encountered while trying to use an ErrorDocument to handle the request.</p>
</body></html>

	<?
}
?>
