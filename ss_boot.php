<?php
/**
 * Serial Sense example Bootloader
 * http://www.serialsense.com
 *
 * This module is meant for you to include, or copy-paste, into your company's distributed PHP application, 
 * plug-in, module, etc. Upon deploying your software master to paying customer, it is strongly recommended
 * that you obfuscate or compile all PHP code. For integration with GNU licensed frameworks, such as WordPress,
 * the GNU licensed code should not be obfuscated; however, this licensing module and your application's or 
 * plug-in's code should be.
 *
 * For more information, visit our resource hub at:
 * http://www.serialsense.com/blog
 */

/**
 * Comment this out if you'd like PHP to be silent if it fails.
 */
ini_set('display_errors', 1);
error_reporting(E_ALL | E_STRICT);

/**
 * License File -- for storing the license alias. This is a secure method to use because the user must 
 * activate their application (using Serial Sense) in order to get the license alias. However, this file 
 * mainly exists for example purposes. Consider storying the license alias in a discreet location.
 *
 * NOTES:
 *    - overload the SS_License method alias() to change how you want to retrieve the alias.
 *    - also overload SS_License method save_alias to change how you will save the alias.
 *    - when storing via file, the file's permissions should be set so everyone can write, ie: chmod 777 file
 */
define('SS_LICENSE_FILE', 'li.sense');

/**
 * API settings
 *
 * If you have a Serial Sense account with access to our premium, SSL secured servers, change the protocol and
 * link location below to match our premium server. (Login to find this information in Your Account settings.)
 *
 * If you are running Serial Sense on your own server, then change this value to the server plug-in location.
 */
define('SS_API_LOCATION', 'http://www.serialsense.com/api/');

/**
 * Input your developer API key here. Keep your developer signature private, and do NOT use your developer 
 * signature in any code deployed to your customers!
 */
//define('SS_API_KEY', 'replace_with_your_developer_key');
define('SS_API_KEY', '4c8a7eb2e48bb6.56332309');

class SS_License {
	/**
	 * $params is an array of API call parameters, where the array key is the parameter name, and array value
	 * is the value of the parameter to set.
	 */
	public $params;

	/**
	 * Sends an API call to the Serial Sense server.
	 *
	 * @param   string  the api call name to make
	 * @return  string  returns the api call result
	 */
	private function call_api($call_name)
	{
		$this->params['apikey'] = SS_API_KEY;

		// get the API call result
		$curl = curl_init(SS_API_LOCATION.$call_name);
		curl_setopt($curl, CURLOPT_POST,              true);
		curl_setopt($curl, CURLOPT_POSTFIELDS,        $this->params);
//		curl_setopt($curl, CURLOPT_CONNECTIONTIMEOUT, 10);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,    1);
		$result = curl_exec($curl);
		curl_close($curl);

		/**
		 * make sure the result has a valid license header. if your API call is setup incorrectly, then the
		 * result will begin with a regular webpage header "<html>", "<!DOCTYPE html>", etc.
		 */
		if (substr($result, 0, 9) != '<license>')
		{
			throw new Exception("Invalid license response header. Check that your SS_API_LOCATION is "
				. "set to the proper server address.");
			return NULL;
		}

		// return everything after "<license>\n"
//		$result = trim(substr($result, 10));
		return trim(substr($result, 10));
	}

	/**
	 * Generates a machine ID
	 *
	 * NOTE: you should overload this function if you want to implement your own method. Especially if you are
	 *       using Serial Sense for a desktop application, in which case you are probably only looking at this
	 *       code for the sake of porting to another programming language.
	 */
	private function machine_id()
	{
		$linux_input  = "ifconfig | grep -i hwaddr | awk '{print $1$5}' | sed 's/://g' | xargs echo | "
		              . "sed 's/ //g'";
		$macosx_input = "ifconfig | egrep -i flags\|ether | awk '{ FS = \"flags\" ; printf(\"%s\", $1); }' | "
		              . "perl -p -e's/\W|ether|://g'";
		/**
		 * If your deployed application is running on your customer's webserver, chances are that customer
		 * is not allowed to execute the ifconfig command. In these situations, the "uname" command is helpful
		 * for generating a unique-enough machine ID for any unix system. As long as your user stays on the 
		 * same web server, it is highly unlikely that the $server_input will generate a different output than
		 * what was originally generated. This is of course possible, so program SS_License::authorize()
		 * function responsibly, and take advantage of the sample code within this module for handling lost or
		 * forgotten license activation codes.
		 *
		 * For more information on the "uname" command, view the man pages by typing the command "man uname"
		 * in your unix shell or Mac OS X terminal.
		 */
		$server_input = "uname -mnrsp";

		// NOTE: this does not work with shell_exec for some reason >\
//		$apple_input  = "system_profiler | grep -i \"Serial Number (system):\" | awk '{print $4}'";

		return shell_exec($server_input);
	}

