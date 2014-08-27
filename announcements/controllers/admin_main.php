<?php
/**
 * Announcements Plugin
 * 
 * @package blesta
 * @subpackage blesta.plugins.Announcements
 * @copyright Copyright (c) 2005, Naja7host SARL.
 * @link http://www.naja7host.com/ Naja7host
 */
 
class AdminMain extends AppController {

    /**
     * Performs necessary initialization
     */
	
	/**
	 * Returns the view to be rendered when managing this plugin
	 */
	 
    private function init() {
        // Require login
        $this->requireLogin();

		Language::loadLang("admin_main", null, PLUGINDIR . "announcements" . DS . "language" . DS);

		$this->uses(array("Announcements.Announcements"));
		
		// $this->Date = $this->parent->Date;
		
		// Set the Data Structure Array
		$this->helpers(array("DataStructure"));
		$this->ArrayHelper = $this->DataStructure->create("Array");
		
        // Set the company ID
        $this->company_id = Configure::get("Blesta.company_id");
		
		// Restore structure view location of the admin portal
		$this->structure->setDefaultView(APPDIR);
		$this->structure->setView(null, $this->structure->view);
		
		$this->total_announcements = $this->Announcements->getListCount($this->company_id, "nh_announcement_news") ; 
		
		$language = Language::_("AnnouncementsPlugin." . Loader::fromCamelCase($this->action ? $this->action : "index") . ".page_title", true);
		$this->structure->set("page_title", $language);
		
    }
	
    public function index() {
		$this->init();
		$this->set("status", "settings");
		$this->set("announcements", $this->total_announcements);
		$this->view->setView(null, "Announcements.default");
			
    }

	/**
	 * Announcement
	 */	
    public function announcements() {
		$this->init();
		$page = (isset($this->get[0]) ? (int)$this->get[0] : 1);
		$sort = (isset($this->get['sort']) ? $this->get['sort'] : "date_added");
		$order = (isset($this->get['order']) ? $this->get['order'] : "desc");
		
		$announcements = $this->Announcements->getAllAnnouncements($this->company_id , $page ) ;

		$vars = array();
		
		$this->set("status", "announcements");
		$this->set("lesannouncements", $announcements);
		$this->set("announcements", $this->total_announcements);
		$this->set("vars", $vars);
		$this->view->setView(null, "Announcements.default");

		// Set pagination parameters, set group if available
		$params = array('sort'=>$sort,'order'=>$order);
		
		// Overwrite default pagination settings
		$settings = array_merge(Configure::get("Blesta.pagination"), array(
				'total_results' => $this->total_announcements,
				'uri'=> $this->base_uri . "plugin/announcements/admin_main/announcements/[p]/",
				'params'=>$params
			)
		);
		$this->helpers(array("Pagination"=>array($this->get, $settings)));
		$this->Pagination->setSettings(Configure::get("Blesta.pagination_ajax"));		
		
		if ($this->isAjax())
			return $this->renderAjaxWidgetIfAsync(isset($this->get[0]) || isset($this->get['sort']));
			
    }

		
	/**
	 * Settings
	 */
	public function settings() {
		$this->init();
		$this->set("status", "settings");
		$this->set("announcements", $this->total_announcements);
		$this->view->setView(null, "Announcements.default");			
	}	

	/**
	 * permissions
	 */
	public function permissions() {
		$this->init();
		$this->set("status", "permissions");
		$this->set("announcements", $this->total_announcements);
		$this->view->setView(null, "Announcements.default");				
	}
	
