<?php
/**
 * Serial Sense example Bootloader
 * http://www.serialsense.com
 *
 * This module is meant for you to include, or copy-paste, into your company's distributed PHP application, 
 * plug-in, module, etc. Upon deploying your software master to paying customer, it is strongly recommended
 * that you obfuscate or compile all PHP code. For integration with GNU licensed frameworks, such as WordPress,
 * the GNU licensed code should not be encrypted; however, this licensing module and your application's or 
 * plug-in's core logic should absolutely be obfuscated and encrypted...unless you want your customers with 
 * PHP experience to bypass Serial Sense's network licensing enforcement.
 *
 * For more information, visit our resource hub at:
 * http://www.serialsense.com/blog
 */

require_once('ss_include/config.php');
require_once('ss_include/SS_License.php');

/**
 * This defined variable will allow any pages under the ss_pages directory to be read.
 */
define('SS_BOOT', '');

/**
 * Obviously: Instance of our license
 */
$license = new SS_License;

/**
 * Process GET actions
 */
if ($_GET)
{
	if (isset($_GET['action']))
	{
		$action = $_GET['action'];

		// deactivate local copy
		if ($action == 'deactivate')
		{
			$license->deactivate();
		}
	}
}

/**
 * POST PROCESSOR
 * The meat of this template's form processing.
 *
 * @return  bool  was a display page rendered?
 */
function process_post()
{
	global $license;

	if ($_POST)
	{
		// did user press the 'activate' button?
		if (isset($_POST['activate']))
		{
			if ( ! ($alias = $license->activate($_POST['activation_code'])))
			{
				// set a POST error to be included in the page display
				$_POST['error'] = array(
					'title'   => 'Invalid Activation Code',
					'message' => 'Please include all hyphens in your activation code.'
					);
			}
			/**
			 * However, if activation does not fail, then SS_License active() or locally_active() (below) will trigger 
			 * this web application as active :D
			 */			
		}
		else if (isset($_POST['forgot']))
		{
			require_once('ss_pages/resent_code.php');
			exit;
		}
	}
}

/**
 * AND LAST...
 *
 * Main Application:
 *
 */

process_post();

/**
 * $license->active() will call the Serial Sense API to check if this machine is active. For a quick active check,  
 * locally_active() will check local variables to determine if this app is authentic. This is much less secure; 
 * however, it proves very useful with a less strict licensing enforcement system.
 */
//if ($license->locally_active()):
if ($license->active()): ?>

	<? require_once('ss_pages/app_status.php'); ?>

<?php else: ?>

	<? require_once('ss_pages/ask_activation.php'); ?>
	
<?php endif; ?>
