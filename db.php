<?php
// db.php

$host    = "localhost";
$dbname  = "Hestimsmart";
$user    = "root";
$pass    = "root";
$charset = "utf8mb4";

$dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // erreurs en exceptions
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // fetch en tableau associatif
    PDO::ATTR_EMULATE_PREPARES   => false,                  // vraies requêtes préparées
];

try {
    $conn = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . htmlspecialchars($e->getMessage()));
}
