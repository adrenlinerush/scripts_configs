<?php

class OpenLDAPPasswdExpireNotify {

  private $logwriter;
  private $strHost;
  private $strSearchDC;
  private $strBindDN;
  private $strBindPwd;
  private $strFromEML;
  private $connLdap;
  private $aryUsers;  

  function __construct() {
    include_once ("OpenLDAPPasswdExpireNotify.config.php");
    
    $this->strHost = $ldapHost;
    $this->strSearchDC = $searchdc;
    $this->strBindDN = $binddn;
    $this->strBindPwd = $bindpwd;
    $this->strFromEML = $smtp_from;

    require_once ("csLogging.class.php");

    $this->logwriter = new csLogging($errorlogfile,$debuglogfile,$debug);
    
  }
  
  public function notify($days) {   
    $this->bindLDAP();   
    $this->getUsers($days);   
    $this->generateEmails();
  }
  public function generateList($days, $filepath) {
    $this->bindLDAP();
    $this->getUsers($days);
    $this->writeToFile($filepath);
  }
  
  private function bindLDAP() {
    ldap_set_option($this->connLdap, LDAP_OPT_PROTOCOL_VERSION, 3);
    $this->logwriter->debugwrite("Set LDAP Version to 3");
    $this->connLdap = ldap_connect("ldaps://$this->strHost:636") or $ldap = false;
    if ($this->connLdap) {
      $this->logwriter->debugwrite('Successfully Connected to LDAP Server');
      $res = ldap_bind($this->connLdap,$this->strBindDN,$this->strBindPwd) or $res = false;
      if ($res) { 
        $this->logwriter->debugwrite("Successfully Bound with Search DN: $this->strBindDN Passwd: $this->strBindPwd");
      }
      else {
         $this->logwriter->writelog("Unable to Bind with User: $this->strBindDN,  Password: $this->strBindPwd Error: " . ldap_error($ldap));
      }
    }
    else {
      $this->logwriter->writelog("Failed to Find Host: $this->strHost");
    }
  }
  
  private function getUsers($days) {
    $date = time();
    $this->logwriter->debugwrite("Current Date: $date");
    $cutoff = ($date+$days*24*60*60);
    $this->logwriter->debugwrite("Cuttoff Date: $cutoff");
    $filter = "sambaPwdMustChange<=".$cutoff;
    $this->logwriter->debugwrite("Filter: $filter");

    $this->aryUsers = ldap_search($this->connLdap,$this->strSearchDC,$filter);
    if ($this->aryUsers) {
      $this->logwriter->debugwrite("Search Completed Without Errors");
      $users = ldap_get_entries($this->connLdap,$this->aryUsers);   
      if ($users["count"] > 0) {
        $this->logwriter->debugwrite("Search Returned Users");
      }
      else {
        $this->logwriter->debugwrite("Search Returned no results.");
      }
    }
    else {  
      $this->logwriter->writelog("Search Failed with Error: " . ldap_error($this->aryUsers));
    }

  }
  
  private function generateEmails() {
    $user = ldap_first_entry($this->connLdap,$this->aryUsers);
    while($user) {
      $attrs = ldap_get_attributes($this->connLdap,$user);
      $user_email = $attrs["mail"][0];
      $this->logwriter->debugwrite("User $user email: $user_email");
      $user_name = $attrs["gecos"][0];
      $this->logwriter->debugwrite("User $user gecos: $user_name");
      $user_uid = $attrs["uid"][0];
      $this->logwriter->debugwrite("User $user uid: $user_uid");      
      $user_pwdexpire = $attrs["sambaPwdMustChange"][0];
      $this->logwriter->debugwrite("User $user sambaPwdMustChange: $user_pwdexpire");
      $curdate = time();
      $daystil_expire = (($user_pwdexpire - $curdate)/60/60/24);
      $this->logwriter->debugwrite("User $user days till expiration: $daystil_expire");
      $days = (int)$daystil_expire;

      $body = $user_name.",\n" .
        "Your password for $user_uid will expire in $days days.  " .
        "Please login to your webmail account to change your password before it expires.  " .
        "If you wait until the password expires you will need to contact the IS team to reset your password for you.\n" .
        "Sincerely,\n" .
        "Country Stone Information Systems Team";

      $bRet = mail($user_email,"Alliance Domain Password Expiration Notice", $body, $this->strFromEML);

      if ($bRet) {
        $this->logwriter->debugwrite("Email sent to User: $user_name\n$body");
      }
      else {
        $this->logwriter->writelog("Failed to Send E-Mail");
      }
      
      $user = ldap_next_entry($this->connLdap,$user);

    }
  }
  
  private function writeToFile($path) {
    file_put_contents($path,"NAME,UID,E-MAIL,DAYS PWD VALID\n", FILE_APPEND | LOCK_EX);
    $user = ldap_first_entry($this->connLdap,$this->aryUsers);
    while($user) {
      $attrs = ldap_get_attributes($this->connLdap,$user);
      $user_email = $attrs["mail"][0];
      $this->logwriter->debugwrite("User $user email: $user_email");
      $user_name = $attrs["gecos"][0];
      $this->logwriter->debugwrite("User $user gecos: $user_name");
      $user_uid = $attrs["uid"][0];
      $this->logwriter->debugwrite("User $user uid: $user_uid");      
      $user_pwdexpire = $attrs["sambaPwdMustChange"][0];
      $this->logwriter->debugwrite("User $user sambaPwdMustChange: $user_pwdexpire");
      $curdate = time();
      $daystil_expire = (($user_pwdexpire - $curdate)/60/60/24);
      $this->logwriter->debugwrite("User $user days till expiration: $daystil_expire");

      file_put_contents($path,$user_name.",".$user_uid.",".$user_email.",".$daystil_expire."\n", FILE_APPEND | LOCK_EX);

      $user = ldap_next_entry($this->connLdap,$user);
    }
  }
}
?>


