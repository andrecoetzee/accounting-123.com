<?
/**
 * Generally used functions/constants related to files/directories
 *
 * @package Cubit
 * @subpackage Filesystem
 */

if (!defined("FILE_LIB")) {
	define("FILE_LIB", true);

global $CFS_filenames; $CFS_filenames = array();
global $CFS_resources; $CFS_resources = array();
global $CFS_counter; $CFS_counter = 0;

class cfs_of {
	/**
	 * adds a file to open file list
	 *
	 * @param resource $r file resource
	 * @param string $n file name
	 */
	static function add($r, $n) {
		global $CFS_filenames, $CFS_resources, $CFS_counter;

		$CFS_filenames[$CFS_counter] = $n;
		$CFS_resources[$CFS_counter] = $r;

		++$CFS_counter;
	}

	/**
	 * removes resource from list
	 *
	 * @param resource $r
	 */
	static function rem($r) {
		global $CFS_filenames, $CFS_resources, $CFS_counter;

		$c = array_search($r, $CFS_resources);

		if ($c !== false) {
			unset($CFS_resources[$c]);
			unset($CFS_filenames[$c]);
		}
	}

	/**
	 * checks for resource and returns filename
	 *
	 * @param resource $r
	 * @return string
	 */
	static function getname($r) {
		global $CFS_filenames, $CFS_resources, $CFS_counter;

		$c = array_search($r, $CFS_resources);

		if ($c === false) {
			return false;
		}

		return $CFS_filenames[$c];
	}

	/**
	 * checks for filename and returns resource
	 *
	 * @param string $f
	 * @return string
	 */
	static function getresource($f) {
		global $CFS_filenames, $CFS_resources, $CFS_counter;

		$c = array_search($f, $CFS_filenames);

		if ($c === false) {
			return false;
		}

		return $CFS_resources[$c];
	}
}

/**
 * general file system handling functions
 *
 */
class cfs {
	/**
	 * error handling
	 *
	 * @param string $function calling function
	 * @param string $err error string
	 * @return bool false
	 */
	static function fileError($function, $err) {
		errDie("$err", true);
		return false;
	}

	/**
	 * checks if enough permissions to, then makes a directory
	 *
	 * will create whole directory structure if needed. iow ./p/a/q
	 * will create "a" AND "q" if "p" exists by "a"/"q" doesn't.
	 * NOTE: if not a full path DOCROOT will be the parent directory.
	 *
	 * @see DOCROOT
	 * @param string $name directory name
	 * @param bool $fullpath full path to file?
	 * @return bool success
	 */
	static function mkdir($name, $fullpath = false) {
		$o = "$name/DUMMY"; // extra path to hack the loop to create last dir
		$p = 0;

		if ($fullpath) {
			$parentdir = DOCROOT . "/";
		} else {
			$parentdir = "";
		}

		while (($p = strpos($o, "/", $p + 1)) !== false) {
			$t = $parentdir . substr($o, 0, $p);
			if (!file_exists($t)) {
				if (@mkdir($t, 0755) === false) {
					return self::fileError("mkdir", "Unable to create directory '$t'.");
				}
			}
		}

		return true;
	}

	/**
	 * opens file and returns in array
	 *
	 * NOTE: if not a full path DOCROOT will be the parent directory
	 *
	 * @see DOCROOT
	 * @param string $name filename
	 * @param bool $fullpath full path to file?
	 * @return array
	 */
	static function file($name, $fullpath = false) {
		if ($fullpath === false) {
			$name = DOCROOT."/$name";
		}
		if (($a = @file($name)) === false) {
			return self::fileError("file", "Unable to open file '$name'.");
		}

		return $a;
	}

	/**
	 * returns the contents of the file binary safe
	 *
	 * NOTE: if not a full path DOCROOT will be the parent directory
	 *
	 * @see DOCROOT
	 * @param string $name filename
	 * @param string $context file_get_contents context values
	 * @param bool $fullpath full path of file?
	 * @return string
	 */
	static function get_contents($name, $context = null, $fullpath = false) {
		if ($fullpath === false) {
			$name = DOCROOT."/$name";
		}

		return file_get_contents($name, false, $context);
	}

	/**
	 * saves data to a file binary safe
	 *
	 * NOTE: if not a full path DOCROOT will be the parent directory
	 *
	 * @see DOCROOT
	 * @param string $name filename
	 * @param string $context file_get_contents context values
	 * @param bool $fullpath full path of file?
	 * @return int num bytes written
	 */
	static function put_contents($name, $data, $context = null, $fullpath = false) {
		if ($fullpath === false) {
			$name = DOCROOT."/$name";
		}

		return file_put_contents($name, $data, false, $context);
	}

	/**
	 * opens file and returns stream resource
	 *
	 * NOTE: if not a full path DOCROOT will be the parent directory.
	 * fails cleanly in case of error
	 *
	 * @param string $name filename
	 * @param string $mode (r/w)[+]
	 * @param bool $fullpath
	 * @return int stream resource
	 */
	static function fopen($name, $mode, $fullpath = false) {
		if ($fullpath === false) {
			$name = DOCROOT."/$name";
		}

		if (($r = @fopen($name, $mode)) === false) {
			$mode_desc = $mode[0] == "r" ? "reading" : "writing";
			return self::fileError("fopen", "Error opening '$name' for $mode_desc.");
		}

		cfs_of::add($r, $name);

		return $r;
	}

	/**
	 * returns true when eof
	 *
	 * @param resource $stream
	 * @return bool
	 */
	static function feof($stream) {
		if (cfs_of::getname($stream) === false) {
			return self::fileError("feof", "Invalid stream.");
		}

		return @feof($stream);
	}

	/**
	 * executes fgets.
	 *
	 * returns false on eof
	 *
	 * @param resource $stream
	 * @param int $len length of characters to read (dflt: 4096)
	 * @return string/bool bytes read
	 */
	static function fgets($stream, $len = 4096) {
		if (cfs_of::getname($stream) === false) {
			return self::fileError("fread", "Invalid stream.");
		}

		if (@feof($stream)) {
			return self::fileError("fread",
				"EOF reached for '".cfs_of::getname($stream)."'");
		}

		$r = @fgets($stream, $len);

		if ($r === false) {
			//return self::fileError("fread",
			//	"Error reading from stream for '".cfs_of::getname($stream)."'");
		}

		return $r;
	}

	/**
	 * executes fread.
	 *
	 * returns false on eof
	 *
	 * @param resource $stream
	 * @param int $len length of characters to read (dflt: 4096)
	 * @return string/bool bytes read
	 */
	static function fread($stream, $len = 4096) {
		if (cfs_of::getname($stream) === false) {
			return self::fileError("fread", "Invalid stream.");
		}

		if (@feof($stream)) {
			return self::fileError("fread",
				"EOF reached for '".cfs_of::getname($stream)."'");
		}

		$r = @fread($stream, $len);

		if ($r === false) {
			return self::fileError("fread",
				"Error reading from stream for '".cfs_of::getname($stream)."'");
		}

		return $r;
	}

	/**
	 * executes fwrite
	 *
	 * @param resource $stream
	 * @param string $str
	 * @return int bytes written
	 */
	static function fwrite($stream, $str) {
		if (cfs_of::getname($stream) === false) {
			return self::fileError("fwrite", "Invalid stream.");
		}

		$r = @fwrite($stream, $str);

		if ($r === false) {
			return self::fileError("fwrite",
				"Error writing to stream for '".cfs_of::getname($stream)."'");
		}

		return $r;
	}

	/**
	 * flushes a stream (does not flag error, but does return success)
	 *
	 * @param stream
	 * @return bool
	 */
	static function fflush($stream) {
		if (cfs_of::getname($stream) === false) {
			return self::fileError("fflush", "Invalid stream.");
		}

		return @fflush($stream);
	}

	/**
	 * executes fclose
	 *
	 * @param resource $stream stream resource
	 * $return bool
	 */
	static function fclose($stream) {
		if (cfs_of::getname($stream) === false) {
			return self::fileError("fclose", "Invalid stream.");
		}

		$r = @fclose($stream);

		if ($r === false) {
			return self::fileError("fclose",
				"Error closing stream to '".cfs_of::getname($stream)."'");
		}

		cfs_of::rem($stream);
	}

	/**
	 * removes a file
	 *
	 * NOTE: if not a full path DOCROOT will be the parent directory
	 *
	 * @see DOCROOT
	 * @param string $name filename
	 * @param bool $fullpath full path of file?
	 * @return string
	 */
	static function unlink($name, $fullpath = false) {
		if ($fullpath === false) {
			$name = DOCROOT."/$name";
		}

		return unlink($name);
	}

	/**
	 * creates random temp file and returns full path
	 *
	 * Function is platform safe and uses default Cubit dir if none specified.
	 * NOTE: if not a full path DOCROOT will be the parent directory.
	 * default tempdir in windows is c:\cubit\sessions.
	 * in linux it is /usr/local/cubit/sessions.
	 *
	 * @param string $pfx optional overwrite of prefix (dflt: cubit)
	 * @param string $path optional overwrite of temp dir
	 * @return string filename
	 */
	static function tempnam($pfx = "cubit", $path = false) {
		if ($path === false) {
			if (PLATFORM == "linux") {
				$path = "/usr/local/cubit/sessions";
			} else {
				$path = "c:/cubit/sessions";
			}
		}

		return tempnam($path, $pfx);
	}

	/**
	 * file exists
	 *
	 * NOTE: if not a full path DOCROOT will be the parent directory
	 *
	 * @param string $name filename
	 * @param bool $fullpath
	 * @return bool
	 */
	static function is_file($name, $fullpath = false) {
		if ($fullpath === false) {
			$name = DOCROOT."/$name";
		}

		return is_file($name);
	}

	/**
	 * dir exists
	 *
	 * NOTE: if not a full path DOCROOT will be the parent directory
	 *
	 * @param string $name filename
	 * @param bool $fullpath
	 * @return bool
	 */
	static function is_dir($name, $fullpath = false) {
		if ($fullpath === false) {
			$name = DOCROOT."/$name";
		}

		return is_dir($name);
	}

	/**
	 * file exists and is executable
	 *
	 * NOTE: if not a full path DOCROOT will be the parent directory
	 *
	 * @param string $name filename
	 * @param bool $fullpath
	 * @return bool
	 */
	static function is_executable($name, $fullpath = false) {
		if ($fullpath === false) {
			$name = DOCROOT."/$name";
		}

		return is_executable($name);
	}

	/**
	 * file is symbolic link
	 *
	 * NOTE: if not a full path DOCROOT will be the parent directory
	 *
	 * @param string $name filename
	 * @param bool $fullpath
	 * @return bool
	 */
	static function is_link($name, $fullpath = false) {
		if ($fullpath === false) {
			$name = DOCROOT."/$name";
		}

		return is_link($name);
	}

	/**
	 * file exists and is readable
	 *
	 * NOTE: if not a full path DOCROOT will be the parent directory
	 *
	 * @param string $name filename
	 * @param bool $fullpath
	 * @return bool
	 */
	static function is_readable($name, $fullpath = false) {
		if ($fullpath === false) {
			$name = DOCROOT."/$name";
		}

		return is_readable($name);
	}

	/**
	 * file exists and is writable
	 *
	 * NOTE: if not a full path DOCROOT will be the parent directory
	 *
	 * @param string $name filename
	 * @param bool $fullpath
	 * @return bool
	 */
	static function is_writable($name, $fullpath = false) {
		if ($fullpath === false) {
			$name = DOCROOT."/$name";
		}

		return is_writable($name);
	}

	/**
	 * returns file size
	 *
	 * NOTE: if not a full path DOCROOT will be the parent directory
	 *
	 * @param string $name filename
	 * @param bool $fullpath
	 * @return int
	 */
	static function filesize($name, $fullpath = false) {
		if ($fullpath === false) {
			$name = DOCROOT."/$name";
		}

		return filesize($name);
	}
}

/**
 * general file system handling functions for uploaded files
 */
class ucfs {
	/**
	 * checks if valid upload
	 *
	 * @param string $name fieldname
	 * @return bool
	 */
	static function valid($name) {
		if (!isset($_FILES[$name])
			|| !is_uploaded_file($_FILES[$name]["tmp_name"])) {
			return false;
		}

		return true;
	}

	/**
	 * opens a file for reading/writing
	 *
	 * @param string $name fieldname
	 * @return resource
	 */
	static function fopen($name, $mode = "r") {
		if (!ucfs::valid($name)) {
			return false;
		}

		return cfs::fopen($_FILES[$name]["tmp_name"], $mode, true);
	}

	/**
	 * opens file and returns in array
	 *
	 * @param string $name fieldname
	 * @return array
	 */
	static function file($name) {
		if (!ucfs::valid($name)) {
			return false;
		}

		return cfs::file($_FILES[$name]["tmp_name"], true);
	}

	/**
	 * returns original filename
	 *
	 * @param string $name fieldname
	 * @return array
	 */
	static function fname($name) {
		if (!ucfs::valid($name)) {
			return false;
		}

		return $_FILES[$name]["name"];
	}

	/**
	 * returns temporary upload filename
	 *
	 * @param string $name fieldname
	 * @return array
	 */
	static function ftmpname($name) {
		if (!ucfs::valid($name)) {
			return false;
		}

		return $_FILES[$name]["tmp_name"];
	}

	/**
	 * returns file size
	 *
	 * @param string $name fieldname
	 * @return array
	 */
	static function fsize($name) {
		if (!ucfs::valid($name)) {
			return false;
		}

		return $_FILES[$name]["size"];
	}

	/**
	 * returns error
	 *
	 * @param string $name fieldname
	 * @return array
	 */
	static function ferror($name) {
		switch ($_FILES[$name]["error"]) {
			case UPLOAD_ERR_OK:
				$err = "There is no error, the file uploaded with success.";
				break;
			case UPLOAD_ERR_INI_SIZE:
				$err = "The uploaded file exceeds the max upload size.";
				break;
			case UPLOAD_ERR_FORM_SIZE:
				$err = "The uploaded file exceeds max file size form permits.";
				break;
			case UPLOAD_ERR_PARTIAL:
				$err = "File only partially uploaded.";
				break;
			case UPLOAD_ERR_NO_FILE:
				$err = "No file was uploaded.";
				break;
			case UPLOAD_ERR_NO_TMP_DIR:
				$err = "No temporary folder to upload to.";
				break;
			case UPLOAD_ERR_CANT_WRITE:
				$err = "Failed to write to disk. Check temporary folder permissions.";
				break;
			default:
				$err = "Unknown error.";
				break;
		}

		return $err;
	}

	/**
	 * returns mime type
	 *
	 * @param string $name fieldname
	 * @return array
	 */
	static function ftype($name) {
		if (!ucfs::valid($name)) {
			return false;
		}

		return $_FILES[$name]["type"];
	}
}

} /* LIB END */
?>
