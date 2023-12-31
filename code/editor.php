<!--
/*
 * Editor script for Custom Error Pages Module for Sentora.org
 * Version : 3.0.4
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
//include('controller.ext.php');

session_start();
if (isset($_SESSION['zpuid']))
{
	$userid = $_SESSION['zpuid'];
	$currentuser = ctrl_users::GetUserDetail($userid);
	$hostdatadir = ctrl_options::GetOption('hosted_dir') . $currentuser['username'];
	$userName = $currentuser['username'];
}

// configuration
$selectedDomain = $_GET['domain'];
$url = "/modules/error_pages/code/editor.php";
$file = $hostdatadir . "/public_html" . $selectedDomain . "/_errorpages/";
$selectedDomainName = str_replace('_', '.', $selectedDomain);
$selectedDomainName = str_replace('/', '', $selectedDomainName);

?>
<script src="ckeditor/ckeditor.js"></script>

<link rel="stylesheet" href="ckeditor/ckeditor.css" rel="stylesheet">
<!-- change to use proper module coding -->
<link href="../../../etc/styles/Sentora_Default/global-css/bootstrap.min.css" rel="stylesheet" type="text/css">

<?php
// Restore default error pages
function restoreDefaultPages($dst)
{
	$errorpages = ctrl_options::GetSystemOption('static_dir') . "/errorpages/";
	$dir = opendir($errorpages); 
	@mkdir($dst); 
	while(false !== ( $file = readdir($dir)) )
	{ 
		if (( $file != '.' ) && ( $file != '..' ))
		{ 
			if (is_dir($errorpages . '/' . $file))
			{ 
				restoreDefaultPages($errorpages . '/' . $file,$dst . '/' . $file); 
			} 
			else
			{ 
				copy($errorpages . '/' . $file,$dst . '/' . $file); 
			} 
		} 
	} 
	closedir($dir); 
}

// update page
if (isset($_POST['text']))
{
	$text = $_POST['text'];
	$page = $_POST['page'];
	$file = $file.$page;
    // save the text contents
    file_put_contents($file, $text);
	//preview updated page
	echo "<div align=\"center\">";
	echo '<div class="alert alert-success" role="alert">
			<h4 class="alert-heading">' . $page . ' Page Saved!</h4>
			<form name="goback" action="" method="post">
				<input class="btn btn-success ui-corner-all" type="submit" name="goback" value="Edit Another Page" />
				<input type="hidden" id="domain" name="domain" value="' . $selectedDomain . '">
			</form>
		  </div>';
	echo "</div>";
	echo "<p align=\"center\"><b>Page Quick View:</b></p>";
	echo $text;
    exit();
}
else
{
	// open page
	if (isset($_POST['page']))
	{
		$page = $_POST['page'];
		$file = $file.$page;
		// read the textfile
		$text = file_get_contents($file);
		/* Editor */
		echo '
		<div align="center">
		<form name="editor" action="" method="post">
			<input class="btn btn-success ui-corner-all" type="submit" name="editor_save" value="Save" />
			<input class="btn btn-warning ui-corner-all" type="button" name="editor_cancel" value="Cancel"  onclick="location.href=\'?domain=' . $selectedDomain . '\';" /><br />
			<strong>File Location:</strong> /public_html<?php echo $selectedDomain; ?>/_errorpages/' . $_POST['page'] . '<br>
			<textarea class="ckeditor" id="error_pages" name="text">' . htmlspecialchars($text) . '</textarea><br />
			<input type="hidden" name="page" value="' . $page . '">
			<input type="hidden" id="domain" name="domain" value="' . $selectedDomain . '">
		</form>
		</div>';
	}
	if (!isset($_POST['page']))
	{
	// Restore default pages
		if (isset($_POST['restore']) && ($_POST['restore'] == "restore"))
		{
			$dst = $hostdatadir . "/public_html" . $selectedDomain . "/_errorpages/";
			
			echo "<div align=\"center\">";
			echo '<div class="alert alert-warning" role="alert">
				<h4 class="alert-heading">Are you sure you want to restore the defaut error pages for: <strong>' . $selectedDomainName . '</h4>';
			echo '<form name="confirm" action="" method="post">
					<input class="btn btn-success ui-corner-all" type="submit" name="confirm" value="Yes" />
					<input class="btn btn-warning ui-corner-all" type="submit" name="confirm" value="No" />
				</form>
			  ';
			echo "</div></div>";
			exit();
		}
		// retore default pages
		if (isset($_POST['confirm']) && ($_POST['confirm'] == "Yes"))
		{
			$dst = $hostdatadir . "/public_html" . $selectedDomain . "/_errorpages/";
			restoreDefaultPages($dst);
			echo "<div align=\"center\">";
			echo '<div class="alert alert-success" role="alert">
				<h4 class="alert-heading">Default error pages restored for: <strong>' . $selectedDomainName . '</h4>
				<form name="goback" action="" method="post">
					<input class="btn btn-success ui-corner-all" type="submit" name="goback" value="Continue" />
					<input type="hidden" id="domain" name="domain" value="' . $selectedDomain . '">
				</form>
			  </div>';
			echo "</div>";
			exit();
		}
		else if (isset($_POST['confirm']) && ($_POST['confirm'] == "No"))
		{
			echo "<div align=\"center\">";
			echo '<div class="alert alert-success" role="alert">
				<h4 class="alert-heading">No changes have been made.</h4>
				<form name="goback" action="" method="post">
					<input class="btn btn-success ui-corner-all" type="submit" name="goback" value="Continue" />
					<input type="hidden" id="domain" name="domain" value="' . $selectedDomain . '">
				</form>
			  </div>';
			echo "</div>";
			exit();
		}

		// editor page selection
		// run checks first to see if the files even exist
		if ((!isset($_POST['restore'])) && (!isset($_POST['confirm'])) && (is_dir($file)) && (count(glob($file . "/*"))))
		{
			echo "<div align=\"center\">";
			echo "<h4>Select a page to edit for: " . $selectedDomainName . "</h4>";
			echo "<form name=\"pageSelect\" action=\"\" method=\"post\">
					<ul style=\"list-style-type:none;\" class=\"errorpagelist\" id=\"error1\">";
			echo "<div class=\"zgrid_wrapper panel\">";
			
			$path = $hostdatadir . "/public_html" . $selectedDomain . "/_errorpages/";
			$images = scandir($path);
			$epages = scandir($path);
			$ignore = Array(".", "..", ".htaccess", "thumbs.db", "index.html");
			foreach($epages as $curepage)
			{
				if (!in_array($curepage, $ignore))
				{
					$selectedDomainName = str_replace('_', '.', $selectedDomain);
					echo "
					<li>
					<input type=\"radio\" name=\"page\" value=\"" . $curepage . "\" onclick=\"this.form.submit();\"\>&nbsp;" . $curepage . "
					<a target=\"_blank\" href=\"http://" . $selectedDomainName . "/_errorpages/" . $curepage . "\">(Click to preview)</a>
					</li>";
				};
			}
			echo "</div>";
			echo "<li><button class=\"btn btn-danger btn-sm ui-corner-all\" type=\"submit\" name=\"restore\" value=\"restore\">Restore the Default Pages</button></li>";
			echo "</ul>
				<input type=\"hidden\" id=\"domain\" name=\"domain\" value=\"" . $selectedDomain . "\">
				</form></div>";
		// end
		}
		else
		{
		// Restore default pages if not exist
			if (isset($_POST['create']) && ($_POST['create'] == "Yes"))
			{
				$dst = $hostdatadir . "/public_html" . $selectedDomain . "/_errorpages/";
				restoreDefaultPages($dst);
				echo "<div align=\"center\">";
				echo '<div class="alert alert-success" role="alert">';
				echo "<h4>Default error pages restored for " . $selectedDomainName . "</h4>";
				echo '<form name="goback" action="" method="post">
				<input class="btn btn-success ui-corner-all" type="submit" name="goback" value="Continue" />
				<input type="hidden" id="domain" name="domain" value="' . $selectedDomain . '">
			</form>';
				echo "</div></div>";
			}
			else
			{
				if (isset($_POST['create']) && ($_POST['create'] == "no"))
				{
					echo "<div align=\"center\">";
					echo '<div class="alert alert-warning" role="alert">';
					echo "<h3>Nothing has been changed...Select a different domain by clicking the button above.</h3>";
					echo "</div></div>";
					exit();
				}
				else
				{
					/* Create default pages if they do not exist */
					echo "<div align=\"center\">";
					echo '<div class="alert alert-warning" role="alert">
						<h4 class="alert-heading">No error pages exist for <strong>' . $selectedDomainName . '</strong></h4>';
					echo "<b>Select a different domain by clicking the button above.<br />Or restore the defaults now?</b>";
					echo '<form name="defaults" action="" method="post">
							<input class="btn btn-success ui-corner-all" type="submit" name="create" value="Yes" />
						</form>
					  ';
					echo "</div></div>";
				}
			}
		}
	}
}
?>