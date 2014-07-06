<?php
/*
 * changePassword.php
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

if(!empty($_POST['email']) && !empty($_POST['password'])){
    $client = new Client($connector);
    $result = "Password changes succesfully<br>";
    try {
        $client->linkEmail($_POST['email']);
        $client->changePassword(urldecode($_POST['password']));
    } catch (Exception $e){
        $result = $e->getMessage();
    }
    echo $result;
}
?>
<form action="" method="post" enctype="application/x-www-form-urlencoded">
    <label>Email:</label>
    <input type="email" name="email" />
    <label>Password:</label>
    <input type="password" name="password" />
    <input type="submit" value="Change Password">
</form>
