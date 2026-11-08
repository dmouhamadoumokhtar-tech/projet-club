<?php
/**
 * Sessions PHP stockées dans la base MySQL/TiDB.
 * Nécessaire sur Vercel : chaque requête peut s'exécuter sur une instance différente,
 * les fichiers de session classiques (/tmp) ne sont donc pas partagés.
 */

class SessionBaseDeDonnees implements SessionHandlerInterface
{
    private $db = null;

    private function db()
    {
        if ($this->db === null) {
            $this->db = cslmdb_connexion_mysql();
        }
        return $this->db;
    }

    public function open($path, $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    #[\ReturnTypeWillChange]
    public function read($id)
    {
        $stmt = $this->db()->prepare('SELECT data FROM php_sessions WHERE id = ? AND expires > ?');
        $now = time();
        $stmt->bind_param('si', $id, $now);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ? $row['data'] : '';
    }

    public function write($id, $data): bool
    {
        $expires = time() + (int) ini_get('session.gc_maxlifetime');
        $stmt = $this->db()->prepare('REPLACE INTO php_sessions (id, data, expires) VALUES (?, ?, ?)');
        $stmt->bind_param('ssi', $id, $data, $expires);
        return $stmt->execute();
    }

    public function destroy($id): bool
    {
        $stmt = $this->db()->prepare('DELETE FROM php_sessions WHERE id = ?');
        $stmt->bind_param('s', $id);
        return $stmt->execute();
    }

    #[\ReturnTypeWillChange]
    public function gc($max_lifetime)
    {
        $now = time();
        $stmt = $this->db()->prepare('DELETE FROM php_sessions WHERE expires < ?');
        $stmt->bind_param('i', $now);
        $stmt->execute();
        return $stmt->affected_rows;
    }
}
