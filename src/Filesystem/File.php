<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Filesystem;

use Awf\Container\Container;
use Awf\Container\ContainerAwareInterface;
use Awf\Container\ContainerAwareTrait;

/**
 * The File adapter of the filesystem abstraction layer.
 *
 * This adapter is used for direct filesystem writes, without using (S)FTP
 */
class File implements FilesystemInterface, ContainerAwareInterface
{
	use ContainerAwareTrait;

	/**
	 * Public constructor
	 *
	 * @param   array       $options  Ignored by this class
     * @param   Container   $container  Ignored by this class
	 *
	 * @return  void
	 */
	public function __construct(array $options, ?Container $container = null)
	{
		if ($container !== null)
		{
			$this->setContainer($container);
		}
	}

	/**
	 * Write the contents into the file
	 *
	 * The contents are first written to a temporary file in the same directory, which then replaces the target with a
	 * single rename(). A plain file_put_contents() truncates the target before writing it, so a concurrent request can
	 * read an empty or half-written file. With rename() readers see either the complete old file or the complete new
	 * one. If a temporary file cannot be created or renamed over the target (read-only directory, Windows locking an
	 * open file, ...) we fall back to writing the target directly.
	 *
	 * @param   string  $fileName  The full path to the file
	 * @param   string  $contents  The contents to write to the file
	 *
	 * @return  boolean  True on success
	 */
	public function write($fileName, $contents)
	{
		if ($this->writeAtomically($fileName, $contents))
		{
			return true;
		}

		$bytes = @file_put_contents($fileName, $contents);

		return ($bytes !== false);
	}

	/**
	 * Returns the name of the temporary file used to replace $fileName.
	 *
	 * It lives in the same directory (so rename() is atomic) and ends in the same extension as the target: a web
	 * server must treat a half-written config.php the way it treats config.php itself, and not serve it as plain text
	 * because it ends in ".tmp". A target without an extension gets a temporary file without one.
	 *
	 * @param   string  $fileName  The full path to the target file
	 *
	 * @return  string
	 *
	 * @throws  \Exception  When no random bytes can be had
	 */
	protected function getTemporaryName($fileName)
	{
		$dir       = dirname($fileName);
		$base      = basename($fileName);
		$extension = pathinfo($base, PATHINFO_EXTENSION);
		$stem      = ($extension === '') ? $base : substr($base, 0, -(strlen($extension) + 1));

		return $dir . DIRECTORY_SEPARATOR . '.' . $stem . '.' . bin2hex(random_bytes(6)) . '.tmp'
			. (($extension === '') ? '' : ('.' . $extension));
	}

	/**
	 * Write the contents to a temporary file next to the target, then rename it over the target.
	 *
	 * @param   string  $fileName  The full path to the file
	 * @param   string  $contents  The contents to write to the file
	 *
	 * @return  boolean  True on success. On failure no temporary file is left behind and the target is untouched.
	 */
	private function writeAtomically($fileName, $contents)
	{
		// Replace the file a symbolic link points to, not the link itself
		if (@is_link($fileName))
		{
			$real = @realpath($fileName);

			if ($real === false)
			{
				return false;
			}

			$fileName = $real;
		}

		// Never replace a directory (or anything else which is not a regular file) by renaming over it
		if (@file_exists($fileName) && !@is_file($fileName))
		{
			return false;
		}

		// rename() replaces a read-only file as long as its directory is writable. A direct write would have failed.
		if (@file_exists($fileName) && !@is_writable($fileName))
		{
			return false;
		}

		try
		{
			$tempName = $this->getTemporaryName($fileName);
		}
		catch (\Exception $e)
		{
			return false;
		}

		// The temporary file gets the permissions the target has (or would have)
		$currentUmask = umask();
		$exists       = @is_file($fileName);
		$perms        = $exists ? @fileperms($fileName) : false;
		$perms        = ($perms === false) ? (0666 & ~$currentUmask) : ($perms & 07777);

		// Create it readable by its owner only, so that no data is ever in a file wider than the target will be
		umask(0077);
		$fp = @fopen($tempName, 'xb');
		umask($currentUmask);

		if ($fp === false)
		{
			return false;
		}

		// Set the final permissions BEFORE writing any data. If we can't, we must not write secrets in there.
		if (!@chmod($tempName, $perms))
		{
			@fclose($fp);
			@unlink($tempName);

			return false;
		}

		// Keep the owner and group where we are allowed to (best effort)
		if ($exists)
		{
			$owner = @fileowner($fileName);
			$group = @filegroup($fileName);

			if ($owner !== false && $owner !== @fileowner($tempName))
			{
				@chown($tempName, $owner);
			}

			if ($group !== false && $group !== @filegroup($tempName))
			{
				@chgrp($tempName, $group);
			}
		}

		$bytes = ($contents === '') ? 0 : @fwrite($fp, $contents);
		$closed = @fclose($fp);

		if ($bytes === false || $bytes !== strlen($contents) || !$closed)
		{
			@unlink($tempName);

			return false;
		}

		if (!@rename($tempName, $fileName))
		{
			@unlink($tempName);

			return false;
		}

		return true;
	}

