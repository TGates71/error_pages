<?php
/*
 * Controller script for Custom Error Pages Module for Sentora.org
 * Version : 3.0.2
 * Author :  TGates
 * Email :  tgates@mach-hosting.com
 */

// Normal functions
// Restore default error pages
	function restoreDefaultPages($dst) {
		$errorpages = ctrl_options::GetSystemOption('static_dir') . "/errorpages/";
		$dir = opendir($errorpages); 
		@mkdir($dst); 
		while(false !== ( $file = readdir($dir)) ) { 
			if (( $file != '.' ) && ( $file != '..' )) { 
				if ( is_dir($errorpages . '/' . $file) ) { 
					restoreDefaultPages($errorpages . '/' . $file,$dst . '/' . $file); 
				} 
				else { 
					copy($errorpages . '/' . $file,$dst . '/' . $file); 
				} 
			} 
		} 
		closedir($dir); 
	}

// Function to retrieve remote XML for update check
	function check_remote_xml($xmlurl,$destfile){
		$feed = simplexml_load_file($xmlurl);
		if ($feed)
		{
			// $feed is valid, save it
			$feed->asXML($destfile);
		} elseif (file_exists($destfile)) {
			// $feed is not valid, grab the last backup
			$feed = simplexml_load_file($destfile);
		} else {
			die('Unable to retrieve XML file');
		}
	}

// Class controller & static functions
class module_controller {
	
// Module update check functions
    static function getModuleVersion() {
        global $zdbh, $controller, $zlo;
        $module_path="./modules/" . $controller->GetControllerRequest('URL', 'module');
        
        // Get Update URL and Version From module.xml
        $mod_xml = "./modules/" . $controller->GetControllerRequest('URL', 'module') . "/module.xml";
        $mod_config = new xml_reader(fs_filehandler::ReadFileContents($mod_xml));
        $mod_config->Parse();
        $module_version = $mod_config->document->version[0]->tagData;
		echo " ".$module_version."";
    }
	
    static function getCheckUpdate() {
        global $zdbh, $controller, $zlo;
        $module_path="./modules/" . $controller->GetControllerRequest('URL', 'module');
        
        // Get Update URL and Version From module.xml
        $mod_xml = "./modules/" . $controller->GetControllerRequest('URL', 'module') . "/module.xml";
        $mod_config = new xml_reader(fs_filehandler::ReadFileContents($mod_xml));
        $mod_config->Parse();
        $module_updateurl = $mod_config->document->updateurl[0]->tagData;
        $module_version = $mod_config->document->version[0]->tagData;

        // Download XML in Update URL and get Download URL and Version
        $myfile = check_remote_xml($module_updateurl, $module_path."/" . $controller->GetControllerRequest('URL', 'module') . ".xml");
        $update_config = new xml_reader(fs_filehandler::ReadFileContents($module_path."/" . $controller->GetControllerRequest('URL', 'module') . ".xml"));
        $update_config->Parse();
        $update_url = $update_config->document->downloadurl[0]->tagData;
        $update_version = $update_config->document->latestversion[0]->tagData;

        if($update_version > $module_version)
            return true;
        return false;
    }

// default module static functions
    static function getModuleDesc() {
        $message = ui_language::translate("An error page informs a visitor when there is a problem accessing your site. Each type of problem has its own code. For example, a visitor who enters a non-existent URL will see a 404 error, while an unauthorized user trying to access a restricted area of your site will see a 403 error.<br><br>Basic error pages are automatically provided by the web server (Apache). However, if you prefer, you can create a custom error page instead of using the defaults. NOTE: Error pages are added automatically if they are found in the _errorpages directory and if they are a valid error code, and saved in the proper format: <error_number>.html (404.html)");
        return $message;
    }

	static function getModuleName() {
		$module_name = ui_module::GetModuleName();
        return $module_name;
    }

	static function getModuleIcon() {
		global $controller;
		$module_icon = "/modules/" . $controller->GetControllerRequest('URL', 'module') . "/assets/icon.png";
        return $module_icon;
    }

    static function getCSFR_Tag() {
        return runtime_csfr::Token();
    }

// module specific static functions

    /* Load CSS and JS files */
    static function getInit() {
        global $controller;
        $line = '<link rel="stylesheet" type="text/css" href="modules/' . $controller->GetControllerRequest('URL', 'module') . '/assets/css-pop.css">';
        $line .= '<script type="text/javascript" src="modules/' . $controller->GetControllerRequest('URL', 'module') . '/assets/css-pop.js"></script>'; 
        return $line;
    }
	
    static function getDomainSelected() {
        if (isset($_POST['domain'])) {
			return true;
        } else {
        	return false;
        }
    }

    static function getRestorePages() {
        if (isset($_POST['restore'])) {
			return true;
        } else {
        	return false;
        }
    }

    static function getEditPage() {
        if (isset($_POST['pageeditor'])) {
			return true;
        } else {
        	return false;
        }
    }

    static function getDomVar() {
        if (isset($_POST['domain'])) {
			$domVar = $_POST['domain'];
			return $domVar;
        } else {
        	return false;
        }
    }

