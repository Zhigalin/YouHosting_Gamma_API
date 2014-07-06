<?php
/*
 * account.class.php
 * 
 * Copyright 2014 Hans <hans@grendelhosting.com>
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 * 
 * 
 */
 
class account{
	private $yh;
	private $domain;
	private $id;
	private $client;
	
	function __construct($connector){
		$this->yh = $connector;
	}
	
	public function linkDomain($domain){
		$this->domain = $domain;
		
		$page = $this->yh->get("http://www.youhosting.com/en/client-account/manage?domain=".urlencode($domain)."&account_id=&username=&email=&account_status=0&has_content=any&hostingplan=0&from=&to=&submit=Search");
		
		$id = $this->yh->getBetween($page,'<a class="icon icon-2" title="Edit Account" href="/en/client-account/edit/id/','"><span>Edit</span></a>');
		
		if(is_numeric($id)){
			$this->id = $id;
		} else {
			throw new Exception("Unable to find the account ID. Are you sure the domain is valid?");
		}
	}
	
	public function getID(){
		return $this->id;
	}
	
	public function getDomain(){
		return $this->domain;
	}
	
	public function linkID($id){
		$output = $this->yh->get("http://www.youhosting.com/en/client-account/edit/id/".$id);
		if($this->yh->getBetween($output,'<h3>','</h3>') == "Application error"){
			throw new Exception("No account found with that ID");
		}
		$this->id = $id;
		
		$page = $this->yh->getBetween($output,'<div class="b-cont">','</div>');
				
		$dom = new DOMDocument();
        $dom->loadHTML($page);
        $tables = $dom->getElementsByTagName('table');
        $rows = $tables->item(0)->getElementsByTagName('tr');
    
        foreach ($rows as $row) {
            $cols = $row->getElementsByTagName('td');
            $key=$cols->item(0)->nodeValue;
            $value=$cols->item(1)->nodeValue;
            if ($key=="Domain"){
                $this->domain = $value;
            }
        }		
	}
	
	public function getClient(){
		if(!empty($client)){
			return $client;
		}
		
		$client = new Client($this->yh);
		
		$output = $this->yh->get("http://www.youhosting.com/en/client-account/edit/id/".$this->id);
		
		$clientId = $this->yh->getBetween($output,'<a href="/en/client/view/id/', '">');
		
		$client->linkId($clientId);
		
		$this->client = $client;
		
		return $client;
	}
	
	/*  createAccount = work in progress  */
	public function createAccount(){
		
	}
		
	public function delete(){
		$this->yh->get("http://www.youhosting.com/en/client-account/delete/id/".$this->id);
	}
    
    public function changePassword($password){
        $postdata = array('password' => $password, 'password_confirm' => $password, 'submit' => "Change");
        $output = $this->yh->post("http://www.youhosting.com/en/client-account/change-password/id/".$this->id, $postdata);
        if(!empty($output)){
            throw new Exception('Error while changing password: '.$this->yh->getBetween($output,'<ul class="errors"><li>','</li>'));
        }
    }
    
    public function changePlan($planid, $period = null){
        $postdata = array('reseller_hostingplan_id' => $planid);
        if(!empty($period)){
            $postdata['period']=$period;
        }
        $output = $this->yh->post("http://www.youhosting.com/en/client-account/change-hosting-plan/id/".$this->id, $postdata);
        if(!empty($output)){
            throw new Exception("Change account plan failed");
        }
    }
    
    public function getLoginUrl(){
        $output = $this->yh->getHeaders("http://www.youhosting.com/en/jump-to/client-account/id/".$this->id);
        return $this->yh->getBetween($output, "Location: ","\n");
    }
    
    public function getCount(){
        $output = $this->yh->get("http://www.youhosting.com/");
        return $this->yh->getBetween($output,'<a href="/en/client-account">','</a>');
    }
    
    public function getCountActive(){
        $output = $this->yh->get("http://www.youhosting.com/");
        return $this->yh->getBetween($output,'<a href="/en/client-account/manage/status/active">','</a>');
    }
}
