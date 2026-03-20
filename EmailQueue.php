<?php
require_once 'Database.php';

$libFiles = ['lib/PHPMailer.php', 'lib/SMTP.php', 'lib/Exception.php'];
$hasLib = true;
foreach ($libFiles as $file) {
    if (!file_exists($file)) {
        $hasLib = false;
        break;
    }
}

if ($hasLib) {
    require_once 'lib/PHPMailer.php';
    require_once 'lib/SMTP.php';
    require_once 'lib/Exception.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailQueue
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function enqueue($recipient, $subject, $body, $sendAfter = null)
    {
        // Using NULL for immediate delivery tells MySQL to use its own NOW() during comparison,
        // avoiding timezone mismatches between PHP and DB.
        $sql = "INSERT INTO email_queue (recipient_email, subject, body, send_after, status) VALUES (?, ?, ?, ?, 'pending')";
        return $this->db->query($sql, [$recipient, $subject, $body, $sendAfter]);
    }

    public function process($limit = 5)
    {
        global $hasLib;
        $limit = (int) $limit;
        // Process emails where send_after is NULL (immediate) or reached (past/now)
        $emails = $this->db->query(
            "SELECT * FROM email_queue 
             WHERE status = 'pending' 
             AND (send_after IS NULL OR send_after <= NOW()) 
             ORDER BY created_at ASC LIMIT $limit"
        )->fetchAll();

        $sentCount = 0;
        foreach ($emails as $email) {
            if ($hasLib) {
                $mail = new PHPMailer(true);
                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'ieeemiuportal@gmail.com';
                    $mail->Password = 'bbra upqw ctlg zcxu'; // App Password
                    $mail->SMTPSecure = 'ssl';
                    $mail->Port = 465;

                    // Recipients
                    $mail->setFrom('ieeemiuportal@gmail.com', 'IEEE MIU Portal');
                    $mail->addAddress($email['recipient_email']);
                    $mail->addReplyTo('ieeemiuportal@gmail.com', 'IEEE MIU Portal');

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = $email['subject'];
                    $mail->Body = $email['body'];
                    $mail->AltBody = strip_tags($email['body']);

                    $mail->send();

                    $this->db->query(
                        "UPDATE email_queue SET status = 'sent', sent_at = NOW(), attempts = attempts + 1 WHERE id = ?",
                        [$email['id']]
                    );
                    $sentCount++;
                } catch (Exception $e) {
                    $error = "Mailer Error: " . $mail->ErrorInfo;
                    $this->db->query(
                        "UPDATE email_queue SET status = 'failed', attempts = attempts + 1, last_error = ? WHERE id = ?",
                        [$error, $email['id']]
                    );
                }
            } else {
                // Fallback to basic PHP mail()
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= "From: IEEE MIU Portal <ieeemiuportal@gmail.com>" . "\r\n";

                if (@mail($email['recipient_email'], $email['subject'], $email['body'], $headers)) {
                    $this->db->query("UPDATE email_queue SET status = 'sent', sent_at = NOW(), attempts = attempts + 1 WHERE id = ?", [$email['id']]);
                    $sentCount++;
                } else {
                    $this->db->query("UPDATE email_queue SET status = 'failed', attempts = attempts + 1, last_error = 'Basic mail() failed' WHERE id = ?", [$email['id']]);
                }
            }
        }
        return $sentCount;
    }
}
?>