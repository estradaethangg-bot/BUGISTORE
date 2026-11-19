<?php
session_start();
header('Content-Type: text/plain; charset=utf-8');
if (!empty($_SESSION['user'])) {
    echo 'Hola, ' . trim(str_replace(["\r", "\n"], '', (string)$_SESSION['user']));
} else {
    echo 'Inicia sesión';
}
