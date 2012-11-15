<?php
/*
 * $Id: Module.php,v 1.5 2003/10/04 15:26:34 dan Exp $
 */

/**
 * Superclass for the modules
 */
class Module {
    //private:
    //var $_myUserInfo;  //denna ska v�l inte vara privat? //
    // Eller ska vi ha en getMyUserInfo() metod sen? /dan
    // Tomas: Just h�r �r den privat. Den som kommer accessas fr�n templates
    // ligger i Core. Detta �r bara en "pekare" som anv�nds av modulerna.
    
    var $_smarty; // Tomas: Beh�ver inte vara public
    var $_core; // Tomas: Bra att ha...

    // If the module can be cached. Set this to true in the inherited modules
    // if they are supposed to be cached.
    var $caching = false;

    //public:
    
    function Module(&$smarty, &$core) {
        $this->_core = &$core;
        //$this->_myUserInfo = &$core->myUserInfo;
        $this->_smarty = &$smarty;
    }
}
?>
