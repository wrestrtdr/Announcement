<?php
/**
 * Announcements Plugin 
 * 
 * @package blesta
 * @subpackage blesta.plugins.Announcements
 * @copyright Copyright (c) 2005, Naja7host SARL.
 * @link http://www.naja7host.com/ Naja7host
 */

class Announcements extends AnnouncementsModel {
	
	/**
	 * Initialize
	 */
	public function __construct() {
		parent::__construct();
		
		Language::loadLang("announcements_model", null, PLUGINDIR . "announcements" . DS . "language" . DS);
	}
	
	
	// 
	// CLIENT SIDE FUNCTION
	// CLIENT SIDE FUNCTION
	//	
	
	/**
	 * Count all Announcements Available to  Client/guest
	 */
	public function getAllAnnouncementsCount($company_id, $client_id = null) {				
		$announcements = $this->getAnnouncementsAvailable($company_id, $client_id)->
			group("temp.id")->
			numResults();		
		return $announcements;		
	}
	
	/**
	 * Retrieves a list of Announcements available to  Client/guest, 
	 */
	public function getAllAvailable($company_id, $client_id = null, $page=1 , $order_by=array('date_added'=>"ASC")) {
		$this->setPerPage(6);
		$announcements = $this->getAnnouncementsAvailable($company_id, $client_id)->
			order($order_by)->
			group("temp.id")->
			limit($this->getPerPage(), (max(1, $page) - 1)*$this->getPerPage())->
			fetchAll();	
		
		return $announcements;
	}	
	
	/**
	 * Checks whether the given client has access to the Announcement
	 */
	public function hasAccessToAnnouncement($announcement_id, $company_id, $client_id = null) {
		// Fetch the files without filtering on category
		$count = $this->getAnnouncementsAvailable($company_id, $client_id, false)->
			where("temp.id", "=", $announcement_id)->group("temp.id")->numResults();
		
		if ($count > 0)
			return true;
		return false;
	}
	
	/**
	 * Partially constructs a Record object for fetching Announcements available to a client/guest
	 */
	private function getAnnouncementsAvailable($company_id, $client_id = null ) {
	
		// Get all public Announcements
		$alias = "nh_announcement_news";
		
		// Set table to 'temp' for consistency
		if (!$client_id) {
			$alias = "temp";
			$this->Record->select($alias . ".*")->
				from(array('nh_announcement_news' => "temp"));
		}
		else
			$this->Record->select("nh_announcement_news.*")->from("nh_announcement_news");	
	
		$this->Record->where($alias . ".public", "=", "1")->
			where($alias . ".active", "=", "1")->
			where($alias . ".company_id", "=", $company_id);

				
	
		// No client was given, so only public files are available, return just those
		if (!$client_id)
			return $this->Record;

		// Use the previous query as subquery to fetch other announceùents  as well
		$subquery_public = $this->Record->get();
		$values = $this->Record->values;
		$this->Record->reset();
		$this->Record->values = $values;
		
		
		// Get all files based on client group
		$this->Record->select("nh_announcement_news.*")->from("nh_announcement_news")->
			innerJoin("nh_announcement_groups", "nh_announcement_groups.announcement_id", "=", "nh_announcement_news.id", false)->
			innerJoin("client_groups", "client_groups.id", "=", "nh_announcement_groups.client_group_id", false)->
			innerJoin("clients", "clients.client_group_id", "=", "client_groups.id", false)->
			where("nh_announcement_news.company_id", "=", $company_id)->
			where("clients.id", "=", $client_id)->
			where("nh_announcement_news.permit_client_groups", "=", "1");

		
		$subquery_group = $this->Record->get();
		$values = $this->Record->values;
		$this->Record->reset();
		$this->Record->values = $values;
		
		// Get all files based on packages
		$this->Record->select("nh_announcement_news.*")->from("nh_announcement_news")->
			innerJoin("nh_announcement_packages", "nh_announcement_packages.announcement_id", "=", "nh_announcement_news.id", false)->
			innerJoin("package_pricing", "package_pricing.package_id", "=", "nh_announcement_packages.package_id", false)->
			innerJoin("services", "services.pricing_id", "=", "package_pricing.id", false)->
			where("services.status", "=", "active")->
			where("services.client_id", "=", $client_id)->
			where("nh_announcement_news.company_id", "=", $company_id)->
			where("nh_announcement_news.permit_packages", "=", "1");
		
		
		$subquery_package = $this->Record->get();
		$values = $this->Record->values;
		$this->Record->reset();
		$this->Record->values = $values;
		
		return $this->Record->select("temp.*")->from(array("((" . $subquery_public . ") UNION (" . $subquery_group . ") UNION (" . $subquery_package . "))" => "temp"));			
	}
	
	
	
