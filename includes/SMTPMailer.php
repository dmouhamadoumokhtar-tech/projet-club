<?php
/**
 * Classe simple pour l'envoi d'emails via SMTP (Gmail)
 * Sans dépendances externes (PHPMailer, etc.)
 */
class SMTPMailer {
    private $host = 'smtp.gmail.com';
    private $port = 587;
    private $username;
    private $password; // Mot de passe d'application Google
    private $socket;

    public function __construct($username, $password) {
        $this->username = $username;
        $this->password = $password;
    }

    public function send($to, $subject, $message, $headers = []) {
        // En-têtes par défaut
        $defaultHeaders = [
            'From' => $this->username,
            'Reply-To' => $this->username,
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/html; charset=UTF-8'
        ];
        
        // Fusionner les en-têtes
        $headers = array_merge($defaultHeaders, $headers);

        // Connexion au serveur SMTP
        if (!$this->connect()) {
            return false;
        }

        // Authentification
        if (!$this->auth()) {
            return false;
        }

        // Envoyer l'email
        if (!$this->sendCommand('MAIL FROM: <' . $this->username . '>')) return false;
        if (!$this->sendCommand('RCPT TO: <' . $to . '>')) return false;
        if (!$this->sendCommand('DATA')) return false;

        // Construire le contenu
        $content = '';
        foreach ($headers as $key => $val) {
            $content .= $key . ': ' . $val . "\r\n";
        }
        $content .= "Subject: " . $subject . "\r\n";
        $content .= "\r\n";
        $content .= $message . "\r\n";
        $content .= ".";

        if (!$this->sendCommand($content)) return false;
        $this->sendCommand('QUIT');
        
        fclose($this->socket);
        return true;
    }

    private function connect() {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);

        $this->socket = stream_socket_client('tcp://' . $this->host . ':' . $this->port, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
        
        if (!$this->socket) {
            $this->log("ERREUR CONNEXION: $errno - $errstr");
            return false;
        }
        
        $this->getResponse(); 
        
        $this->sendCommand('EHLO ' . gethostname());
        $this->sendCommand('STARTTLS');
        
        // Le contexte SSL est déjà défini lors de la création du socket, 
        // mais stream_socket_enable_crypto l'utilise pour le handshake.
        if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
             $this->log("ERREUR CRYPTO: Impossible d'activer TLS");
            return false;
        }
        
        return $this->sendCommand('EHLO ' . gethostname());
    }

    private function auth() {
        if (!$this->sendCommand('AUTH LOGIN')) return false;
        if (!$this->sendCommand(base64_encode($this->username))) return false;
        if (!$this->sendCommand(base64_encode($this->password))) return false;
        return true;
    }

    private function sendCommand($cmd) {
        // Masquer le mot de passe dans les logs
        if (strpos($cmd, 'AUTH') === false && base64_decode($cmd, true) === false) {
             $this->log("CLIENT: " . $cmd);
        } else {
             $this->log("CLIENT: [SECRT]");
        }
        
        fputs($this->socket, $cmd . "\r\n");
        $response = $this->getResponse();
        
        // Vérifier si le code commence par 2 ou 3 (Succès)
        if (substr($response, 0, 1) != '2' && substr($response, 0, 1) != '3') {
            $this->log("ERREUR CRITIQUE: " . $response);
            return false;
        }
        return true;
    }

    private function getResponse() {
        $response = "";
        while ($str = fgets($this->socket, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) == " ") {
                break;
            }
        }
        $this->log("SERVEUR: " . trim($response));
        return $response;
    }

    private function log($msg) {
        $logDir = sys_get_temp_dir();
        $logFile = $logDir . '/smtp_debug.log';
        @file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $msg . "\n", FILE_APPEND);
    }
}
?>
