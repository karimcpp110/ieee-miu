<?php
namespace PHPMailer\PHPMailer;

class SMTP {
    const VERSION = '6.8.0';
    const CRLF = "\r\n";
    const DEFAULT_PORT = 25;
    const MAX_LINE_LENGTH = 998;
    const MAX_REPLY_LENGTH = 512;
    protected $smtp_conn;
    protected $error = ['error' => '', 'detail' => '', 'smtp_code' => '', 'smtp_code_ex' => ''];
    protected $helo_rply;
    protected $server_caps;
    protected $last_reply = '';
    
    public function connect($host, $port = null, $timeout = 30, $options = []) {
        $this->error = ['error' => '', 'detail' => '', 'smtp_code' => '', 'smtp_code_ex' => ''];
        if ($this->connected()) { $this->error['error'] = 'Already connected to a server'; return false; }
        if (empty($port)) { $port = self::DEFAULT_PORT; }
        $this->smtp_conn = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if (empty($this->smtp_conn)) { $this->error = ['error' => 'Connection failed', 'detail' => $errstr, 'smtp_code' => $errno]; return false; }
        if (strpos(PHP_OS, 'WIN') !== 0) { stream_set_timeout($this->smtp_conn, $timeout, 0); }
        $this->last_reply = $this->get_lines();
        return true;
    }

    public function hello($host = '') {
        return $this->sendHello('EHLO', $host) || $this->sendHello('HELO', $host);
    }

    protected function sendHello($hello, $host) {
        $this->smtp_conn; $this->client_send($hello . ' ' . $host . self::CRLF);
        $this->last_reply = $this->get_lines();
        return substr($this->last_reply, 0, 3) == '250';
    }

    public function authenticate($user, $pass, $authtype = null, $OAuth = null) {
        $this->client_send('AUTH LOGIN' . self::CRLF);
        $this->get_lines();
        $this->client_send(base64_encode($user) . self::CRLF);
        $this->get_lines();
        $this->client_send(base64_encode($pass) . self::CRLF);
        $this->last_reply = $this->get_lines();
        return substr($this->last_reply, 0, 3) == '235';
    }

    public function connected() {
        if (!is_resource($this->smtp_conn)) { return false; }
        $sock_status = stream_get_meta_data($this->smtp_conn);
        if ($sock_status['eof']) { $this->close(); return false; }
        return true;
    }

    public function close() {
        $this->error = ['error' => '', 'detail' => '', 'smtp_code' => '', 'smtp_code_ex' => ''];
        $this->server_caps = null;
        $this->helo_rply = null;
        if (is_resource($this->smtp_conn)) { fclose($this->smtp_conn); $this->smtp_conn = null; }
    }

    protected function client_send($data) { return fwrite($this->smtp_conn, $data); }

    protected function get_lines() {
        if (!is_resource($this->smtp_conn)) { return ''; }
        $data = ''; $starttime = time();
        while (is_resource($this->smtp_conn) && !feof($this->smtp_conn)) {
            $str = @fgets($this->smtp_conn, 515); $data .= $str;
            if (!isset($str[3]) || (isset($str[3]) && $str[3] == ' ')) { break; }
            $info = stream_get_meta_data($this->smtp_conn);
            if ($info['timed_out']) { break; }
            if (time() - $starttime > 30) { break; }
        }
        return $data;
    }

    public function mail($from) {
        $this->client_send('MAIL FROM:<' . $from . '>' . self::CRLF);
        $this->last_reply = $this->get_lines();
        return substr($this->last_reply, 0, 3) == '250';
    }

    public function recipient($to) {
        $this->client_send('RCPT TO:<' . $to . '>' . self::CRLF);
        $this->last_reply = $this->get_lines();
        return substr($this->last_reply, 0, 3) == '250' || substr($this->last_reply, 0, 3) == '251';
    }

    public function data($msg_data) {
        $this->client_send('DATA' . self::CRLF);
        $this->last_reply = $this->get_lines();
        if (substr($this->last_reply, 0, 3) != '354') { return false; }
        $msg_data = str_replace(["\r\n", "\r"], "\n", $msg_data);
        $lines = explode("\n", $msg_data);
        foreach ($lines as $line) {
            if (strpos($line, '.') === 0) { $line = '.' . $line; }
            $this->client_send($line . self::CRLF);
        }
        $this->client_send(self::CRLF . '.' . self::CRLF);
        $this->last_reply = $this->get_lines();
        return substr($this->last_reply, 0, 3) == '250';
    }

    public function quit($close_on_error = true) {
        $this->client_send('QUIT' . self::CRLF);
        $res = $this->get_lines();
        if ($close_on_error) { $this->close(); }
        return substr($res, 0, 3) == '221';
    }

    public function getError() { return $this->error; }
}
