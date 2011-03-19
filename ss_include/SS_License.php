<?php

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
			return null;
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
	 * @return  string  the license alias stored locally. a null string is returned if the alias is not
	 *                  available.
	 */
	private function alias()
	{
		$fp = fopen(SS_LICENSE_FILE, 'r');
		if ( ! $fp)
			return null;

		$alias = trim(fgets($fp));
		fclose($fp);

		// check that this license is valid. $extra is just a checksome integer.
		// if ( ! is_numeric($extra))
		// 	return null;

		// calculate our alias checksum
		// $checksum = 0;
		// for ($i = 0; $i < strlen($alias); $i++)
		// {
		// 	$checksum += ord($alias[$i]);
		// }

		// if ($checksum == $extra)
		// 	return $alias;
		// else
		// 	return null;

		return $this->decrypt($alias);
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
		// if alias is not valid, then create a blank license entry
		if ( ! $this->valid_code($alias))
		{
			$fp = fopen(SS_LICENSE_FILE, 'w');
			if ( ! $fp)
			{
				throw new Exception('Unable to create or write to license file: "'.SS_LICENSE_FILE.'"');
				return false;
			}

			fwrite($fp, "\n");
			fclose($fp);
			return false;
		}

		$alias = $this->encrypt($alias);

		$fp = fopen(SS_LICENSE_FILE, 'w');
		if ( ! $fp)
			return false;

		fwrite($fp, $alias."\n");
		fclose($fp);
		return true;
	}

	/**
	 * PRIVATE FUNCTION. Do not allow outside developers (hackers) to mess with stored encryption strings :)
	 *
	 * These functions are mainly used to encrypt and decrypt the alias before writing to file. It is very important 
	 * that you do not use the same encryption/decryption methods as used here!!!
	 *
	 * In fact, considering this licensing template is available publicly, it is better that you completely change how you save the 
	 * user's alias in general. While end users are not at risk of someone else activating their licensed product, you--the developer--
	 * risk this alias key being used by another unauthorized user ONLY IF you are not using strict booting terms.
	 *
	 * By "strict booting terms", I mean using the SS_License class's method active() to determine if the copy is active. A less strict--
	 * and less secure--method at boot time is to check the class method locally_active(). 
	 *
	 * This may be considered un-needed; however, it could also serve to let the licensing module know
	 * if someone is attempting to alter the stored license alias. at most, this extra check sum helps
	 * hackers waste their time trying to get an alias to work for product authentication purposes :)
	 */
	private function encrypt($alias)
	{
		return $alias;
	}

	private function decrypt($alias)
	{
		return $alias;
	}

	/**
	 * determines if this software copy was activated
	 */
	public function active()
	{
		// if there is no alias present, this software copy is not active
		$alias = $this->alias();
		if ( ! $this->valid_code($alias))
			return false;

		$this->params['mach'] = $this->machine_id();
		$this->params['code'] = $alias;

		return ($this->call_api('auth') == '1');
	}

	/**
	 * a quick check to determine if the software had been activated.
	 */
	public function locally_active()
	{
		$alias = $this->alias();
		return $this->valid_code($alias);
	}

	/**
	 * Performs tasks needed to activate license.
	 *
	 * @param  bool  did the license activation code successfully activate?
	 */
	public function activate($code)
	{
		if ( ! $this->valid_code($code))
			return false;

		$this->params['code'] = $code;
		$this->params['mach'] = $this->machine_id();
		$alias = $this->call_api('activate');
		if ($this->valid_code($alias))
		{
			// extract and save the alias to file
			$this->save_alias($alias);
			return true;
		}
		else
			return false;
	}

	/**
	 * Deactivates local copy.
	 * @return  bool  whether deactivation was successful or not.
	 */
	public function deactivate()
	{
		$this->params['code'] = $this->alias();
		$this->params['mach'] = $this->machine_id();
		$result = $this->call_api('deactivate');

		// if deactivation was successful then also deactive this copy locally.
		if ($result == 'S')
		{
			$this->save_alias(null);
		}

		return $result == 'S';
	}

}	// end class SS_License

?>