<?php
/**
 * Serial Sense example Bootloader
 * http://www.serialsense.com
 *
 * This module is meant for you to include, or copy-paste, into your company's distributed PHP application, 
 * plug-in, module, etc. Upon deploying your software master to paying customer, it is strongly recommended
 * that you obfuscate or compile all PHP code. For integration with GNU licensed frameworks, such as WordPress,
 * the GNU licensed code should not be obfuscated; however, this licensing module and your application's or 
 * plug-in's code absolutely should be...unless you want your users to easily remove Serial Sense's network
 * licensing enforcement.
 *
 * For more information, visit our resource hub at:
 * http://www.serialsense.com/blog
 */

require_once('ss_include/config.php');
require_once('ss_include/SS_License.php');

/**
 * Obviously: Instance of our license
 */
$license = new SS_License;

/**
 * POST PROCESSOR
 *
 * Process post information
 */
function ProcessPost()
{
	global $license;

	if ($_POST)
	{
		// did user press the 'activate' button?
		if (isset($_POST['activate']))
		{
			echo "about to try activation<br>";
			print_r($_POST);
			echo '<br>';
			if ( ! ($alias = $license->activate($_POST['activation_code'])))
			{
				?>
				<table border="1" cellpadding="25" cellspacing="0">
				<tr><td>
					<font color="#d00"><strong>Invalid Activation Code!</strong></font>
						<br>
					Do not forget: you must include all hyphens in your activation code.
				</td></td>
				</table>
				<?php
				var_dump($alias);
				echo '<br>';
			}
			else
			{
				echo "welp, looks like it worked :)<br>";
			}
			/**
			 * However, if activation does not fail, then SS_License::active() (below) will trigger this 
			 * web application as active :D
			 */			
		}
		else if (isset($_POST['forgot']))
		{
			echo "you forgot your code??<br>";
		}
	}
}

/**
 * AND LAST...
 *
 * Main Application:
 *
 */
?>

<html>
<body>

<?php if ($license->active()): ?>

	<h1>Running Application...</h1>

<?php else: ?>

	<h1>Activate Your Copy Today!</h1>
	
	<hr>

	<?php ProcessPost(); ?>
	
	<form method="post">
	<h2>
		<strong>License Activation Code: </strong>
		<input type="text" name="activation_code" maxlength="15"> 
		<input type="submit" name="activate" value="Activate">
	</h2>

	<p><font color="#777">
		Forgot your license code? No problem, just input your email below and your activation code will be 
		re-emailed to you.
			<br><br>
		Your Email: 
		<input type="text" name="email"> 
		<input type="submit" name="forgot" value="Re-send Code">
	</font></p>

	</form>

<?php endif; ?>

</body>
</html>