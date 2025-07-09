<?php
class User {
    public $usuario;
    public $clave;

    public function __construct($usuario, $clave) {
        $this->usuario = $usuario;
        $this->clave = $clave;
    }
}
?>