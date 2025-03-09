<?php

class DatabaseHandler
{
    private $pdo; // PDO-Instanz
    private $sessionStarted = false;

    /**
     * Konstruktor: Initialisiert die Verbindung zur Datenbank.
     * 
     * @param string $dsn Datenbank-DSN (z. B. mysql:host=localhost;dbname=example;charset=utf8)
     * @param string $username Datenbankbenutzer
     * @param string $password Datenbankpasswort
     * @param array $options PDO-Optionen
     */
    public function __construct($dsn, $username, $password, $options = [])
    {
        try {
            $this->pdo = new PDO($dsn, $username, $password, $options);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            http_send_status(506);
            die("Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
        }
    }

    /**
     * Startet die PHP-Session.
     * 
     * @return void
     */
    public function startSession()
    {
        if (!$this->sessionStarted) {
            session_start();
            $this->sessionStarted = true;
        }
    }

    /**
     * Führt einen SELECT-Befehl aus und gibt die Ergebnisse zurück.
     * 
     * @param string $query SQL-SELECT-Query
     * @param array $params Parameter für das Prepared Statement
     * @return array Ergebnisdaten als Array
     */
    public function select($query, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Fehler beim Ausführen des SELECT-Befehls: " . $e->getMessage());
        }
    }

    /**
     * Führt ein INSERT-, UPDATE- oder DELETE-Statement aus.
     * 
     * @param string $query SQL-Query
     * @param array $params Parameter für das Prepared Statement
     * @return int Anzahl der betroffenen Zeilen
     */
    public function execute($query, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            die("Fehler beim Ausführen des SQL-Befehls: " . $e->getMessage());
        }
    }

    /**
     * Ruft die zuletzt eingefügte ID ab.
     * 
     * @return string Letzte eingefügte ID
     */
    public function getLastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Setzt einen Wert in die Session.
     * 
     * @param string $key Schlüssel
     * @param mixed $value Wert
     * @return void
     */
    public function setSessionValue($key, $value)
    {
        if ($this->sessionStarted) {
            $_SESSION[$key] = $value;
        }
    }

    /**
     * Ruft einen Wert aus der Session ab.
     * 
     * @param string $key Schlüssel
     * @return mixed|null Wert oder null, falls nicht vorhanden
     */
    public function getSessionValue($key, $default = null)
    {
        if ($this->sessionStarted && isset($_SESSION[$key])) {
            return $_SESSION[$key];
        }
        return $default;
    }

    /**
     * Beendet die Serversitzung.
     * 
     * @return void
     */
    public function destroySession()
    {
        if ($this->sessionStarted) {
            session_destroy();
            $this->sessionStarted = false;
        }
    }
}