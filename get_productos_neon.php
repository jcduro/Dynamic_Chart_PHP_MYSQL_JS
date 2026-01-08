
 <!-- Welcome to
  
     |  ___|  __ \  |   |  _ \   _ \   
     | |      |   | |   | |   | |   |  
 \   | |      |   | |   | __ <  |   |  
\___/ \____| ____/ \___/ _| \_\\___/   
                                       
  ___|  _ \  __ \  ____|    _ )   _ _| __ \  ____|    \     ___|  
 |     |   | |   | __|     _ \ \   |  |   | __|     _ \  \___ \  
 |     |   | |   | |      ( `  <   |  |   | |      ___ \       | 
\____|\___/ ____/ _____| \___/\/ ___|____/ _____|_/    _\_____/  

  https://jcduro.bexartideas.com/index.php | 2026 | JC Duro Code & Ideas

------------------------------------------------------------------------------- -->



<?php
// get_productos_neon.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . "/conexion.php";

try {
    $sql = "SELECT codigo, nombre, precio, cantidad, img
            FROM productos_neon
            WHERE activo = 1
            ORDER BY id ASC LIMIT 5";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --------- convierte rutas relativas en absolutas ---------
    $baseUrl = 'https://' . $_SERVER['HTTP_HOST'];   // https://tudominio.com
    foreach ($productos as &$p) {
        $p['img'] = $baseUrl . $p['img'];            // /proyectos/… → https://tudominio.com/proyectos/…
    }
    // ---------------------------------------------------------

    echo json_encode(
        [
            'status' => 'ok',
            'data'   => $productos
        ],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

} catch (PDOException $e) {
    error_log('Error en get_productos_neon.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(
        [
            'status'  => 'error',
            'message' => 'Error al obtener productos.'
        ],
        JSON_UNESCAPED_UNICODE
    );
}
