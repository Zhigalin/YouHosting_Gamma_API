<?php
/*
 * connector.class.php
 * 
 * Copyright 2014 Hans <hans@grendelhosting.com>
 * 
 * Based on Unofficial YouHosting API by Maarten Eyskens
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
class connector{
    private $login=YH_USER;
    private $pass=YH_PASS;
    private $cookie;
    
    /**
     * Constructor for the connector class. This logs in to YouHosting and makes the cookies available.
     **/
    public function __construct(){
		$this->cookie = tempnam("/tmp", "COOKIE");
        
        //hack the security code
        $file=file_get_contents("http://www.youhosting.com/en/auth");
        $output=$this->GetBetween($file,"document.write(",");");
        $alphas = array_merge(range('A', 'Z'), range('a', 'z'));
        $delete=array('"',"'","+","<",">"," ","=");
        $key=str_replace($delete,"",$output);
        $key=str_replace($alphas,"",$key);
        $delete=array("'","+","<",">"," ");
        $name=str_replace($delete,"",$output);
        $name= $this->GetBetween($name,'inputname="','"');
        
        //now we can login
        $curl_handle = curl_init ("http://www.youhosting.com/en/auth");
        curl_setopt ($curl_handle, CURLOPT_COOKIEJAR, $this->cookie);
        curl_setopt ($curl_handle, CURLOPT_RETURNTRANSFER, true);
	    $post_array = array('submit'=>'Login', 'email' => $this->login, 'password' => $this->pass, $name=>$key);
	    curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $post_array);
	    $output = curl_exec ($curl_handle);
        //we are logged in now
	}
	
	/**
	 * General purpose function to fetch data from and HTML page.
	 * 
	 **/
    public function getBetween($content,$start,$end){
        $r = explode($start, $content);
        if (isset($r[1])){
            $r = explode($end, $r[1]);
            return $r[0];
        }
    }
    
    /**
     * Parse an HTML table to a PHP array
     * @param $table the HTML table as string
     * @param $names an array of column names
     **/
    public function getArrayFromTable($table){
        // create a dom document
        $dom = new DOMDocument;
        // read the html table to the document
        $dom->loadHTML($table);
        
        // get the individual rows by splitting doc at <tr>
        $rows = $dom->getElementsByTagname('tr');
        
        // new array before foreach
        $return = array();
        
        foreach($rows as $row){
            $array = array();
            foreach($row->childNodes as $key=>$field){
                $item = trim($field->textContent);
                if(!empty($item)){
                    $array[] = $item;
                }
            }
            $return[] = $array;
        }
        return array_filter($return);
    }
    
    public function getSimpleXMLFromTable($table){
        // create a dom document
        $dom = new DOMDocument;
        // read the html table to the document
        $dom->loadHTML($table);
        
        // get the individual rows by splitting doc at <tr>
        $rows = $dom->getElementsByTagname('tr');
        
        // new array before foreach
        $return = array();
        
        foreach($rows as $row){
            $fields = simplexml_import_dom($row);
            $return[] = $fields;
        }
        return $return;
    }            
    
    /**
     * Performs a GET request to YouHosting.
     * 
     * @param $url the url to get
     * @return the HTML doc
     **/
    public function get($url){        
	    $curl_handle = curl_init ($url);
	    curl_setopt ($curl_handle, CURLOPT_COOKIEFILE, $this->cookie);
	    curl_setopt ($curl_handle, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec ($curl_handle);
        return $output;
    }
    
    public function getHeaders($url){
        $curl_handle = curl_init ($url);
	    curl_setopt ($curl_handle, CURLOPT_COOKIEFILE, $this->cookie);
	    curl_setopt ($curl_handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt ($curl_handle, CURLOPT_HEADER, TRUE);
        $output = curl_exec ($curl_handle);
        return $output;
    }
    
    /**
     * Performs a POST request to YouHosting
     * 
     * @param $url the url to post data to
     * @param $postData an array of post data to submit to the page
     * @return the returned HTML page
     **/
    public function post($url, $postData){
		//let's create a user
	    $curl_handle = curl_init($url);
	    curl_setopt ($curl_handle, CURLOPT_COOKIEFILE, $this->cookie);
        curl_setopt ($curl_handle, CURLOPT_RETURNTRANSFER, true);
        $post_array = $postData;
	    curl_setopt ($curl_handle, CURLOPT_POSTFIELDS, $post_array);
        $output = curl_exec ($curl_handle);
        return $output;
    } 
    
}
