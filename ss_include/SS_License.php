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
		return trim(substr($result, 10));
	}

	/**
	 * Generates a machine ID
	 *
	 * NOTE: you should overload this function if you want to implement your own method. Especially if you are
	 *       using Serial Sense for a desktop application, in which case you are probably only looking at this
	 *       code for the sake of porting to another programming language.
	 */
	private function machine_id($max_size = 0)
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
		// if you wish to exclude the revision flag (-r) then uncomment the code below:
		$server_input = "uname -mnsp";

		// NOTE: this is not working with shell_exec for some reason >\
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

		$alias = fread($fp, SS_LICENSE_MAXSIZE);
		fclose($fp);

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
	private function save_alias($alias)
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

		fwrite($fp, $alias);
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
		// $iv not necessary with RIJNDAEL_256
//		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
//		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);

		// encryption key can only be 32 bytes (256-bits) -- so crunch it down to size!
		$key = $this->crunch($this->machine_id());
		return mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $alias, MCRYPT_MODE_ECB);
	}

	private function decrypt($alias)
	{
		// $iv not necessary with RIJNDAEL_256
//		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
//		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);

		// encryption key can only be 32 bytes (256-bits) -- so crunch it down to size!
		$key = $this->crunch($this->machine_id());
		$result = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $alias, MCRYPT_MODE_ECB);

		// remove only the null character padding from the right
		return rtrim($result, "\0");
	}

	/**
	 * "Crunches" a string to half of its size. This is intended for ASCII strings that will be used as 
	 * an encryption key with this class's encrypt and decrypt functions.
	 *
	 * @param   integer  maximum allowed size of the crunched string. if the string's length is over $size * 2, 
	 *                   the remaining characters will be truncated.
	 * @return  string   the "crunched" string.
	 */
	private function crunch($str, $max_size = 32)
	{
		$crunch_str = array();

		/**
		 * truncate original string to be a maximum of crunch string's $max_size * 2
		 * "length" is the planned size of the crunched string.
		 */
		if (strlen($str) > $max_size * 2)
		{
			$str = substr($str, 0, $max_size * 2);
			$length = $max_size;
		}
		else
		{
			$length = (int)(strlen($str) / 2 + 0.5);
		}
		for ($i = 0; $i < $length; $i++)
		{
			// fetch the first (a) and second (b) character pair ascii values
			$a = isset($str[$i * 2])     ? ord($str[$i * 2])     : 0;
			$b = isset($str[$i * 2 + 1]) ? ord($str[$i * 2 + 1]) : 0;
			$crunch_str[$i] = chr($a + $b);
		}
		return implode('', $crunch_str);
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