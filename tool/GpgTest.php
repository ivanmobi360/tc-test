<?php
namespace tool;
use Utils;
class GpgTest extends \DatabaseBaseTest{
  
  public function testCreate(){
    /*$gpg = '/usr/bin/gpg';

    $recipient = 'john@doe.com';

    $secret_file = __DIR__ . DIRECTORY_SEPARATOR 'plain-text.txt';

    echo shell_exec("$gpg -e -r $recipient $secret_file");
    */
    
    // init gnupg
    /*$res = gnupg_init();
    // not really needed. Clearsign is default
    gnupg_setsignmode($res,GNUPG_SIG_MODE_CLEAR);
    // add key with passphrase 'test' for signing
    gnupg_addsignkey($res,"8660281B6051D071D94B5B230549F9DC851566DC","test");
    // sign
    $signed = gnupg_sign($res,"just a test");
    echo $signed;*/
    
    $email = new \tool\MailerBuilder('special-email@tixpro.com', 'Special Email');
    $email->setIsEncryptedMessage(true);
    @$email->setContent('Hello world');
    $email->sendmail();
    
    
  }
  
    
}