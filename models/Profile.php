<?php
class Profile {
    public $tipo, $host, $puerto, $base, $usuario, $clave;

    public function __construct($tipo, $host, $puerto, $base, $usuario, $clave) {
        $this->tipo = $tipo;
        $this->host = $host;
        $this->puerto = $puerto;
        $this->base = $base;
        $this->usuario = $usuario;
        $this->clave = $clave;
    }
}
?>