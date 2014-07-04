<?php
/*
 * bulkdelete.php
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

require('./loader.php');

$page = $connector->get("http://www.youhosting.com/en/client-account/manage?domain=&account_id=&username=&email=&account_status=canceled&has_content=any&hostingplan=0&from=&to=&submit=Search");

$page = $connector->getBetween($page,'<table class="colorized mtop">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Domain</th>
                        <th>Status</th>
                        <th>Login</th>
                        <th>Jump To</th>
                    </tr>
                </thead>','<tfoot>');
$dom = new DOMDocument();
$dom->loadHTML($page);
$tables = $dom->getElementsByTagName('tbody');
$rows = $tables->item(0)->getElementsByTagName('tr');

foreach ($rows as $row) {
	$cols = $row->getElementsByTagName('td');
	$domain=$cols->item(1)->nodeValue;
	$account = new Account($connector);
	try{
		$account->linkDomain($domain);
	} catch(Exception $e) {
		echo "Unable to find account: ".$domain;
		continue;
	}
	$account->delete();
}

echo "completed";
