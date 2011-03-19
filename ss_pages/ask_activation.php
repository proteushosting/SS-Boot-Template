<?php defined('SS_BOOT') or die('This script was written to run under the Serial Sense boot template. <a href="../ss_boot.php">Click here</a>'); ?>

<html>
<head>
	<link rel="stylesheet" href="ss_pages/css/basic.css" type="text/css" media="screen" title="Serial Sense" charset="utf-8">
</head>
<body>

<h1>Activate Your Copy Today!</h1>

<hr>
<br>

<form method="post" action="ss_boot.php">

	<blockquote>
		<? if (isset($_POST['error'])): ?>
			<p>
				<span class="error-title"><?=$_POST['error']['title']?>:</span> 
				<span class="error-message"><?=$_POST['error']['message']?></span>
			</p>
		<? endif; ?>
		<p>
			<strong>License Activation Code:</strong> 
			<input type="text" name="activation_code" maxlength="15"> 
			<input type="submit" name="activate" value="Activate">
		</p>

			<br>

		<p class="light">
			Forgot your license code? No problem, just input your email below and your activation code will be re-emailed to you.
		</p>
		<p class="light">
			Your Email: 
			<input type="text" name="email"> 
			<input type="submit" name="forgot" value="Re-send Code">
		</p>
	</blockquote>

</form>
</body>
</html>
