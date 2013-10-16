<?php

try {
    $conn = new PDO('mysql:host=localhost;dbname=online-store', 'root', '');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo 'ERROR: ' . $e->getMessage();
}

if($conn)
{
echo 'Connection the database successfully made.';
}
else {
	echo 'Could not connect to database';
}
?>