	public function getAnnouncementClient($company_id, $client_id = null) {
	
	}
	
	// 
	// ADMIN SIDE FUNCTION
	// ADMIN SIDE FUNCTION
	//
	
	/**
	 * Return the total number of rows,
	 */
	public function getListCount($company_id , $table = null) {
		return $this->Record->select()->from($table)->
			where("company_id", "=", $company_id)->
			numResults();
	}	
	
	/**
	 * Fetches all Announcements (admin_side)
	 */
	public function getAllAnnouncements($company_id , $page=1 , $order_by=array('date_added'=>"ASC")) {		
		return $this->Record->select()->from("nh_announcement_news")->
			where("nh_announcement_news.company_id", "=", $company_id)->
			order($order_by)->
			limit($this->getPerPage(), (max(1, $page) - 1)*$this->getPerPage())->
			fetchAll();
	}	
	
	/**
	 * Fetches the Announcement
	 */ 
	public function get($announcement_id) {
		$announcement = $this->Record->select()->from("nh_announcement_news")->where("id", "=", $announcement_id)->fetch();
		
		if ($announcement) {		
			// Get client groups and packages
			$announcement->packages = $this->getAnnouncementPackages($announcement_id);
			$announcement->client_groups = $this->getAnnouncementGroups($announcement_id);
		}
		
		return $announcement;
	}
	
	/**
	 * Add Announcement
	 */	
	public function add(array $vars) {
		$this->Input->setRules($this->getRules($vars));
		
		$vars['date_added'] = date("c");
		
		if (empty($vars['active']))
			$vars['active'] = 0 ; 
			
		// print_r($vars);
		if ($this->Input->validates($vars)) {
			// Begin a transaction
			$this->Record->begin();

			$fields = array("title", "body", "date_added", "active", "company_id", "public", "permit_client_groups", "permit_packages");
			$this->Record->insert("nh_announcement_news", $vars, $fields);
			$announcement_id = $this->Record->lastInsertId();
			
			// Add client groups
			if ($this->ifSet($vars['permit_client_groups'], "0") == "1" && !empty($vars['announcement_groups']))
				$this->addAnnouncementGroups($announcement_id, $vars['announcement_groups']);
			
			// Add package groups
			if ($this->ifSet($vars['permit_packages'], "0") == "1" && !empty($vars['announcement_packages']))
				$this->addAnnouncementPackages($announcement_id, $vars['announcement_packages']);
			
			// Commit the transaction
			$this->Record->commit();
		}
	}	
		
	/**
	 * Updates a Announcement
	 */
	public function edit($announcement_id, array $vars) {
		$vars['id'] = $announcement_id;
		$this->Input->setRules($this->getRules($vars, true));
		
		if ($this->Input->validates($vars)) {
			// Begin a transaction
			$this->Record->begin();
			
		
			$fields = array("title", "body", "date_added", "active", "public", "permit_client_groups", "permit_packages");
			$this->Record->where("id", "=", $announcement_id)->update("nh_announcement_news", $vars, $fields);
			
			// Add client groups
			$this->deleteAnnouncementGroups($announcement_id);
			if ($this->ifSet($vars['permit_client_groups'], "0") == "1" && !empty($vars['announcement_groups']))
				$this->addAnnouncementGroups($announcement_id, $vars['announcement_groups']);
			
			// Add package groups
			$this->deleteAnnouncementPackages($announcement_id);
			if ($this->ifSet($vars['permit_packages'], "0") == "1" && !empty($vars['announcement_packages']))
				$this->addAnnouncementPackages($announcement_id, $vars['announcement_packages']);
			
			// Commit the transaction
			$this->Record->commit();		

		}
	}	
	
	/**
	 * Deletes a Announcement
	 */
	public function delete($id) {
		$announcement_id = $this->get($id);

		if ($announcement_id) {
			// Begin a transaction
			$this->Record->begin();
			
			// Delete all groups/packages related to the 
			$this->deleteAnnouncementGroups($announcement_id->id);
			$this->deleteAnnouncementPackages($announcement_id->id);
			
			// Delete the 
			$this->Record->from("nh_announcement_news")->where("id", "=", $announcement_id->id )->delete();

			// Commit the changes
			$this->Record->commit();
			
		}
	}
	
	/**
	 * Fetches all of the client groups that this announcement belongs to
	 */
	public function getAnnouncementGroups($announcement_id) {
		return $this->Record->select("client_group_id")->from("nh_announcement_groups")->
			where("announcement_id", "=", $announcement_id)->fetchAll();
	}
	

