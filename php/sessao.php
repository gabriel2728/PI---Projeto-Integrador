<?php
// Inicia a sessão com cookie protegido (HttpOnly, SameSite) e expiração por inatividade.
// Deve ser incluído no lugar de session_start() em todas as páginas.
require_once __DIR__ . '/seguranca.php';
iniciarSessaoSegura();
