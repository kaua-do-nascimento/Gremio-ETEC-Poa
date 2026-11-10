<?php

require_once __DIR__ . "/config/database.php";

$database = new Database();
$db = $database->connect();

echo "Conexão com o banco realizada com sucesso!";