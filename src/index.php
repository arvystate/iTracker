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

define('IN_APP', true);

include_once('mysql.php');

$db = new sql_db();
$db->sql_open('localhost', 'db_user', 'db_pass', 'db_name', true, 'db_table_prefix');

if ($_GET['reset'] == 'true')
{
	$db->sql_truncate('markers');
}

if ($_GET['fullscreen'] == 'true')
{
	$canvas = 'map_canvas_full';
}
else
{
	$canvas = 'map_canvas';
}

if ($_GET['all'] == 'true')
{
	$markers = $db->sql_select('markers', '1 ORDER BY TIME DESC');
}
else
{
	$markers = $db->sql_select('markers', '1 ORDER BY time DESC LIMIT 1');
}

if (count ($markers) == 0)
{
	echo ('No data availible to display.');
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
	<meta http-equiv="refresh" content="150">
    <title>iTracker v1.2</title>
	
	<link href="style.css" rel="stylesheet" type="text/css" />
	
    <script src="http://maps.google.com/maps?file=api&amp;v=2&amp;sensor=true&amp;key=<GOOGLE_MAPS_KEY>" type="text/javascript"></script>
	<script src="popupmarker.js" type="text/javascript"></script>
	<script src="img.js" type="text/javascript"></script>
	<script type="text/javascript">

    function initialize()
	{
    	if (GBrowserIsCompatible())
		{
	        var map = new GMap2(document.getElementById("<?=$canvas?>"));
			map.addControl(new GLargeMapControl());
			
			<?
			// If full screen we display type controls
			if ($_GET['fullscreen'] == 'true')
			{
				echo ('map.addControl(new GMapTypeControl());');
			}
			
			// Display correct map center
			echo ('map.setCenter(new GLatLng(' . $markers[0]->lat . ', ' . $markers[0]->lon . '), 10)');
			?>
			
			var opts;
			var marker;
			
			<?
			// Create all the markers loaded
			
			for ($i = 0; $i < count ($markers); $i++)
			{
			?>
	        
	        opts = { text : "Time: <?=date("j.n.Y H:i:s", $markers[$i]->time + 25200)?><br /><? if ($markers[$i]->spd != -1) { echo ('Speed: ' . $markers[$i]->spd . ' km/h<br />'); } ?>Accuracy: <?=$markers[$i]->acc?> m"};
	        
	        marker = new PopupMarker(new GLatLng(<?=($markers[$i]->lat)?>, <?=$markers[$i]->lon?>), opts);
	        map.addOverlay(marker);
			
			<?
			}
			?>
		}
    }

    </script>
  </head>
<body onload="initialize()" onunload="GUnload()">
<?
if ($_GET['fullscreen'] == 'true')
{
	if ($_GET['all'] == 'true')
	{
		echo ('<div id="back"><a href="index.php?all=true">Back</a></div>');
	}
	else
	{
		echo ('<div id="back"><a href="index.php">Back</a></div>');
	}
	echo ('<div id="map_canvas_full"></div>');
}
else
{
	echo ('<div id="container">');
	
	echo ('<div id="text_map"><img src="img/map.png" alt="Map" title="Map" /></div>');
	echo ('<div id="map_canvas"></div>');
	
	if ($_GET['all'] == 'true')
	{
		echo ('<div id="text_fullscreen"><a href="?fullscreen=true&all=true"><img src="img/fullscreen_off.png" id="fullscreen" onmouseover="imgSwap(\'fullscreen\')" onmouseout="imgSwap(\'fullscreen\')" alt="Fullscreen" title="Fullscreen" /></a></div>');
		echo ('<div id="text_allmarkers_accept"><a href="index.php"><img src="img/allmarkers_off.png" id="markers" onmouseover="imgSwap(\'markers\')" onmouseout="imgSwap(\'markers\')" alt="Whole path" title="Whole path" /></a> <img src="img/icon_accept.png" width="20px" alt="Enabled" title="Enabled" /></div>');
	}
	else
	{
		echo ('<div id="text_fullscreen"><a href="?fullscreen=true"><img src="img/fullscreen_off.png" id="fullscreen" onmouseover="imgSwap(\'fullscreen\')" onmouseout="imgSwap(\'fullscreen\')" alt="Fullscreen" title="Fullscreen" /></a></div>');
		echo ('<div id="text_allmarkers"><a href="?all=true"><img src="img/allmarkers_off.png" id="markers" onmouseover="imgSwap(\'markers\')" onmouseout="imgSwap(\'markers\')" alt="All markers" title="Whole path" /></a></div>');
	}
	
	echo ('<div id="footer">iTracker v1.2.0, Released under <a href="http://www.gnu.org/copyleft/gpl.html">GPL</a> license, <a href="http://www.arvystate.net">ArvYStaTe.net</a>, November 2009</div>');
	
	echo ('</div>');
}
?>
</body>
</html>