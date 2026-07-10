<?php
/* Cierre de sesión del usuario.
   Destruye la sesión activa y redirige al usuario a la página de inicio de sesión. */
session_start();
session_destroy();
header("Location: login.php");
exit;