	/**
	 * Grabs the license alias (stored locally by default). NOTE: you should overload this function if you 
	 * wish to store the license alias differently--database, different file location, or whatever you can 
	 * dream up.
	 *
	 * @return  string  the license alias stored locally. a NULL string is returned if the alias is not
	 *                  available.
	 */
	private function alias()
	{
		$fp = fopen(SS_LICENSE_FILE, 'r');
		if ( ! $fp)
			return NULL;

		$alias = fgets($fp);
		$extra = fgets($fp);
		fclose($fp);

		// check that this license is valid. $extra is just a checksome integer.
		if ( ! is_integer($extra))
			return NULL;

		// calculate our alias checksum
		$checksum = 0;
		for ($i = 0; $i < strlen($alias); $i++)
		{
			$checksum += ord($alias[$i]);
		}
		foreach ($alias as $letter)
		{
			$checksum += int($letter);
//			echo "letter is: $letter<br>";
		}

		if ($checksum == $extra)
			return $alias;
		else
			return NULL;
	}

	/**
	 * Basic validation for a license activate code or license alias. Serial Sense license codes and aliases 
	 * will always be between 13 and 15 characters. Between 1 and 3 of these characters will be a hyphen (-)
	 * character.
	 *
	 * NOTE: users must ALWAYS include the hyphens! If they omit the hyphens, then the code will ALWAYS be 
	 *       only 12 characters in length.
	 *
	 * @param   string  the license code alias in question
	 * @return  bool    is the code string length between 13 and 15 characters long?
	 */
	private function valid_code($code)
	{
		return (strlen($code) <= 15 && strlen($code) >= 13);
	}

	/**
	 * Saves the license alias locally. This is a secure method for the user because if a hacker finds out the
	 * alias code, then the alias will still be useless to the hacker. Why? Because aliases cannot be used to
	 * activate a machine like the license activation code.
	 *
	 * @param   string  the license code alias to remember
	 * @return  bool    was this function able to save the alias?
	 */
	public function save_alias($alias)
	{
		if ( ! $this->valid_code($alias))
			return false;

		/**
		 * generate checksum. this is un-needed; however, it could also serve to let the licensing module know
		 * if someone is attempting to alter the stored license alias. at most, this extra check sum helps
		 * hackers waste their time trying to get an alias to work for product authentication purposes :)
		 */
		$checksum = 0;
		for ($i = 0; $i < strlen($alias); $i++)
		{
			$checksum += ord($alias[$i]);
			echo "letter is: {$alias[$i]}<br>";
		}
//		foreach ($alias as $letter)
//		{
//			$checksum += int($letter);
//			echo "letter is: $letter<br>";
//		}
		echo "checksum is: $checksum<br>";

		$fp = fopen(SS_LICENSE_FILE, 'w');
		if ( ! $fp)
			return false;

		fwrite($fp, $alias."\n");
		fwrite($fp, $checksum."\n");
		fclose($fp);
		return true;
	}

	/**
	 * determines if this software copy was activated
	 */
	public function active()
	{
		if ( ! $this->alias())
		{
			return false;
		}

//		$this->call = 'auth';
		$this->params['mach'] = $this->machine_id();
		$this->params['code'] = $this->alias();
		if ($result = $this->api('auth'))
		{
			echo "result = $result<br>";
		}
		else
		{
			var_dump($result);
		}
	}

	/**
	 * Performs tasks needed to activate license.
	 *
	 * @param  bool  did the license activation code successfully activate?
	 */
	public function activate($code)
	{
		echo "inside activate<br>";

//		echo "result from valid_code($code): ";
//		var_dump($this->valid_code($code));
//		echo '<br>';
		if ( ! $this->valid_code($code))
		{
			echo "wtf? returning false??<br>";
			return false;
		}

		echo "so far, code is valid<br>";

		$this->params['code'] = $code;
		$this->params['mach'] = $this->machine_id();
		$alias = $this->call_api('activate');
		if ($this->valid_code($alias))
		{
			echo "i gunna tell yew what it is!<br>";
			echo "result = '$alias'<br>";
			// extract and save the alias to file
			$this->save_alias($alias);
			return true;
		}
		else
			return false;
	}

}	// end class SS_License

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