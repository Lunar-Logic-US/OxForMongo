<?php

interface Ox_Mailer {
    const MAIL_LOG_COLLECTION = 'mail_log';
    
    /**
    * Send an email.
    *
    * @param string $from
    * @param string $to
    * @param string $subject 
    * @param string $messageInHtml - The content of the message, in HTML format. Use this for email clients pubthat can process HTML.
    * @param string $messageInText - The content of the message, in text format. Use this for text-based email clients, or clients on high-latency networks.
    *
    * @return array $result
    */
    public function sendMail($from, $to, $subject, $messageInHtml, $messageInText);
}