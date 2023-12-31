<?php
/*
 * Controller script for Custom Error Pages Module for Sentora.org
 * Version : 3.0.4
 * Author :  TGates
 * Email :  tgates@mach-hosting.com
 */

// Normal functions
// Function to retrieve remote XML for update check
	function check_remote_xml($xmlurl,$destfile)
	{
		$feed = simplexml_load_file($xmlurl);
		if ($feed)
		{
			// $feed is valid, save it
			$feed->asXML($destfile);
		} elseif (file_exists($destfile))
		{
			// $feed is not valid, grab the last backup
			$feed = simplexml_load_file($destfile);
		}
		else
		{
			die('Unable to retrieve XML file');
		}
	}

// Class controller & static functions
//class module_controller
class module_controller extends ctrl_module
{
// Module update check functions
    static function getModuleVersion()
	{
        global $zdbh, $controller, $zlo;
        $module_path="./modules/" . $controller->GetControllerRequest('URL', 'module');
        
        // Get Update URL and Version From module.xml
        $mod_xml = "./modules/" . $controller->GetControllerRequest('URL', 'module') . "/module.xml";
        $mod_config = new xml_reader(fs_filehandler::ReadFileContents($mod_xml));
        $mod_config->Parse();
        $module_version = $mod_config->document->version[0]->tagData;
		echo " " . $module_version;
    }
	
    static function getCheckUpdate()
	{
        global $zdbh, $controller, $zlo;
        $module_path="./modules/" . $controller->GetControllerRequest('URL', 'module');
        
        // Get Update URL and Version From module.xml
        $mod_xml = "./modules/" . $controller->GetControllerRequest('URL', 'module') . "/module.xml";
        $mod_config = new xml_reader(fs_filehandler::ReadFileContents($mod_xml));
        $mod_config->Parse();
        $module_updateurl = $mod_config->document->updateurl[0]->tagData;
        $module_version = $mod_config->document->version[0]->tagData;

        // Download XML in Update URL and get Download URL and Version
        $myfile = check_remote_xml($module_updateurl, $module_path . "/" . $controller->GetControllerRequest('URL', 'module') . ".xml");
        $update_config = new xml_reader(fs_filehandler::ReadFileContents($module_path . "/" . $controller->GetControllerRequest('URL', 'module') . ".xml"));
        $update_config->Parse();
        $update_url = $update_config->document->downloadurl[0]->tagData;
        $update_version = $update_config->document->latestversion[0]->tagData;

        if ($update_version > $module_version)
            return true;
        return false;
    }

/* Testing - Not needed (in core) if you use 'class module_controller extends ctrl_module' instead of 'class module_controller'

    static function getModuleDesc()
    {
        $module_desc = ui_language::translate(ui_module::GetModuleDescription());
        return $module_desc;
    }

	static function getModuleName()
    {
        $module_name = ui_module::GetModuleName();
        return $module_name;
    }

    static function getModuleIcon()
    {
        global $controller;
        $mod_dir = $controller->GetControllerRequest('URL', 'module');
        // Check if the current userland theme has a module icon override
        if (file_exists('etc/styles/' . ui_template::GetUserTemplate() . '/img/modules/' . $mod_dir . '/assets/icon.png'))
            return './etc/styles/' . ui_template::GetUserTemplate() . '/img/modules/' . $mod_dir . '/assets/icon.png';
        return './modules/' . $mod_dir . '/assets/icon.png';
    }

    static function getCSFR_Tag()
    {
        return runtime_csfr::Token();
    }
*/

// module specific static functions
    static function getDomainSelected()
	{
        if (isset($_POST['domain']))
		{
			return true;
        }
		else
		{
        	return false;
        }
    }

    static function getRestorePages()
	{
        if (isset($_POST['restore']))
		{
			return true;
        }
		else
		{
        	return false;
        }
    }

    static function getOpenEditor()
	{
		global $controller;
		if ($controller->GetAllControllerRequests('FORM'))
		{
			$domain = $controller->GetAllControllerRequests('FORM');
			$domain = array_shift($domain);
			$theEditor = '
			<iframe src="./modules/' . $controller->GetControllerRequest('URL', 'module') . '/code/editor.php?domain=' . $domain . '" id="ep_editor" name="ep_editor" width="100%" height="985" scrolling="auto" frameborder="0"></iframe>
			';
			//include("./modules/" . $controller->GetControllerRequest('URL', 'module') . "/code/editor.php?domain=" . $domain);
			return $theEditor;
        }
		else
		{
        	return false;
        }
    }

    static function getDomVar()
	{
        if (isset($_POST['domain']))
		{
			$domVar = $_POST['domain'];
			return $domVar;
        }
		else
		{
        	return false;
        }
    }

