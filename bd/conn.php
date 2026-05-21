
<?php
// config/database.php

$host = "localhost";
$dbname = "consulsoft";
$username = "root";
$password = ""; // por defecto en XAMPP

try {
    // Crear conexión PDO
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);

    // Configurar atributos
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Opcional: mensaje de prueba
    // echo "Conexión exitosa";

} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>

<!-- git commit -m "first commit"
git branch -M main
git remote add origin https://github.com/Sebas-091202/Debia-Smart-Inventory-2.git
git push -u origin main -->