<?php

	require('./loader.php');
	
	foreach($_FILES as $name => $file) {
		$f = fopen($file["tmp_name"], "r");
		${$name} = fread($f, filesize($file["tmp_name"]));
		fclose($f);
	}
	
	$postdata =
		array(
			'submit' => 'Save',
			'login' => $login,
			'profile' => $profile,
			'default' => $default,
			'public' => $public,
			'inner' => $inner
		)
	;
	$output = $connector->post("http://www.youhosting.com/en/themes/layouts/theme/xx", $postdata);
	if(!empty($output)){
		throw new Exception('Error while changing theme\'s HTML');
	}
	
	$postdata =
		array(
			'submit' => 'Save',
			'css' => $css
		)
	;
	$output = $connector->post("http://www.youhosting.com/en/themes/advanced-css/theme/xx", $postdata);
	if(!empty($output)){
		throw new Exception('Error while changing theme\'s CSS');
	}
	
	echo "Success"
?>