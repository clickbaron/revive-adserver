<?php
/**
 * Table Definition for ext_thorium_account_mapping
 */
require_once MAX_PATH.'/lib/max/Dal/DataObjects/DB_DataObjectCommon.php';

class DataObjects_Ext_thorium_account_mapping extends DB_DataObjectCommon 
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'ext_thorium_account_mapping';     // table name
    public $agencyid;                        // MEDIUMINT(9) => openads_mediumint => 129 
    public $account_id;                      // VARCHAR(32) => openads_varchar => 130 

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('DataObjects_Ext_thorium_account_mapping',$k,$v); }

    var $defaultValues = array(
                'account_id' => '',
                );

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE
}
?>