	/**
	 * Add a Announcement
	 */
	public function addAnnouncement() {
		$this->init();
		
		$this->uses(array("ClientGroups", "Packages"));
		
		// Get all client groups and packages for selection
		$client_groups = $this->ArrayHelper->numericToKey($this->ClientGroups->getAll($this->company_id), "id", "name");
		$packages = $this->ArrayHelper->numericToKey($this->Packages->getAll($this->company_id, array('name' => "ASC"), "active"), "id", "name");
		
		// Set vars
		$vars = array(
			// 'plugin_id' => $this->plugin_id,
			'client_groups' => $client_groups,
			'packages' => $packages
		);
		unset($client_groups, $packages);
		
		if (!empty($this->post)) {
		
			// print_r($this->post);
			
			// Set the category this file is to be added in
			$data = array(
				'company_id' => $this->company_id
			);
			
			// Set vars according to selected items
			if (isset($this->post['type']) && $this->post['type'] == "public")
				$data['public'] = "1";
			else {
				// Set availability to groups/packages
				if (isset($this->post['available_to_client_groups']) && $this->post['available_to_client_groups'] == "1")
					$data['permit_client_groups'] = "1";
				if (isset($this->post['available_to_packages']) && $this->post['available_to_packages'] == "1")
					$data['permit_packages'] = "1";
			}
			
			// Set any client groups/packages
			if (isset($data['permit_client_groups']))
				$data['announcement_groups'] = isset($this->post['announcement_groups']) ? (array)$this->post['announcement_groups'] : array();
			if (isset($data['permit_packages']))
				$data['announcement_packages'] = isset($this->post['announcement_packages']) ? (array)$this->post['announcement_packages'] : array();
			
			$data = array_merge($this->post, $data);
			
			// Add the download
			$this->Announcements->add($data);
			
			if (($errors = $this->Announcements->errors())) {
				// Error, reset vars
				$vars['vars'] = (object)$this->post;
				$this->setMessage("error", $errors, false, null, false);
			}
			else {
				// Success
				$this->flashMessage("message", Language::_("AnnouncementsPlugin.!success.announcement_added", true));
				$this->redirect($this->base_uri . "plugin/announcements/admin_main/");
			}
		}
		
		// Set all selected client groups in assigned and unset all selected client groups from available
		if (isset($vars['vars']->announcement_groups) && is_array($vars['vars']->announcement_groups)) {
			$selected = array();
			
			foreach ($vars['client_groups'] as $id => $name) {
				if (in_array($id, $vars['vars']->announcement_groups)) {
					$selected[$id] = $name;
					unset($vars['client_groups'][$id]);
				}
			}
			
			$vars['vars']->announcement_groups = $selected;
		}
		
		// Set all selected packages in assigned and unset all selected packages from available
		if (isset($vars['vars']->announcement_packages) && is_array($vars['vars']->announcement_packages)) {
			$selected = array();
			
			foreach ($vars['packages'] as $id => $name) {
				if (in_array($id, $vars['vars']->announcement_packages)) {
					$selected[$id] = $name;
					unset($vars['packages'][$id]);
				}
			}
			
			$vars['vars']->announcement_packages = $selected;
		}
		
		// Set the view to render
		$this->set("vars", $vars);
		$this->view->setView(null, "Announcements.default");

		// Include WYSIWYG
		$this->Javascript->setFile("ckeditor/ckeditor.js", "head", VENDORWEBDIR);
		$this->Javascript->setFile("ckeditor/adapters/jquery.js", "head", VENDORWEBDIR);
		
		// Set the view to render
		// return $this->partial("admin_main_addannouncement", $vars);		
	}
	
