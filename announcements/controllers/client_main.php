<?php
/**
 * Announcements Plugin
 * 
 * @package blesta
 * @subpackage blesta.plugins.Announcements
 * @copyright Copyright (c) 2005, Naja7host SARL.
 * @link http://www.naja7host.com/ Naja7host
 */
class ClientMain extends AnnouncementsController {

	/**
	 * Pre-action
	 */
	public function preAction() {
		parent::preAction();

		$this->company_id = (isset($this->client->company_id) ? $this->client->company_id : Configure::get("Blesta.company_id"));
		
		Language::loadLang("client_main", null, PLUGINDIR . "announcements" . DS . "language" . DS);

		$this->uses(array("Announcements.Announcements"));
		
		$this->client_id = $this->Session->read("blesta_client_id");
		// $this->setPerPage(6);	
		// Restore structure view location of the client portal
		$this->structure->setDefaultView(APPDIR);
		$this->structure->setView(null, $this->orig_structure_view);
	}


	/**
	 * List Announcement
	 */
	public function index() {
		
		$page = (isset($this->get[0]) ? (int)$this->get[0] : 1);
		$sort = (isset($this->get['sort']) ? $this->get['sort'] : "date_added");
		$order = (isset($this->get['order']) ? $this->get['order'] : "desc");
			
		$this->announcements = $this->Announcements->getAllAvailable($this->company_id, $this->client_id , $page ) ;  
		$this->total_announcements = $this->Announcements->getAllAnnouncementsCount($this->company_id, $this->client_id) ;  
		
		// print_r(count( $this->total_announcements));
		
		foreach ($this->announcements as $announcement ) {
			$announcement->body = $this->Announcements->truncateHtml($announcement->body);
			$announcement->date_added = $this->Date->cast($announcement->date_added , "d-m-Y");
		}	
		
		// Load the Text Parser
		// $this->helpers(array("TextParser"));
		// $parser_syntax = "markdown";
		
		
		// $this->set("string", $this->DataStructure->create("string"));
		$this->set("announcements", $this->announcements);
		$this->view->setView(null, "Announcements.default");
		
		// Overwrite default pagination settings
		$settings = array_merge(Configure::get("Blesta.pagination_client"), array(
				'total_results' => $this->total_announcements,
				'results_per_page' => 6,
				'uri'=> $this->base_uri . "plugin/announcements/client_main/index/[p]/",
				'params'=>array('sort'=>$sort,'order'=>$order)
			)
		);
		$this->helpers(array("Pagination"=>array($this->get, $settings)));
		$this->Pagination->setSettings(Configure::get("Blesta.pagination_ajax"));	
		
	
		
		if ($this->isAjax())
			return $this->renderAjaxWidgetIfAsync(isset($this->get[0]) || isset($this->get['sort']));	

	}
	
	/**
	 * Show Announcement
	 */
	public function view() {
		
		
		// Ensure a announcement ID was provided
		if (!isset($this->get[0]) || !($announcement = $this->Announcements->get($this->get[0])) ||
			($announcement->company_id != $this->company_id) ||
			!$this->Announcements->hasAccessToAnnouncement($announcement->id, $this->company_id, $this->client_id))
			$this->redirect($this->base_uri . "plugin/announcements/client_main/");
		
		$this->set("announcement", $announcement);
		$this->view->setView(null, "Announcements.default");
	}
}
?>