<?php
/**
 * Provides a dummy method for password encryption (passthru).
 *
 * @package    phpMyFAQ 
 * @subpackage PMF_Enc
 * @author     Lars Scheithauer <lars.scheithauer@googlemail.com>
 * @since      2009-09-04
 * @version    SVN: $Id$
 * @copyright  2005-2009 phpMyFAQ Team
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
 * PMF_Enc_Enc
 *
 * @package    phpMyFAQ 
 * @subpackage PMF_Enc
 * @author     Lars Scheithauer <lars.scheithauer@googlemail.com>
 * @since      2005-09-18
 * @version    SVN: $Id$
 * @copyright  2005-2009 phpMyFAQ Team
 */
class PMF_Enc_Enc extends PMF_Enc
{
    /**
     * Name of the encryption method.
     *
     * @var string
     */
    public $enc_method = 'none';

    /**
     * encrypts the string str and returns the result.
     *
     * @param  string $str String
     * @return string
     */
    public function encrypt($str)
    {
        return $str;
    }
}