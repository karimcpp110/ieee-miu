<?php
namespace PHPMailer\PHPMailer;

require_once 'Exception.php';
require_once 'SMTP.php';

class PHPMailer {
    public $Priority = null;
    public $CharSet = 'UTF-8';
    public $ContentType = 'text/plain';
    public $Encoding = '8bit';
    public $From = 'root@localhost';
    public $FromName = 'Root User';
    public $Subject = '';
    public $Body = '';
    public $AltBody = '';
    public $WordWrap = 0;
    public $Mailer = 'smtp';
    public $Sendmail = '/usr/sbin/sendmail';
    public $UseSendmailOptions = true;
    public $Host = 'localhost';
    public $Port = 25;
    public $Helo = '';
    public $SMTPSecure = '';
    public $SMTPAuth = false;
    public $Username = '';
    public $Password = '';
    public $Timeout = 300;
    public $SMTPDebug = 0;
    public $ErrorInfo = '';
    public $SMTPKeepAlive = false;
    public $SingleTo = false;
    protected $to = [];
    protected $error_count = 0;
    protected $last_error = '';
    protected $smtp = null;

    public function __construct($exceptions = null) {
        $this->smtp = new SMTP();
    }

    public function isSMTP() {
        $this->Mailer = 'smtp';
    }

    public function setFrom($address, $name = '') {
        $this->From = $address;
        $this->FromName = $name;
        return true;
    }

    public function addAddress($address, $name = '') {
        $this->to[] = [$address, $name];
        return true;
    }

    public function addReplyTo($address, $name = '') {
        return true; // Simplified
    }

    public function isHTML($isHtml = true) {
        $this->ContentType = ($isHtml ? 'text/html' : 'text/plain');
    }

    public function send() {
        try {
            if ($this->Mailer == 'smtp') {
                return $this->smtpSend();
            }
            return mail($this->to[0][0], $this->Subject, $this->Body);
        } catch (Exception $e) {
            $this->last_error = $e->getMessage();
            return false;
        }
    }

    protected function smtpSend() {
        $host = $this->Host;
        if ($this->SMTPSecure == 'ssl') $host = 'ssl://' . $host;
        
        if (!$this->smtp->connect($host, $this->Port)) {
            throw new Exception("Connect failed: " . json_encode($this->smtp->getError()));
        }
        
        if ($this->Helo == '') $this->Helo = $this->Host;
        $this->smtp->hello($this->Helo);

        if ($this->SMTPSecure == 'tls') {
            // Simplified STARTTLS implementation would go here
            // For now, let's assume the user can use 'ssl' or direct connection
        }

        if ($this->SMTPAuth) {
            if (!$this->smtp->authenticate($this->Username, $this->Password)) {
                throw new Exception("Auth failed");
            }
        }
        $this->smtp->mail($this->From);
        foreach ($this->to as $to) {
            $this->smtp->recipient($to[0]);
        }
        $header = "Date: " . date('D, j M Y H:i:s O') . "\r\n";
        $header .= "To: " . $this->to[0][0] . "\r\n";
        $header .= "From: " . $this->FromName . " <" . $this->From . ">\r\n";
        $header .= "Subject: " . $this->Subject . "\r\n";
        $header .= "Content-Type: " . $this->ContentType . "; charset=" . $this->CharSet . "\r\n\r\n";
        
        if (!$this->smtp->data($header . $this->Body)) {
            throw new Exception("Data failed");
        }
        $this->smtp->quit();
        return true;
    }
}
