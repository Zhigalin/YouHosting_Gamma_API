<?php
/*
 * client.class.php
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
 
class client{
	private $yh;
	private $email;
	private $id;
	
	function __construct($connector){
		$this->yh = $connector;
	}
	
	public function linkEmail($email){
		$this->email = $email;
		
		$page = $this->yh->get("http://www.youhosting.com/en/client/manage?email=".$email."&submit=Search");
		
		$id = $this->yh->getBetween($page,'<a class="" href="/en/client/view/id/','">');
		
		if(is_numeric($id)){
			$this->id = $id;
		} else {
			throw new Exception("Unable to find the client ID. Are you sure the email is valid?");
		}
	}
    
	public function linkId($id){
		$this->id = $id;
	}
    
	public function getId(){
		return $this->id;
	}
    
	/**
	 * Create a new account
	 **/
	public function createNew($email,$pass,$country,$name){
		$output = $this->yh->post('http://www.youhosting.com/en/client/add',array('email'=>$email, 'first_name'=>$name, 'country'=> $country, 'password' => $pass, 'password_confirm'=>$pass, 'send_email'=>'1','submit'=>'Save'));
		
		if(empty($output)){
			$this->linkEmail($email);
			return true;
		}
		
		throw new Exception('Error while creating account: '.$this->yh->getBetween($output,'<ul class="errors"><li>','</li></ul>'));
		
        return false;
	}
	
	/**
	 * Change the status of an account
	 * 
	 * @param $status: status of the account. can be 'pending_phone_confirmation', 'pending_confirmation', 'active', 'suspended' or 'canceled'
	 * @param $allAccounts: boolean whether all accounts should be changed. can be '1' or '0'
	 * @param $allVps: boolean whether all vps should be changed. can be '1' or '0'
	 * @param $reason: the reason for the account change. can be 'none', 'abuse', 'non_payment' or 'fraud'
	 **/
	public function changeStatus($status,$allAccounts,$allVps,$reason,$notes){
		$postData = array('status'=>$status,'change_accounts'=>$allAccounts,'change_vps'=>$allVps,'reason'=>$reason,'notes'=>$notes);
		
		$output = $this->yh->post('http://www.youhosting.com/en/client/change-status/id/'.$this->id,$postData);
		
		if($output == ''){
			return true;
		} else {
			throw new Exception('Error while modifying account: '.$this->yh->getBetween($output,'<ul class="errors"><li>','</li></ul>'));
		}
	}
	
    /**
     * Suspend a client
     * 
     * @param $allAccounts: boolean whether all accounts should be changed. can be '1' or '0'
	 * @param $allVps: boolean whether all vps should be changed. can be '1' or '0'
	 * @param $reason: the reason for the account change. can be 'none', 'abuse', 'non_payment' or 'fraud'
     **/
	public function suspend($allAccounts,$allVps,$reason,$notes){
		try{
			return $this->changeStatus('suspended',$allAccounts,$allVps,$reason,$notes);
		} catch (Exception $e){
			throw $e;
		}
	}
    /**
     * Get the e-mail address of a client
     **/
    public function getEmail(){
        if(!empty($this->email)){
            return $this->email;
        }
        
        $output = $this->yh->get("http://www.youhosting.com/en/client/view/id/".$this->id);
        
        $email = $this->yh->getBetween($output, '<td><a class="jdax" title="New ticket" href="/en/client-support/new-client-ticket/id/'.$this->id.'">', '</a></td>');
        
        $this->email = $email;
        
        return $email;
    }
    
    /**
     * Modify the account balance of the client
     * 
     * @param $amount the amount to change the balance by. a positive or negative value of your default currency
     * @param $description a textual description of the payment
     * @param $gateway the payment gateway of this transaction (optional)
     * @param $invoice the invoice number of the transaction (without prefix letters) (optional)
     **/
    public function modifyBalance($amount, $description, $gateway = "Not Provided", $invoice = null){
        $postdata = array('amount' => $amount, 'description' => $description, 'invoice_id' => $invoice, 'gateway' => $gateway);
        $output = $this->yh->post("http://www.youhosting.com/en/client/balance/id/".$this->id,$postdata);
        if(!empty($output)){
            throw new Exception($this->yh->getBetween($output,"<p><strong>error: </strong>","</p>"));
        }
    }
    
    public function getBalance(){       
        $output = $this->yh->get("http://www.youhosting.com/en/client/view/id/".$this->id);
        
        $balance = $this->yh->getBetween($output, "<span>Balance ", " ");
        
        $this->balance = $balance;
        
        return $this->balance;
    }
    
    /**
     * Cover the invoices of the client
     * 
     * @param $invoice the invoice ID of the client without prefix letters (optional)
     **/
    public function coverInvoices($invoice = null){
        $url = "http://www.youhosting.com/en/client/cover/id/".$this->id;
        if(!empty($invoice)){
            $url.="?invoice_id=".$invoice;
        }
        $this->yh->get($url);
    }
    
    /**
     * Change the password of this client
     * 
     * @param $password the new password
     **/
    public function changePassword($password){
        $postdata = array('password' => $password, 'password_confirm' => $password, 'submit' => "Change");
        $output = $this->yh->post("http://www.youhosting.com/en/client/change-password/id/".$this->id, $postdata);
        if(!empty($output)){
            throw new Exception('Error while changing password: '.$this->yh->getBetween($output,'<ul class="errors"><li>','</li>'));
        }
    }
    
    public function getLoginUrl(){
        $output = $this->yh->getHeaders("http://www.youhosting.com/en/jump-to/client-area/id/".$this->id);
        return $this->yh->getBetween($output, "Location: ","\n");
    }
    
    public function getCount(){
        $output = $this->yh->get("http://www.youhosting.com/");
        return $this->yh->getBetween($output,'<a href="/en/client">','</a>');
    }
}