	/**
	 * Attaches a list of client groups to a announcement
	 */
	private function addAnnouncementGroups($announcement_id, $client_group_ids) {
		// Set all announcement client groups
		foreach ($client_group_ids as $client_group_id)
			$this->Record->insert("nh_announcement_groups", array('announcement_id'=>$announcement_id, 'client_group_id'=>$client_group_id));
	}	
	

	/**
	 * Deletes all client groups attached to this announcement
	 */
	private function deleteAnnouncementGroups($announcement_id) {
		$this->Record->from("nh_announcement_groups")->where("announcement_id", "=", $announcement_id)->delete();
	}
		
	/**
	 * Fetches all of the client groups that this announcement belongs to
	 */
	public function getAnnouncementPackages($announcement_id) {
		return $this->Record->select("package_id")->from("nh_announcement_packages")->
			where("announcement_id", "=", $announcement_id)->fetchAll();
	}
	
	/**
	 * Attaches a list of packages to a announcement
	 */
	private function addAnnouncementPackages($announcement_id, $package_ids) {
		// Set all announcement packages
		foreach ($package_ids as $package_id)
			$this->Record->insert("nh_announcement_packages", array('announcement_id'=>$announcement_id, 'package_id'=>$package_id));
	}
	
	/**
	 * Deletes all packages attached to this announcement
	 */
	private function deleteAnnouncementPackages($announcement_id) {
		$this->Record->from("nh_announcement_packages")->where("announcement_id", "=", $announcement_id)->delete();
	}


	
	/**
	 * Validates that the given client groups may be added for this announcement
	 */
	public function validateGroups($client_group_ids, $permit_client_groups) {
		// Client groups are not permitted, don't bother checking them
		if ($permit_client_groups == "0")
			return true;
		
		// Make sure all client groups being added actually exist
		if ($client_group_ids) {
			foreach ($client_group_ids as $client_group_id) {
				if (!$this->validateExists($client_group_id, "id", "client_groups"))
					return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Validates that the given packages may be added for this file
	 */
	public function validatePackages($package_ids, $permit_packages) {
		// Packages are not permitted, don't bother checking them
		if ($permit_packages == "0")
			return true;
		
		// Make sure all packages being added actually exist
		if ($package_ids) {
			foreach ($package_ids as $package_id) {
				if (!$this->validateExists($package_id, "id", "packages"))
					return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Validates that at least one of the given availability options is set, but not all
	 */
	public function validateAnnouncementAssignment($permit_public, $permit_client_groups, $permit_packages) {
		// Either public is selected, and nothing else, or at least one of the others is selected and not public
		if ($permit_public == "1" && $permit_client_groups == "0" && $permit_packages == "0")
			return true;
		elseif ($permit_public != "1" && ($permit_client_groups == "1" || $permit_packages == "1"))
			return true;
		return false;
	}	
	
	/**
	 * Retrieves a list of rules to validate add/editing announcements
	 */
	private function getRules(array $vars, $edit = false) {
		$rules = array(
			'company_id' => array(
				'exists' => array(
					'rule' => array(array($this, "validateExists"), "id", "companies"), // validateExists
					'message' => $this->_("AnnouncementsPlugin.!error.company_id.exists")
				)
			),
			'title' => array(
				'empty' => array(
					'rule' => "isEmpty",
					'negate' => true,
					'message' => $this->_("AnnouncementsPlugin.!error.title.empty")
				)
			),
			'body' => array(
				'empty' => array(
					'rule' => "isEmpty",
					'negate' => true,
					'message' => $this->_("AnnouncementsPlugin.!error.body.empty")
				)		
			),
			'active' => array(
				'format' => array(
					'if_set' => true,
					'rule' => array("in_array", array("0", "1")),
					'message' => $this->_("AnnouncementsPlugin.!error.active.format")
				)
			),			
			'public' => array(
				'format' => array(
					'if_set' => true,
					'rule' => array("in_array", array("0", "1")),
					'message' => $this->_("AnnouncementsPlugin.!error.public.format")
				),
				'assignment' => array(
					'rule' => array(array($this, "validateAnnouncementAssignment"), $this->ifSet($vars['permit_client_groups'], "0"), $this->ifSet($vars['permit_packages'], "0")),
					'message' => $this->_("AnnouncementsPlugin.!error.public.assignment")
				)
			),
			'permit_client_groups' => array(
				'format' => array(
					'if_set' => true,
					'rule' => array("in_array", array("0", "1")),
					'message' => $this->_("AnnouncementsPlugin.!error.permit_client_groups.format")
				)
			),
			'announcement_groups' => array(
				'format' => array(
					'if_set' => true,
					'rule' => array(array($this, "validateGroups"), $this->ifSet($vars['permit_client_groups'], "0")),
					'message' => $this->_("AnnouncementsPlugin.!error.announcement_groups.format")
				)
			),
			'permit_packages' => array(
				'format' => array(
					'if_set' => true,
					'rule' => array("in_array", array("0", "1")),
					'message' => $this->_("AnnouncementsPlugin.!error.permit_packages.format")
				)
			),
			'announcement_packages' => array(
				'format' => array(
					'if_set' => true,
					'rule' => array(array($this, "validatePackages"), $this->ifSet($vars['permit_packages'], "0")),
					'message' => $this->_("AnnouncementsPlugin.!error.announcement_packages.format")
				)
			)
		);
		
		if ($edit) {
			// Update rules, check that the file exists
			$rules['id'] = array(
				'exists' => array(
					'rule' => array(array($this, "validateExists"), "id", "nh_announcement_news"),
					'message' => $this->_("AnnouncementsPlugin.!error.announcement_id.exists")
				)
			);
		}
		
		return $rules;
	}
	
	
	
	/**
	 * truncate Html
	 */
	public function truncateHtml($text,  $length = 150 , $strip_tags = true , $ending = ' ... ', $exact = false, $considerHtml = true  ) {
		if ($considerHtml) {
			// if the plain text is shorter than the maximum length, return the whole text
			if (strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
				$text = str_replace("&nbsp;", "", $text);
				return strip_tags($text);
			}
			
			// splits all html-tags to scanable lines
			preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER);
			$total_length = strlen($ending);
			$open_tags = array();
			$truncate = '';
			foreach ($lines as $line_matchings) {
				// if there is any html-tag in this line, handle it and add it (uncounted) to the output
				if (!empty($line_matchings[1])) {
					// if it's an "empty element" with or without xhtml-conform closing slash
					if (preg_match('/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is', $line_matchings[1])) {
						// do nothing
					// if tag is a closing tag
					} else if (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $line_matchings[1], $tag_matchings)) {
						// delete tag from $open_tags list
						$pos = array_search($tag_matchings[1], $open_tags);
						if ($pos !== false) {
						unset($open_tags[$pos]);
						}
					// if tag is an opening tag
					} else if (preg_match('/^<\s*([^\s>!]+).*?>$/s', $line_matchings[1], $tag_matchings)) {
						// add tag to the beginning of $open_tags list
						array_unshift($open_tags, strtolower($tag_matchings[1]));
					}
					// add html-tag to $truncate'd text
					$truncate .= $line_matchings[1];
				}
				// calculate the length of the plain text part of the line; handle entities as one character
				$content_length = strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', ' ', $line_matchings[2]));
				if ($total_length+$content_length> $length) {
					// the number of characters which are left
					$left = $length - $total_length;
					$entities_length = 0;
					// search for html entities
					if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', $line_matchings[2], $entities, PREG_OFFSET_CAPTURE)) {
						// calculate the real length of all entities in the legal range
						foreach ($entities[0] as $entity) {
							if ($entity[1]+1-$entities_length <= $left) {
								$left--;
								$entities_length += strlen($entity[0]);
							} else {
								// no more characters left
								break;
							}
						}
					}
					$truncate .= substr($line_matchings[2], 0, $left+$entities_length);
					// maximum lenght is reached, so get off the loop
					break;
				} else {
					$truncate .= $line_matchings[2];
					$total_length += $content_length;
				}
				// if the maximum length is reached, get off the loop
				if($total_length>= $length) {
					break;
				}
			}
		} else {
			if (strlen($text) <= $length) {
				return $text;
			} else {
				$truncate = substr($text, 0, $length - strlen($ending));
			}
		}
		// if the words shouldn't be cut in the middle...
		if (!$exact) {
			// ...search the last occurance of a space...
			$spacepos = strrpos($truncate, ' ');
			if (isset($spacepos)) {
				// ...and cut the text in this position
				$truncate = substr($truncate, 0, $spacepos);
			}
		}
		// add the defined ending to the text
		$truncate .= $ending;
		if($considerHtml) {
			// close all unclosed html-tags
			foreach ($open_tags as $tag) {
				$truncate .= '</' . $tag . '>';
			}
		}
		
		$truncate = str_replace("&nbsp;", " ", $truncate);
		
		if ($strip_tags)
			return strip_tags(htmlspecialchars_decode($truncate));
		else 
			return htmlspecialchars_decode($truncate);
	}
	
}
?>