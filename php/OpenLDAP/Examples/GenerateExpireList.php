<?php

include_once ("OpenLDAPPasswdExpireNotify.class.php");
 
$objPasswdExpire = new OpenLDAPPasswdExpireNotify();
$objPasswdExpire->generateList(14, "test.csv");
 
?>
