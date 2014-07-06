<?php
/*
 * suspendByEmail.php
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
 
require("./loader.php");

// configuration stuff
$blockedDomains = array("yandex.ru", "yandex.kz", "yandex.by", "ya.ru", "yandex.com", "yandex.ua", "narod.ru"); // the list of blocked domains
$from = "2014-07-01"; // the starting date to look at
$to = ""; // the ending date to look at

function initializeDatabase($db){
	$query = "CREATE TABLE emails (id int PRIMARY KEY)";
	try {
		$stmt = $db->prepare($query);
		$result = $stmt->execute();
	} catch (PDOException $e){
		die("Database Error: ".$e->getMessage());
	}
	
	$query = "CREATE TABLE settings (setting varchar(255) PRIMARY KEY, value varchar(255))";
	try {
		$stmt = $db->prepare($query);
		$result = $stmt->execute();
	} catch (PDOException $e){
		die("Database Error: ".$e->getMessage());
	}
	
	$query = "INSERT INTO settings VALUES ('page', '1')";
	try {
		$stmt = $db->prepare($query);
		$result = $stmt->execute();
	} catch (PDOException $e){
		die("Database Error: ".$e->getMessage());
	}
}

if(empty($_REQUEST['action'])){
	$query = "SELECT value FROM settings WHERE setting = 'page'";
	
	try{
		$stmt = $db->prepare($query);
		$result = $stmt->execute();
	} catch(PDOException $e){
		initializeDatabase($db);
	}
	
	echo "Welcome to the suspension script. This script will suspend all clients and accounts who signed up between the dates you provided and using an email address under the domains you listed.<br>If the script dies because of the script execution timeout, simply hit F5 to restart the script and it will continue where it left off.<br>";
	echo '<form action="" method="get"><input type="hidden" name="action" value="importClients"><input type="submit" value="Start"></form>';
	die();
}

if($_REQUEST['action'] == "importClients"){
    set_time_limit(0);
    while(true){
        $query = "SELECT value FROM settings WHERE setting = 'page'";
        
        try{
            $stmt = $db->prepare($query);
            $stmt->execute();
        } catch(PDOException $e){
            die("Database Error: ".$e->getMessage());
        }
        
        $row = $stmt->fetch();
        $page = $row['value'];
        
        $output = $connector->get("http://www.youhosting.com/en/client/manage/page/".$page."?email=&ip=&name=&domain=&from=".$from."&username=&status=any&to=".$to."&id=&submit=Search&pager_controller=client&pager_action=manage&is_list=0");
        
        $output = $connector->getBetween($output,"</thead>

<tbody>","</tbody>");
            
        $xml = $connector->getSimpleXMLFromTable($output);
        
        $array = array();
        
        foreach($xml as $row){
            foreach($row->children() as $field){
                foreach($field->children() as $a){
                    foreach($a->attributes() as $attribute){
                        if(!empty($attribute)){
                            $splode = explode("/id/",$attribute);
                            if(sizeof($splode)==2){
                                if(!in_array($splode[1],$array)){
                                    $array[] = $splode[1];
                                }
                            }
                        }
                    }
                }
            }
        }
        
        unset($array[0]);
        
        if(sizeof($array)==0){
            break;
        }
        
        foreach($array as $id){
            $query = "INSERT INTO emails (id) VALUES (:id)";
            $queryParams = array(':id'=>$id);
            
            try{
                $stmt = $db->prepare($query);
                $stmt->execute($queryParams);
            } catch(PDOException $e){
                echo("Database Error: ".$e->getMessage()."<br>\n");
            }
        }
        
        $query = "UPDATE settings SET value=:value WHERE setting='page'";
        $queryParams = array(':value'=> $page+1);
	
        try{
            $stmt = $db->prepare($query);
            $result = $stmt->execute($queryParams);
        } catch(PDOException $e){
            die("Database Error: ".$e->getMessage());
        }
    }
    
    echo "Clients Imported<br>";
	echo "We now have a list of client ID's, and with that we can fetch the e-mail addresses and check them. Depending on the number of accounts and the speed of YouHosting's server, this can take a long time to complete. If you hit the script execution timeout, simply refresh the page (and resubmit content if asked) and the script should pick up where it left off.<br>";
	echo '<form action="" method="get"><input type="hidden" name="action" value="suspendClients"><input type="submit" value="Suspend"></form>';
}

if($_REQUEST['action'] == "suspendClients"){	
	$query = "SELECT id FROM emails";
	
	try{
		$stmt = $db->prepare($query);
		$result = $stmt->execute();
	} catch(PDOException $e){
		die("Database Error: ".$e->getMessage());
	}
	
	$array = $stmt->fetchAll();
    
    foreach($array as $row){
        $id = $row['id'];
        $client = new Client($connector);
        $client->linkId($id);
        
        $email = $client->getEmail();
        
        $emailParts = explode("@", $email);
        
        if(!empty($emailParts[1]) && in_array($emailParts[1],$blockedDomains)){
            $client->suspend(1,1,'abuse','Spam');
        }
        
        $query = "DELETE FROM emails WHERE id=:id";
		$queryParams = array(':id' => $id);
		
		try{
			$stmt = $db->prepare($query);
			$result = $stmt->execute($queryParams);
		} catch(PDOException $e){
			die("Database Error: ".$e->getMessage());
		}
    }
	
    echo "Clients suspended<br>";
	echo '<form action="" method="get"><input type="hidden" name="action" value="cleanup"><input type="submit" value="Clean Up"></form>';
}

if($_REQUEST['action'] == "cleanup"){
	$query = "DROP TABLE `emails`, `settings`";
	
	try{
		$stmt = $db->prepare($query);
		$result = $stmt->execute();
	} catch(PDOException $e){
		die("Database Error: ".$e->getMessage());
	}
	
	echo 'Clean up complete. Thank you for using this script.';
}
