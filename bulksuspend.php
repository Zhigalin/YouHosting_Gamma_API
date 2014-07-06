<?php
/*
 * bulksuspend.php
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

function initializeDatabase($db){
	$query = "CREATE TABLE domains (domain varchar(255) PRIMARY KEY)";
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
	
	$query = "INSERT INTO settings VALUES ('action', 'database initialized')";
	try {
		$stmt = $db->prepare($query);
		$result = $stmt->execute();
	} catch (PDOException $e){
		die("Database Error: ".$e->getMessage());
	}
	
	$query = "INSERT INTO settings VALUES ('note', '')";
	try {
		$stmt = $db->prepare($query);
		$result = $stmt->execute();
	} catch (PDOException $e){
		die("Database Error: ".$e->getMessage());
	}
	
}

if(empty($_POST['action'])){
	$file = fopen('./domains.txt','r');
	
	$query = "SELECT 1 FROM domains LIMIT 1";
	
	try{
		$stmt = $db->prepare($query);
		$result = $stmt->execute();
	} catch(PDOException $e){
		initializeDatabase($db);
	}
	
	echo "Welcome to the bulk suspend script. This script will read domains from a text file and suspend the owners.<br>";
	if(!$file){
		echo "We could not find the file. Please create a file called domains.txt in the directory of this file and insert all domains names with each domain on a new line. Then refresh the page.";
		die();
	}
	echo "Please insert a suspension message below:<br>";
	echo '<form action="" method="post"><input type="hidden" name="action" value="importDomains"><input type="text" name="reason"><input type="submit" value="Import Domains"></form>';
	die();
}

if($_POST['action'] == "importDomains"){
	$file = fopen('./domains.txt','r');
	
	$query = "UPDATE settings SET value=:value WHERE setting='note'";
	$queryParams = array(':value'=>$_POST['reason']);
	
	try{
		$stmt = $db->prepare($query);
		$result = $stmt->execute($queryParams);
	} catch(PDOException $e){
		die("Database Error: ".$e->getMessage());
	}
	
	while($line = fgets($file)){
		$line = preg_replace( "/\r|\n/", "", $line);
		$query = "INSERT INTO domains VALUES (:domain)";
		$queryParams = array(':domain'=>$line);
		
		try{
			$stmt = $db->prepare($query);
			$result = $stmt->execute($queryParams);
		} catch(PDOException $e){
			die("Database Error: ".$e->getMessage());
		}
	}
	
	$query = "UPDATE settings SET value=:value WHERE setting='action'";
	$queryParams = array(':value'=>'suspending');
	
	try{
		$stmt = $db->prepare($query);
		$result = $stmt->execute($queryParams);
	} catch(PDOException $e){
		die("Database Error: ".$e->getMessage());
	}
	
	echo "Domains Imported<br>";
	echo "Next up, we are going to suspend clients. Depending on the number of accounts and the speed of YouHosting's server, this can take a long time to complete. If you hit the script execution timeout, simply refresh the page (and resubmit content if asked) and the script should pick up where it left off.<br>";
	echo '<form action="" method="post"><input type="hidden" name="action" value="suspendDomains"><input type="submit" value="Suspend Domains"></form>';
}

if($_POST['action'] == "suspendDomains"){
	set_time_limit(0);
	
	$query = "SELECT value FROM settings WHERE setting='action'";
	
	try{
		$stmt = $db->prepare($query);
		$result = $stmt->execute();
	} catch(PDOException $e){
		die("Database Error: ".$e->getMessage());
	}
	
	$row = $stmt->fetch();
	
	if($row['value'] != 'completed'){		
		if($row['value'] != 'suspending'){
			die("Unknown status: ".$row['action']);
		}
		
		$query = "SELECT value FROM settings WHERE setting='note'";
		
		try{
			$stmt = $db->prepare($query);
			$result = $stmt->execute();
		} catch(PDOException $e){
			die("Database Error: ".$e->getMessage());
		}
		
		$row = $stmt->fetch();
		$reason = $row['value'];
		
		$query = "SELECT domain FROM domains";
		
		try{
			$stmt = $db->prepare($query);
			$result = $stmt->execute();
		} catch(PDOException $e){
			die("Database Error: ".$e->getMessage());
		}
		
		$domains = $stmt->fetchAll();
		
		$errorDomains = array();
		
		foreach($domains as $domainRow){
			$domain = $domainRow['domain'];
			
			$account = new Account($connector);
			try{
				$account->linkDomain($domain);
			} catch(Exception $e){
			};
			$client = $account->getClient();
			
			try{
				$client->suspend(1,1,'abuse',$reason);
			} catch (Exception $e){
				$errorDomains[]=$domain;
			}
			
			$query = "DELETE FROM domains WHERE domain='".$domain."'";
				
			try{
				$stmt = $db->prepare($query);
				$result = $stmt->execute();
			} catch(PDOException $e){
				die("Database Error: ".$e->getMessage());
			}
		}
		
		$query = "UPDATE settings SET value=:value WHERE setting='action'";
		$queryParams = array(':value'=>'completed');
		
		try{
			$stmt = $db->prepare($query);
			$result = $stmt->execute($queryParams);
		} catch(PDOException $e){
			die("Database Error: ".$e->getMessage());
		}
	}
	
	if(count($errorDomains)==0){
		echo "Script completed. There were no errors.<br>";
	} else {
		echo "Script completed. Some domains failed:<br>";
		foreach($errorDomains as $errorDomain){
			echo $errorDomain."<br>";
		}
	}
	
	echo '<form action="" method="post"><input type="hidden" name="action" value="cleanup"><input type="submit" value="Clean Up"></form>';
}

if($_POST['action'] == "cleanup"){
	$query = "DROP TABLE `domains`, `settings`";
	
	try{
		$stmt = $db->prepare($query);
		$result = $stmt->execute();
	} catch(PDOException $e){
		die("Database Error: ".$e->getMessage());
	}
	
	echo 'Clean up complete';
}
