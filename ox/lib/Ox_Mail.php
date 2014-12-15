<?php


require_once(OX_FRAMEINTERFACE  . 'Ox_Mailer.php');

/**
 * Default Ox Mailer. Usese PHP's mail function by default calls /usr/sbin/sendmail
 * Available through Ox_LibraryLoader::mailer()
 */
class Ox_Mail implements Ox_Mailer {
    public function sendMail($from, $to, $subject, $messageInHtml=null, $messageInText=null) {
        $headers = "From: " . $from . "\r\n";
        $headers .= "Reply-To: ". $from . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        
        if(!is_null($messageInHtml)) {
            $message = $messageInHtml;
            $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
        } else {
            $message = $messageInText;
        }
        
        $log_entry = array(
            'type'=>'local',
            'to'=>$to,
            'headers'=>$headers,
            'message'=>$message,
            'timestamp'=>new MongoDate()
        );
        
        if(!@mail($to, $subject, $message, $headers)) {
            Ox_Logger::logError('Error sending: ' . print_r($log_entry, 1));
            $log_entry['is_success'] = false;    
        } else {
            $log_entry['is_success'] = true;
        }
        
        Ox_LibraryLoader::db()->{Ox_Mailer::MAIL_LOG_COLLECTION}->insert($log_entry);
    }
}