	/**
	 * Delete a file (remove it from the disk)
	 *
	 * @param   string  $fileName  The full path to the file
	 *
	 * @return  boolean  True on success
	 */
	public function delete($fileName)
	{
		return @unlink($fileName);
	}

	/**
	 * Create a copy of the file
	 *
	 * @param   string  $from  The full path of the file to copy from
	 * @param   string  $to    The full path of the file that will hold the copy
	 *
	 * @return  boolean  True on success
	 */
	public function copy($from, $to)
	{
		return @copy($from, $to);
	}

	/**
	 * Move or rename a file
	 *
	 * @param   string  $from  The full path of the file to move
	 * @param   string  $to    The full path of the target file
	 *
	 * @return  boolean  True on success
	 */
	public function move($from, $to)
	{
		return @rename($from, $to);
	}

	/**
	 * Change the permissions of a file
	 *
	 * @param   string   $fileName     The full path of the file whose permissions will change
	 * @param   integer  $permissions  The new permissions, e.g. 0644 (remember the leading zero in octal numbers!)
	 *
	 * @return  boolean  True on success
	 */
	public function chmod($fileName, $permissions)
	{
		return @chmod($fileName, $permissions);
	}

    /**
     * Return the current working dir
     *
     * @return  string
     */
    public function cwd()
    {
        return @getcwd();
    }

	/**
	 * Create a directory if it doesn't exist. The operation is implicitly recursive, i.e. it will create all
	 * intermediate directories if they do not already exist.
	 *
	 * @param   string   $dirName      The full path of the directory to create
	 * @param   integer  $permissions  The permissions of the created directory
	 *
	 * @return  boolean  True on success
	 */
	public function mkdir($dirName, $permissions = 0755)
	{
		return @mkdir($dirName, $permissions, true);
	}

	/**
	 * Remove a directory if it exists.
	 *
	 * @param   string   $dirName    The full path of the directory to remove
	 * @param   boolean  $recursive  Should I remove its contents recursively? Otherwise it will fail if the directory
	 *                               is not empty.
	 *
	 * @return mixed
	 */
	public function rmdir($dirName, $recursive = true)
	{
		if (!$recursive)
		{
			return @rmdir($dirName);
		}
		else
		{
			if (!is_dir($dirName))
			{
				return $this->delete($dirName);
			}

			$ret = true;
			$di = new \DirectoryIterator($dirName);

			/** @var \DirectoryIterator $dirEntry */
			foreach ($di as $dirEntry)
			{
				if ($dirEntry->isDot())
				{
					continue;
				}

				if ($dirEntry->isFile())
				{
					$ret = $ret && $this->delete($dirEntry->getPathname());
				}
				elseif ($dirEntry->isDir())
				{
					$ret = $ret && $this->rmdir($dirEntry->getPathname(), true);
				}
			}

			$ret = $ret && @rmdir($dirName);

			return $ret;
		}
	}

	/**
	 * Translate an absolute filesystem path into a relative path for use by the filesystem abstraction, e.g. a relative
	 * (S)FTP path
	 *
	 * @param   string  $fileName  The full filesystem path of a file or directory
	 *
	 * @return  string  The translated path for use by the filesystem abstraction
	 */
	public function translatePath($fileName)
	{
		return $fileName;
	}

