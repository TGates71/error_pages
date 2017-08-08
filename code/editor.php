<!--
/*
 * Editor script for Custom Error Pages Module for Sentora.org
 * Version : 3.1.2
 * Author :  TGates
 * Email :  tgates@mach-hosting.com
 */
-->
<?php
include('../../../cnf/db.php');
include('../../../dryden/db/driver.class.php');
include('../../../dryden/debug/logger.class.php');
include('../../../dryden/runtime/dataobject.class.php');
include('../../../dryden/ctrl/options.class.php');
include('../../../dryden/ctrl/auth.class.php');
include('../../../dryden/ctrl/users.class.php');
include('../../../inc/dbc.inc.php');
include('controller.ext.php');
// uses default bootstrap

session_start();
if (isset($_SESSION['zpuid']))
{
	$userid = $_SESSION['zpuid'];
	$currentuser = ctrl_users::GetUserDetail($userid);
	$userName = $currentuser['username'];
	$hostdatadir = ctrl_options::GetOption('hosted_dir')."".$userName;
}

// configuration
$domVar = $_GET['domain'];
// separate domain from path
$domVars = explode("|", $domVar);
$domainName = $domVars[0];
$domainPath = $domVars[1];
$url = "/modules/error_pages/code/editor.php";
$file = $hostdatadir."/public_html/".$domainPath."/_errorpages/";
?>
<script src="ckeditor/ckeditor.js"></script>
<!-- restore default pages confirm box -->
<script type="text/javascript">
	function confSubmit(form) {
		if (confirm("Are you sure you want to Restore the Default Pages?")) {
		form.submit();
		} else {
		alert("Canceled. No changes have been made.");
		location.reload();
		}
	}
</script>
<script>
function reloadPage()
  {
  location.reload();
  }
</script>

<link rel="stylesheet" href="ckeditor/ckeditor.css">
<link href="../../../../etc/styles/Sentora_Default/global-css/bootstrap.min.css" rel="stylesheet">

<?php
// check if form has been submitted
if (isset($_POST['text']))
{
	$text = $_POST['text'];
	$page = $_POST['page'];
	$file = $file.$page;
    // save the text contents
    file_put_contents($file, $text);
	//preview saved page
	echo "<div align=\"center\">";
	echo "<button class=\"btn btn-success\" name=\"goback\" onclick=\"history.go(-2);\">Go Back</button><br />";
	echo "Saved. Previewing: ".$page."";
	echo "</div><br>";
	echo $text;
    exit();
} else {

// open page
if (isset($_POST['page']))
{
$page = $_POST['page'];
$file = $file.$page;
// read the textfile
$text = file_get_contents($file);
?>
<!-- Editor -->
<div align="center">
<form name="editor" action="" method="post">
    <input class="btn btn-success" type="submit" value="Save" />
    <input class="btn btn-danger" type="button" name="goback" value="Go Back" onclick="history.go(-1);" /><br />
    <strong>File Location:</strong> /public_html/<?php echo $domainPath; ?>/_errorpages/<?php echo $_POST['page']; ?><br>
    <textarea class="ckeditor" id="error_pages" name="text"><?php echo htmlspecialchars($text); ?></textarea><br />
    <input type="hidden" name="page" value="<?php echo $page; ?>"> 
</form>
</div>
<?php
}
if (!isset($_POST['page']))
	{
	// Restore default pages
		if (isset($_POST['restore']) && ($_POST['restore'] == "restore")) {
			$dst = $hostdatadir."/public_html/".$domainPath."/_errorpages/";
			restoreDefaultPages($dst);
			echo "<div align=\"center\">";
			echo "<h3>Default error pages restored for <strong>".$domainName."</h3>";
			echo "<input  class=\"btn btn-success\" type=\"button\" name=\"goback\" value=\"Go Back\" onclick=\"history.go(-1);\" />";
			echo "</div>";
			exit();
		}
			
// run checks to see if the files even exist
if((is_dir($file)) && (count(glob($file."/*"))))
		{
// start
	echo "<div align=\"center\">";
	echo "<h2>Selected Domain: ".$domainName."</h2>";
	echo "<strong>Select a page to edit:</strong>
        <form name=\"pageSelect\" action=\"\" method=\"post\">
            <ul style=\"list-style-type:none;\" class=\"errorpagelist\" id=\"error1\">";
	echo "<li><input type=\"radio\" name=\"restore\" value=\"restore\" onClick=\"confSubmit(this.form);\">Restore the Default Pages</li>";
		$path = $hostdatadir."/public_html/".$domainPath."/_errorpages/";
		$images = scandir($path);
		$epages = scandir($path);
		$ignore = Array(".", "..", ".htaccess", "thumbs.db", "index.html");
		foreach($epages as $curepage){if(!in_array($curepage, $ignore)) {
	//echo "<li><button onclick=\"DelPage('$curepage')\">Delete</button><input type=\"radio\" name=\"page\" value=\"".$curepage."\">".$curepage."</li>";
	echo "
	<li><input type=\"radio\" name=\"page\" value=\"".$curepage."\"
	 onclick=\"this.form.submit();\">".$curepage." 
	<a target=\"_blank\" href=\"http://".$domainName."/_errorpages/".$curepage."\">(Click to preview)</a></li>";
	};
		}
	echo "</ul>
        </form></div>";
// end
		} else {
// Restore default pages if not exist
	if (isset($_POST['create']) && ($_POST['create'] == "yes")) {
		$dst = $hostdatadir."/public_html/".$domainPath."/_errorpages/";
		restoreDefaultPages($dst);
		echo "<div align=\"center\">";
		echo "<h3>Default error pages restored for ".$domainName."</h3>";
		echo "<input class=\"btn btn-success\" type=\"button\" name=\"goback\" value=\"Go Back\" onclick=\"history.go(-1);\" />";
		echo "</div>";
	} else {
		if (isset($_POST['create']) && ($_POST['create'] == "no")) {
			echo "<div align=\"center\">";
			echo "<h3>Nothing has been changed...Select another domain by clicking the button above.</h3>";
			echo "</div>";
			exit();
	}
?>
<div align="center">
	<h3>
    <form name="defaults" action="" method="POST">
    No error pages exist for <strong><?php echo $domainName; ?></strong>. Restore the defaults now?
    <br />
    <input type="radio" name="create" value="yes" checked="checked">Yes
    <input type="radio" name="create" value="no">No
    <br />
    <input class="btn btn-success" type="submit" value="Continue" />
    </form>
    </h3>
</div>
<?php
			}
		}
	}
}
?>