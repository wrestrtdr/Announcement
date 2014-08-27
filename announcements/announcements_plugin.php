<?php
/**
 * Announcements Plugin
 * 
 * @package blesta
 * @subpackage blesta.plugins.Announcements
 * @copyright Copyright (c) 2005, Naja7host SARL.
 * @link http://www.naja7host.com/ Naja7host
 */
 
class AnnouncementsPlugin extends Plugin {

	public function __construct() {
		Language::loadLang("announcements", null, dirname(__FILE__) . DS . "language" . DS);
		
		// Load components required by this plugin
		Loader::loadComponents($this, array("Input", "Record"));
		
        // Load modules for this plugun
        Loader::loadModels($this, array("ModuleManager"));
		$this->loadConfig(dirname(__FILE__) . DS . "config.json");
	}
	
	/**
	 * Performs any necessary bootstraping actions
	 *
	 * @param int $plugin_id The ID of the plugin being installed
	 */
	public function install($plugin_id) {	
			
		// Add the system overview table, *IFF* not already added
		try {
			// Announcements  table
			$this->Record->
				setField("id", array('type'=>"int", 'size'=>10, 'unsigned'=>true, 'auto_increment'=>true))->		
				setField("title", array('type'=>"varchar", 'size'=>255))->
				setField("body", array('type'=>"text"))->
				setField("date_added", array('type'=>"datetime"))->
				setField("active", array('type'=>"int", 'size'=>10, 'default'=>1))->	
				setField("public", array('type'=>"tinyint", 'size'=>1, 'default'=>0))->				
				setField("company_id", array('type'=>"int", 'size'=>10, 'unsigned'=>true))->				
				setField("permit_client_groups", array('type'=>"tinyint", 'size'=>1, 'default'=>0))->
				setField("permit_packages", array('type'=>"tinyint", 'size'=>1, 'default'=>0))->				
				setKey(array("id"), "primary")->
				setKey(array("company_id"), "index")->
				create("nh_announcement_news", true);
					
			// Announcements client groups
			$this->Record->
				setField("announcement_id", array('type'=>"int", 'size'=>10, 'unsigned'=>true))->
				setField("client_group_id", array('type'=>"int", 'size'=>10, 'unsigned'=>true))->
				setKey(array("announcement_id", "client_group_id"), "primary")->
				create("nh_announcement_groups", true);
			
			// Announcements Client packages
			$this->Record->
				setField("announcement_id", array('type'=>"int", 'size'=>10, 'unsigned'=>true))->
				setField("package_id", array('type'=>"int", 'size'=>10, 'unsigned'=>true))->
				setKey(array("announcement_id", "package_id"), "primary")->
				create("nh_announcement_packages", true);
				
		}
		catch(Exception $e) {
			// Error adding... no permission?
			$this->Input->setErrors(array('db'=> array('create'=>$e->getMessage())));
			return;
		}
	}
	
    /**
     * Performs migration of data from $current_version (the current installed version)
     * to the given file set version
     *
     * @param string $current_version The current installed version of this plugin
     * @param int $plugin_id The ID of the plugin being upgraded
     */
	public function upgrade($current_version, $plugin_id) {
		
		// Upgrade if possible
		if (version_compare($this->getVersion(), $current_version, ">")) {
			// Handle the upgrade, set errors using $this->Input->setErrors() if any errors encountered
		}
	}
	
    /**
     * Performs any necessary cleanup actions
     *
     * @param int $plugin_id The ID of the plugin being uninstalled
     * @param boolean $last_instance True if $plugin_id is the last instance across all companies for this plugin, false otherwise
     */
	public function uninstall($plugin_id, $last_instance) {
		if (!isset($this->Record))
			Loader::loadComponents($this, array("Record"));
		
		// Remove all tables *IFF* no other company in the system is using this plugin
		if ($last_instance) {
			try {
				$this->Record->drop("nh_announcement_news");
				$this->Record->drop("nh_announcement_groups");
				$this->Record->drop("nh_announcement_packages");
			}
			catch (Exception $e) {
				// Error dropping... no permission?
				$this->Input->setErrors(array('db'=> array('create'=>$e->getMessage())));
				return;
			}
		}
 
	}

	
	/**
	 * Returns all actions to be configured for this widget (invoked after install() or upgrade(), overwrites all existing actions)
	 *
	 * @return array A numerically indexed array containing:
	 * 	-action The action to register for
	 * 	-uri The URI to be invoked for the given action
	 * 	-name The name to represent the action (can be language definition)
	 */
	public function getActions() {
		return array(
			array(
				'action'=>"nav_primary_client",
				'uri'=>"plugin/announcements/client_main/",
				'name'=>Language::_("AnnouncementsPlugin.client_main", true)
			),
			array(
				'action'=>"widget_client_home",
				'uri'=>"plugin/announcements/client_widget/",
				'name'=>Language::_("AnnouncementsPlugin.client_widget", true)
			),
			array(
				'action' => "nav_secondary_staff",
				'uri' => "plugin/announcements/admin_main/",
				'name' => Language::_("AnnouncementsPlugin.admin_main", true),
				'options' => array('parent' => "tools/")
			)			
		);
	}

	
	/**
	 * Execute the cron task
	 *
	 */

	public function cron($key) {
		// Todo a task 
	}

	
	/**
	 * Attempts to add new cron tasks for this plugin
	 *
	 */

	private function addCronTasks(array $tasks) {
		// TODO
	}	
	
}
?>