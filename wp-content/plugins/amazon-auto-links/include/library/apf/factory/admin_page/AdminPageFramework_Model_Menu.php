<?php
/*
 * Admin Page Framework v3.9.1b05 by Michael Uno
 * Compiled with Admin Page Framework Compiler <https://github.com/michaeluno/amazon-auto-links-compiler>
 * <https://en.michaeluno.jp/amazon-auto-links>
 * Copyright (c) 2013-2022, Michael Uno; Licensed under MIT <https://opensource.org/licenses/MIT>
 */

abstract class AmazonAutoLinks_AdminPageFramework_Model_Menu extends AmazonAutoLinks_AdminPageFramework_Controller_Page {
    public function __construct($sOptionKey=null, $sCallerPath=null, $sCapability='manage_options', $sTextDomain='amazon-auto-links')
    {
        parent::__construct($sOptionKey, $sCallerPath, $sCapability, $sTextDomain);
        new AmazonAutoLinks_AdminPageFramework_Model_Menu__RegisterMenu($this);
    }
}
