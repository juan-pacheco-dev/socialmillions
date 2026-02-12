<?php
$conn = mysqli_connect('localhost', 'root', '', '');
if (!$conn)
    die("Connection failed: " . mysqli_connect_error());

$res = mysqli_query($conn, "SHOW DATABASES");
while ($row = mysqli_fetch_row($res)) {
    $db = $row[0];
    if (in_array($db, ['information_schema', 'mysql', 'performance_schema', 'phpmyadmin']))
        continue;

    $c = @mysqli_connect('localhost', 'root', '', $db);
    if (!$c)
        continue;

    // Check if table exists
    $tableCheck = @mysqli_query($c, "SHOW TABLES LIKE 'usuarios'");
    if ($tableCheck && mysqli_num_rows($tableCheck) > 0) {
        $q = @mysqli_query($c, "SELECT id, nombre FROM usuarios WHERE id IN (42, 43)");
        if ($q && mysqli_num_rows($q) > 0) {
            while ($u = mysqli_fetch_assoc($q)) {
                echo "FOUND User " . $u['id'] . " in DB: $db (Name: " . $u['nombre'] . ")\n";
            }
        }
    }
    mysqli_close($c);
}
?>