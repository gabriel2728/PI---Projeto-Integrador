<?php
/**
 * PHPMailer - PHP email creation and transport class.
 * PHP Version 5.5+
 * @package PHPMailer
 * @link https://github.com/PHPMailer/PHPMailer
 * @author Marcus Bointon (Synchro/coolbru) <phpmailer@synchromedia.co.uk>
 * @author Jim Jagielski (jimjag) <jimjag@gmail.com>
 * @author Andy Prevost (codeworxtech) <codeworxtech@users.sourceforge.net>
 * @author Brent R. Matzelle (original founder)
 * @copyright 2012 - 2020 Marcus Bointon
 * @copyright 2010 - 2012 Jim Jagielski
 * @copyright 2004 - 2009 Andy Prevost
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

namespace PHPMailer\PHPMailer;

/**
 * PHPMailer class.
 */
class PHPMailer
{
    public $SMTPAuth = false;
    public $SMTPSecure = '';
    public $Host = 'localhost';
    public $Port = 25;
    public $Username = '';
    public $Password = '';
    public $From = '';
    public $FromName = '';
    public $Subject = '';
    public $Body = '';
    public $AltBody = '';
    public $CharSet = 'UTF-8';
    public $Mailer = 'mail';
    public $ErrorInfo = '';

    public function __construct($exceptions = null)
    {
    }

    public function isSMTP()
    {
        $this->Mailer = 'smtp';
        return $this;
    }

    public function setFrom($address, $name = '', $auto = true)
    {
        $this->From = $address;
        $this->FromName = $name;
        return true;
    }

    public function addAddress($address, $name = '')
    {
        return true;
    }

    public function addReplyTo($address, $name = '')
    {
        return true;
    }

    public function isHTML($isHtml = true)
    {
        return true;
    }

    public function send()
    {
        try {
            if ($this->Mailer === 'smtp') {
                return $this->smtpSend();
            } else {
                return $this->mailSend();
            }
        } catch (\Exception $e) {
            $this->ErrorInfo = $e->getMessage();
            return false;
        }
    }

    private function smtpSend()
    {
        // Implementação básica SMTP
        $this->ErrorInfo = 'SMTP não implementado nesta versão simplificada';
        return false;
    }

    private function mailSend()
    {
        $headers = 'From: ' . $this->From;
        if (!empty($this->FromName)) {
            $headers .= ' <' . $this->FromName . '>';
        }
        $headers .= "\r\n";
        $headers .= 'Reply-To: ' . $this->From . "\r\n";
        $headers .= 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-Type: text/plain; charset=' . $this->CharSet . "\r\n";

        return mail($this->toAddresses[0] ?? '', $this->Subject, $this->Body, $headers);
    }

    public function smtpConnect()
    {
        return false;
    }

    public function smtpClose()
    {
    }
}