	/**
	 * Lists the subdirectories inside a directory
	 *
	 * @param   null|string  $dir  The directory to scan. Skip to use the current directory.
	 *
	 * @return  array|bool  A list of folders, or false if we could not get a listing
	 *
	 * @throws  \RuntimeException  When the server is incompatible with our folder scanner
	 */
	public function listFolders($dir = null)
	{
		if (empty($dir))
		{
			$dir = getcwd();
		}

		// Get a raw directory listing (hoping it's a UNIX server!)
		$list = array();

		$handle = @opendir($dir);

		if (!is_resource($handle))
		{
			throw new \RuntimeException(sprintf('Cannot list contents of directory "%s" – make sure the folder exists and that you have adequate permissions to it', $dir), 500);
		}

		while (($entry = readdir($handle)) !== false)
		{
			if (substr($entry, 0, 1) == '.')
			{
				continue;
			}

			if (!is_dir("$dir/$entry"))
			{
				continue;
			}

			$list[] = $entry;
		}

		closedir($handle);

		if (!empty($list))
		{
			asort($list);
		}

		return $list;
	}

    /**
     * Utility function to read the files in a folder.
     *
     * @param   string $path The path of the folder to read.
     * @param   string $filter A filter for file names.
     * @param   mixed $recurse True to recursively search into sub-folders, or an integer to specify the maximum depth.
     * @param   boolean $full True to return the full path to the file.
     * @param   array $exclude Array with names of files which should not be shown in the result.
     * @param   array $excludefilter Array of filter to exclude
     * @param   boolean $naturalSort False for asort, true for natsort
     *
     * @throws \InvalidArgumentException
     *
     * @return  array  Files in the given folder.
     */
    public function directoryFiles($path, $filter = '.', $recurse = false, $full = false, $exclude = array('.svn', 'CVS', '.DS_Store', '__MACOSX'),
                                 $excludefilter = array('^\..*', '.*~'), $naturalSort = false)
    {
        // @TODO: should we check the path to make sure it's valid and clean?

        // Is the path a folder?
        if (!is_dir($path))
        {
            throw new \InvalidArgumentException(sprintf('Path %s is not a directory', $path), 500);
        }

        // Compute the excludefilter string
        if (count($excludefilter))
        {
            $excludefilter_string = '/(' . implode('|', $excludefilter) . ')/';
        }
        else
        {
            $excludefilter_string = '';
        }

        // Get the files
        $arr = $this->folderItems($path, $filter, $recurse, $full, $exclude, $excludefilter_string, true);

        // Sort the files based on either natural or alpha method
        if ($naturalSort)
        {
            natsort($arr);
        }
        else
        {
            asort($arr);
        }
        return array_values($arr);
    }

    /**
     * Function to read the files/folders in a folder.
     *
     * @param   string   $path                  The path of the folder to read.
     * @param   string   $filter                A filter for file names.
     * @param   mixed    $recurse               True to recursively search into sub-folders, or an integer to specify the maximum depth.
     * @param   boolean  $full                  True to return the full path to the file.
     * @param   array    $exclude               Array with names of files which should not be shown in the result.
     * @param   string   $excludefilter_string  Regexp of files to exclude
     * @param   boolean  $findfiles             True to read the files, false to read the folders
     *
     * @return  array  Files.
     */
    protected function folderItems($path, $filter, $recurse, $full, $exclude, $excludefilter_string, $findfiles)
    {
        @set_time_limit((int) ini_get('max_execution_time'));

        // Initialise variables.
        $arr = array();

        // Read the source directory
        if (!($handle = @opendir($path)))
        {
            return $arr;
        }

        while (($file = readdir($handle)) !== false)
        {
            if ($file != '.' && $file != '..' && !in_array($file, $exclude)
                && (empty($excludefilter_string) || !preg_match($excludefilter_string, $file)))
            {
                // Compute the fullpath
                $fullpath = $path . DIRECTORY_SEPARATOR . $file;

                // Compute the isDir flag
                $isDir = is_dir($fullpath);

                if (($isDir xor $findfiles) && preg_match("/$filter/", $file))
                {
                    // (fullpath is dir and folders are searched or fullpath is not dir and files are searched) and file matches the filter
                    if ($full)
                    {
                        // Full path is requested
                        $arr[] = $fullpath;
                    }
                    else
                    {
                        // Filename is requested
                        $arr[] = $file;
                    }
                }
                if ($isDir && $recurse)
                {
                    // Search recursively
                    if (is_int($recurse))
                    {
                        // Until depth 0 is reached
                        $arr = array_merge($arr, $this->folderItems($fullpath, $filter, $recurse - 1, $full, $exclude, $excludefilter_string, $findfiles));
                    }
                    else
                    {
                        $arr = array_merge($arr, $this->folderItems($fullpath, $filter, $recurse, $full, $exclude, $excludefilter_string, $findfiles));
                    }
                }
            }
        }
        closedir($handle);
        return $arr;
    }
}
