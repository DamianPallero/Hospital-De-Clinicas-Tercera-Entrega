<?php
class Captcha {
    public static function generar() {
        $num1 = rand(1, 10);
        $num2 = rand(1, 10);
        $operaciones = ['+', '-'];
        $operacion = $operaciones[array_rand($operaciones)];
        
        switch ($operacion) {
            case '+':
                $respuesta = $num1 + $num2;
                break;
            case '-':
                if ($num1 < $num2) {
                    $temp = $num1;
                    $num1 = $num2;
                    $num2 = $temp;
                }
                $respuesta = $num1 - $num2;
                break;
        }
        
        $pregunta = "$num1 $operacion $num2";
        $_SESSION['captcha_respuesta'] = $respuesta;
        $_SESSION['captcha_tiempo'] = time();
        
        return $pregunta;
    }
    
    public static function validar($respuesta_usuario) {
        if (!isset($_SESSION['captcha_respuesta']) || !isset($_SESSION['captcha_tiempo'])) {
            return false;
        }
        
        if (time() - $_SESSION['captcha_tiempo'] > 300) {
            unset($_SESSION['captcha_respuesta'], $_SESSION['captcha_tiempo']);
            return false;
        }
        
        $respuesta_correcta = $_SESSION['captcha_respuesta'];
        unset($_SESSION['captcha_respuesta'], $_SESSION['captcha_tiempo']);
        
        return (int)$respuesta_usuario === (int)$respuesta_correcta;
    }
}