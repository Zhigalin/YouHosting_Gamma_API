<?php
/*
 * modifyBalance.php
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

$email = "test@example.com";
$amount = -15;
$description = "Test transaction";
$gateway = "None";

try{
    $client = new Client($connector); // create a blank client
    $client->linkEmail($email); // link the client using the email address
    $client->modifyBalance($amount, $description); // modify the balance of the client. for parameters - see the modifyBalance method in the client.class.php file
} catch (Exception $e){
    die($e->getMessage()); // output the error message provided by YouHosting
}

echo "New balance: ".$client->getBalance();

$client->coverInvoices(123456); // if the invoice is called for example YHC123456, you need to input the number here

echo "<br>Balance after invoice: ".$client->getBalance();

