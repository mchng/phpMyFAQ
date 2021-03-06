<?php
/**
 * File handler class
 *
 * @package    phpMyFAQ
 * @license    MPL
 * @author     Anatoliy Belsky <ab@php.net>
 * @since      2009-08-21
 * @version    SVN: $Id: File.php 4872 2009-09-06 10:54:06Z anatoliy $
 * @copyright  2009 phpMyFAQ Team
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 */

/**
 * PMF_Atachment_Filesystem_File
 * 
 * @package    phpMyFAQ
 * @license    MPL
 * @author     Anatoliy Belsky <ab@php.net>
 * @since      2009-08-21
 * @version    SVN: $Id: File.php 4872 2009-09-06 10:54:06Z anatoliy $
 * @copyright  2009 phpMyFAQ Team
 */
abstract class PMF_Attachment_Filesystem_File extends PMF_Attachment_Filesystem_Entry
{
    /**
     * Enums
     */
    const MODE_READ   = 'rb';
    const MODE_APPEND = 'ab';
    const MODE_WRITE  = 'wb';
    
    /**
     * Filemode
     * 
     * @var string
     */
    protected $mode;
    
    /**
     * Constructor 
     * 
     * @param string $filepath path to file
     * @param string $mode     mode for fopen
     * 
     * @return null
     * TODO check for correct mode if file doesn't exist
     */
    public function __construct($filepath, $mode = self::MODE_READ)
    {
        $this->path = $filepath;
        $this->mode = $mode;
        
        $this->handle = @fopen($this->path, $this->mode);
        
        if(!is_resource($this->handle)) {
            throw new PMF_Attachment_Filesystem_File_Exception('Could not open file');
        }
    }
    
    /**
     * Destructor
     * 
     * @return null
     */
    public function __destruct()
    {
        if(is_resource($this->handle)) {
            fclose($this->handle);
        }
    }
    
    /**
     * Either EOF was reached
     * 
     * @return boolean
     */
    public function eof()
    {
        return feof($this->handle);
    }
    
    /**
     * Get next file chunk
     * 
     * @return string
     */
    abstract public function getChunk();
    
    /**
     * Put chunk into file.
     * 
     * @param string $chunk chunk to write
     * 
     * @return integer bytes written or false
     */
    abstract public function putChunk($chunk);
    
    /**
     * @see inc/PMF_Attachment/Filesystem/PMF_Attachment_Filesystem_Entry#delete()
     */
    public function delete()
    {
        $retval = true;
        
        if($this->handle) {
            fclose($this->handle);
        }
        
        if(!is_uploaded_file($this->path) && file_exists($this->path)) {
            $retval = unlink($this->path);
        }
        
        return $retval;
    }
    
    /**
     * Return current file mode
     * 
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }
    
    /**
     * Reopen file in given mode
     * 
     * @param string $mode file mode
     * 
     * @return boolean
     */
    public function setMode($mode)
    {
        $retval = false;
        
        if(in_array($mode, array(self::MODE_WRITE, self::MODE_READ, self::MODE_APPEND))) {
            fclose($this->handle);
            $this->handle = fopen($this->path, $mode);
        
            $retval = is_resource($this->handle);
        }
        
        return $retval;
    }
    
    /**
     * Simple copy file
     * 
     * @param string $target filepath
     * 
     * @return boolean
     */
    public function copyToSimple($target)
    {
        $retval = false;
        
        if(is_uploaded_file($this->path)) {
            $retval = move_uploaded_file($this->path, $target);
        } else {
            $retval = copy($this->path, $target);
        }
        
        return $retval;
    }
    
    /**
     * Selfcheck
     * 
     * @return boolean
     */
    public function isOk()
    {
        return is_resource($this->handle);
    }
}