    static function ListDomains($uid = 0)
	{
        global $zdbh;
        if ($uid == 0)
		{
            $sql = "SELECT * FROM x_vhosts WHERE vh_deleted_ts IS NULL ORDER BY vh_name_vc ASC";
            $numrows = $zdbh->prepare($sql);
        }
		else
		{
            $sql = "SELECT * FROM x_vhosts WHERE vh_acc_fk=:uid AND vh_deleted_ts IS NULL ORDER BY vh_name_vc ASC";
            $numrows = $zdbh->prepare($sql);
            $numrows->bindParam(':uid', $uid);
        }
        $numrows->execute();
        if ($numrows->fetchColumn() <> 0)
		{
            if ($uid == 0)
			{
                $sql = $zdbh->prepare($sql);
            }
			else
			{
                $sql = $zdbh->prepare($sql);
                $sql->bindParam(':uid', $uid);
            }
            $res = array();
            $sql->execute();
            while ($rowdomains = $sql->fetch())
			{
                array_push($res, array(
                    'uid' => $rowdomains['vh_acc_fk'],
                    'name' => $rowdomains['vh_name_vc'],
                    'directory' => $rowdomains['vh_directory_vc'],
                    'active' => $rowdomains['vh_active_in'],
                    'id' => $rowdomains['vh_id_pk'],
                ));
            }
            return $res;
        }
		else
		{
            return false;
        }
    }

    static function ListDomainDirs($uid)
	{
        global $controller;
        $currentuser = ctrl_users::GetUserDetail($uid);
        $res = array();
        $handle = @opendir(ctrl_options::GetSystemOption('hosted_dir') . $currentuser['username'] . "/public_html");
        $chkdir = ctrl_options::GetSystemOption('hosted_dir') . $currentuser['username'] . "/public_html/";
        if (!$handle)
		{
            # Log an error as the folder cannot be opened...?
        }
		else
		{
            while ($file = @readdir($handle))
			{
                if ($file != "." && $file != ".." && $file != "_errorpages")
				{
                    if (is_dir($chkdir . $file))
					{
                        array_push($res, array('domains' => $file));
                    }
                }
            }
            closedir($handle);
        }
        return $res;
    }
		
    static function getDomainList()
	{
        $currentuser = ctrl_users::GetUserDetail();
        $res = array();
        $domains = self::ListDomains($currentuser['userid']);
        if (!fs_director::CheckForEmptyValue($domains))
		{
            foreach ($domains as $row)
			{
                $status = self::getDomainStatusHTML($row['active'], $row['id']);
                $res[] = array('name' => $row['name'],
                               'directory' => $row['directory'],
                               'active' => $row['active'],
                               'status' => $status,
                               'id' => $row['id']);
            }
            return $res;
        }
		else
		{
            return false;
        }
    }

    static function getDomainStatusHTML($int, $id)
	{
        global $controller;
        if ($int == 1)
		{
            return '<td><font color="green">' . ui_language::translate('Live') . '</font></td>' . '<td></td>';
        }
		else
		{
            return '<td><font color="orange">' . ui_language::translate('Pending') . '</font></td>'
                 . '<td><a href="" class="help_small" id="help_small_' . $id . '_a"'
                 . 'title="' . ui_language::translate('Your domain will become active at the next scheduled update.  This can take up to one hour.') . '">'
                 . '<img src="/modules/' . $controller->GetControllerRequest('URL', 'module') . '/assets/help_small.png" border="0" /></a>';
        }
    }

    static function getCurrentDomain()
	{
        global $controller;
        $domain = $controller->GetControllerRequest('URL', 'domain');
        return ($domain) ? $domain : '';
    }
	
    static function getResult()
    {
        if (!fs_director::CheckForEmptyValue(self::$blank))
		{
            return ui_sysmessage::shout(ui_language::translate("Your Domain can not be empty. Please enter a valid Domain Name and try again."), "zannounceerror");
        }
        if (!fs_director::CheckForEmptyValue(self::$badname))
		{
            return ui_sysmessage::shout(ui_language::translate("Your Domain name is not valid. Please enter a valid Domain Name: i.e. 'domain.com'"), "zannounceerror");
        }
        if (!fs_director::CheckForEmptyValue(self::$alreadyexists))
		{
            return ui_sysmessage::shout(ui_language::translate("The domain already appears to exist on this server."), "zannounceerror");
        }
        if (!fs_director::CheckForEmptyValue(self::$error))
		{
            return ui_sysmessage::shout(ui_language::translate("Please remove 'www'. The 'www' will automatically work with all Domains / Subdomains."), "zannounceerror");
        }
        if (!fs_director::CheckForEmptyValue(self::$writeerror))
		{
            return ui_sysmessage::shout(ui_language::translate("There was a problem writting to the virtual host container file. Please contact your administrator and report this error. Your domain will not function until this error is corrected."), "zannounceerror");
        }
        if (!fs_director::CheckForEmptyValue(self::$ok))
		{
            return ui_sysmessage::shout(ui_language::translate("Changes to your domain web hosting has been saved successfully."), "zannounceok");
        }
        return;
    }

// Module translation static functions - Not implemented yet	
    static function getRestart()
	{
        $message = ui_language::translate("Select a different domain or Cancel");
        return $message;
    }

    static function getCopyright()
	{
        $message = '<font face="ariel" size="2">' . ui_module::GetModuleName() . ' v3.0.4 &copy; 2013-'.date("Y").' by <a target="_blank" href="http://forums.sentora.org/member.php?action=profile&uid=2">TGates</a> for <a target="_blank" href="http://sentora.org">Sentora Control Panel</a>&nbsp;&#8212;&nbsp;Help support future development of this module and donate today!</font>
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