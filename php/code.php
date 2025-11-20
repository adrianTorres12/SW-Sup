<?php
include "connecction.php";

function generarCodigoUnico($conn) {
    do {

        $codigo = random_int(1000, 9999);

        $stmt = $conn->prepare("SELECT COUNT(*) FROM speaking WHERE code = ?");
        $stmt->bind_param("s", $codigo);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();

    } while ($count > 0); // repetir si ya existe

    return $codigo;
}