    static function ListDomains($uid = 0) {
        global $zdbh;
        if ($uid == 0) {
            $sql = "SELECT * FROM x_vhosts WHERE vh_deleted_ts IS NULL ORDER BY vh_name_vc ASC";
            $numrows = $zdbh->prepare($sql);
        } else {
            $sql = "SELECT * FROM x_vhosts WHERE vh_acc_fk=:uid AND vh_deleted_ts IS NULL ORDER BY vh_name_vc ASC";
            $numrows = $zdbh->prepare($sql);
            $numrows->bindParam(':uid', $uid);
        }
        //$numrows = $zdbh->query($sql);
        $numrows->execute();
        if ($numrows->fetchColumn() <> 0) {
            if ($uid == 0) {
                $sql = $zdbh->prepare($sql);
            }else{
                $sql = $zdbh->prepare($sql);
                $sql->bindParam(':uid', $uid);
            }
            $res = array();
            $sql->execute();
            while ($rowdomains = $sql->fetch()) {
                array_push($res, array(
                    'uid' => $rowdomains['vh_acc_fk'],
                    'name' => $rowdomains['vh_name_vc'],
                    'directory' => $rowdomains['vh_directory_vc'],
                    'active' => $rowdomains['vh_active_in'],
                    'id' => $rowdomains['vh_id_pk'],
                ));
            }
            return $res;
        } else {
            return false;
        }
    }

    static function ListDomainDirs($uid) {
        global $controller;
        $currentuser = ctrl_users::GetUserDetail($uid);
        $res = array();
        $handle = @opendir(ctrl_options::GetSystemOption('hosted_dir') . $currentuser['username'] . "/public_html");
        $chkdir = ctrl_options::GetSystemOption('hosted_dir') . $currentuser['username'] . "/public_html/";
        if (!$handle) {
            # Log an error as the folder cannot be opened...
        } else {
            while ($file = @readdir($handle)) {
                if ($file != "." && $file != ".." && $file != "_errorpages") {
                    if (is_dir($chkdir . $file)) {
                        array_push($res, array('domains' => $file));
                    }
                }
            }
            closedir($handle);
        }
        return $res;
    }
		
    static function getDomainList() {
        $currentuser = ctrl_users::GetUserDetail();
        $res = array();
        $domains = self::ListDomains($currentuser['userid']);
        if (!fs_director::CheckForEmptyValue($domains)) {
            foreach ($domains as $row) {
                $status = self::getDomainStatusHTML($row['active'], $row['id']);
                $res[] = array('name' => $row['name'],
                               'directory' => $row['directory'],
                               'active' => $row['active'],
                               'status' => $status,
                               'id' => $row['id']);
            }
            return $res;
        } else {
            return false;
        }
    }

    static function getDomainStatusHTML($int, $id) {
        global $controller;
        if ($int == 1) {
            return '<td><font color="green">' . ui_language::translate('Live') . '</font></td>'
                 . '<td></td>';
        } else {
            return '<td><font color="orange">' . ui_language::translate('Pending') . '</font></td>'
                 . '<td><a href="#" class="help_small" id="help_small_' . $id . '_a"'
                 . 'title="' . ui_language::translate('Your domain will become active at the next scheduled update.  This can take up to one hour.') . '">'
                 . '<img src="/modules/' . $controller->GetControllerRequest('URL', 'module') . '/assets/help_small.png" border="0" /></a>';
        }
    }

    static function getCurrentDomain() {
        global $controller;
        $domain = $controller->GetControllerRequest('URL', 'domain');
        return ($domain) ? $domain : '';
    }
	
    static function getResult()
    {
        if (!fs_director::CheckForEmptyValue(self::$blank)) {
            return ui_sysmessage::shout(ui_language::translate("Your Domain can not be empty. Please enter a valid Domain Name and try again."), "zannounceerror");
        }
        if (!fs_director::CheckForEmptyValue(self::$badname)) {
            return ui_sysmessage::shout(ui_language::translate("Your Domain name is not valid. Please enter a valid Domain Name: i.e. 'domain.com'"), "zannounceerror");
        }
        if (!fs_director::CheckForEmptyValue(self::$alreadyexists)) {
            return ui_sysmessage::shout(ui_language::translate("The domain already appears to exist on this server."), "zannounceerror");
        }
        if (!fs_director::CheckForEmptyValue(self::$error)) {
            return ui_sysmessage::shout(ui_language::translate("Please remove 'www'. The 'www' will automatically work with all Domains / Subdomains."), "zannounceerror");
        }
        if (!fs_director::CheckForEmptyValue(self::$writeerror)) {
            return ui_sysmessage::shout(ui_language::translate("There was a problem writting to the virtual host container file. Please contact your administrator and report this error. Your domain will not function until this error is corrected."), "zannounceerror");
        }
        if (!fs_director::CheckForEmptyValue(self::$ok)) {
            return ui_sysmessage::shout(ui_language::translate("Changes to your domain web hosting has been saved successfully."), "zannounceok");
        }
        return;
    }

// Module translation static functions - Not implemented yet	
    static function getRestart() {
        $message = ui_language::translate("Select a different domain/Cancel");
        return $message;
    }
    static function getCopyright() {
        $message = '<font face="ariel" size="2">'.ui_module::GetModuleName().' v3.0.2 &copy; 2013-'.date("Y").' by <a target="_blank" href="http://forums.sentora.org/member.php?action=profile&uid=2">TGates</a> for <a target="_blank" href="http://sentora.org">Sentora Control Panel</a>&nbsp;&#8212;&nbsp;Help support future development of this module and donate today!</font>
<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="DW8QTHWW4FMBY">
<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" width="70" height="21" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
</form>';
        return $message;
    }

}
?>