	/**
	 * Edit Announcement
	 */
	public function editAnnouncement() {
		$this->init();
		
		// Ensure a Announcement was given
		if (!isset($this->get[0]) || !($announcement = $this->Announcements->get($this->get[0])) ||
			($announcement->company_id != $announcement->company_id))
			$this->redirect($this->base_uri . "plugin/announcements/admin_main/");
		
		$this->uses(array("ClientGroups", "Packages"));
		
		// Get all client groups and packages for selection
		$client_groups = $this->ArrayHelper->numericToKey($this->ClientGroups->getAll($this->company_id), "id", "name");
		$packages = $this->ArrayHelper->numericToKey($this->Packages->getAll($this->company_id, array('name' => "ASC"), "active"), "id", "name");
		
		// Set vars
		$vars = array(
			// 'plugin_id' => $this->plugin_id,
			'client_groups' => $client_groups,
			'packages' => $packages
		);
		unset($client_groups, $packages);
		
		
		if (!empty($this->post)) {
			
			// print_r($this->post);
			
			// Set the category this file belongs to
			$data = array(
				'company_id' => $this->company_id
			);
			
			// Set vars according to selected items
			if (isset($this->post['type']) && $this->post['type'] == "public") {
				$data['public'] = "1";
				$data['permit_client_groups'] = "0";
				$data['permit_packages'] = "0";
			}
			else {
				$data['public'] = "0";
				
				// Set availability to groups/packages
				if (isset($this->post['available_to_client_groups']) && $this->post['available_to_client_groups'] == "1")
					$data['permit_client_groups'] = "1";
				if (isset($this->post['available_to_packages']) && $this->post['available_to_packages'] == "1")
					$data['permit_packages'] = "1";
			}
			
			// Set any client groups/packages
			if (isset($data['permit_client_groups']))
				$data['announcement_groups'] = isset($this->post['announcement_groups']) ? (array)$this->post['announcement_groups'] : array();
			if (isset($data['permit_packages']))
				$data['announcement_packages'] = isset($this->post['announcement_packages']) ? (array)$this->post['announcement_packages'] : array();
			
	
			$data = array_merge($this->post, $data);
			
			// Update the download
			$this->Announcements->edit($announcement->id, $data);
			
			if (($errors = $this->Announcements->errors())) {
				// Error, reset vars
				$vars['vars'] = (object)$this->post;
				$this->setMessage("error", $errors, false, null, false);
			}
			else {
				// Success
				$this->flashMessage("message", Language::_("AnnouncementsPlugin.!success.announcement_updated", true));
				$this->redirect($this->base_uri . "plugin/announcements/admin_main/");
			}
		}
		
		// Set initial packages/client groups
		if (empty($vars['vars'])) {
			$vars['vars'] = $announcement;
			$vars['vars']->announcement_groups = $this->ArrayHelper->numericToKey($announcement->client_groups, "client_group_id", "client_group_id");
			$vars['vars']->announcement_packages = $this->ArrayHelper->numericToKey($announcement->packages, "package_id", "package_id");

			
			// Set default radio/checkboxes
			if ($announcement->permit_client_groups == "1" || $announcement->permit_packages == "1") {
				$vars['vars']->type = "logged_in";
				$vars['vars']->available_to_client_groups = ($announcement->permit_client_groups == "1" ? $announcement->permit_client_groups : "0");
				$vars['vars']->available_to_packages = ($announcement->permit_packages == "1" ? $announcement->permit_packages : "0");
			}
		}
		
		// Set all selected client groups in assigned and unset all selected client groups from available
		if (isset($vars['vars']->announcement_groups) && is_array($vars['vars']->announcement_groups)) {
			$selected = array();
			
			foreach ($vars['client_groups'] as $id => $name) {
				if (in_array($id, $vars['vars']->announcement_groups)) {
					$selected[$id] = $name;
					unset($vars['client_groups'][$id]);
				}
			}
			
			$vars['vars']->announcement_groups = $selected;
		}
		
		// Set all selected packages in assigned and unset all selected packages from available
		if (isset($vars['vars']->announcement_packages) && is_array($vars['vars']->announcement_packages)) {
			$selected = array();
			
			foreach ($vars['packages'] as $id => $name) {
				if (in_array($id, $vars['vars']->announcement_packages)) {
					$selected[$id] = $name;
					unset($vars['packages'][$id]);
				}
			}
			
			$vars['vars']->announcement_packages = $selected;
		}
		
		// Set the view to render
		$this->set("vars", $vars);
		$this->view->setView(null, "Announcements.default");

		// Include WYSIWYG
		$this->Javascript->setFile("ckeditor/ckeditor.js", "head", VENDORWEBDIR);
		$this->Javascript->setFile("ckeditor/adapters/jquery.js", "head", VENDORWEBDIR);
		
	}	
		
	/**
	 * Delete Announcement
	 */
	public function deleteAnnouncement() {
		$this->init();
		// print_r($this->post);
		if (!isset($this->post['id']) || !($announcement = $this->Announcements->get($this->post['id'])) ||
			($announcement->company_id != $this->company_id))
			$this->redirect($this->base_uri . "plugin/announcements/admin_main/");
		// print_r($announcement);
		$this->Announcements->delete($announcement->id);
		
		$this->flashMessage("message", Language::_("AnnouncementsPlugin.!success.announcement_deleted", true));
		$this->redirect($this->base_uri . "plugin/announcements/admin_main/");			
	}			
}
?>