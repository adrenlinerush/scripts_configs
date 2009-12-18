<?php

include_once ("OpenLDAPPasswdExpireNotify.class.php");
 
$objPasswdExpire = new OpenLDAPPasswdExpireNotify();
$objPasswdExpire->notify(14);
 
?>
