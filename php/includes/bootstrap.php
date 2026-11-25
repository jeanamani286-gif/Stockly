<?php
session_set_cookie_params(['httponly'=>true,'samesite'=>'Strict']);
session_start();
function db(): PDO {
  static $pdo = null;
  if (!$pdo) $pdo = new PDO('mysql:host=localhost;dbname=boutique;charset=utf8mb4','root','',
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES=>false]);
  return $pdo;
}
function json_out($data, int $code=200): never {
  http_response_code($code); header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE); exit;
}
function login(string $email, string $pwd): bool {
  $st = db()->prepare('SELECT * FROM utilisateurs WHERE email=? AND actif=1'); $st->execute([$email]);
  $u = $st->fetch(PDO::FETCH_ASSOC);
  if (!$u || !password_verify($pwd, $u['mot_de_passe'])) return false;
  session_regenerate_id(true); $_SESSION['uid']=(int)$u['id']; $_SESSION['role_id']=(int)$u['role_id']; return true;
}
function require_perm(string $code): int {
  if (empty($_SESSION['uid'])) json_out(['erreur'=>'Non authentifié'], 401);
  $st = db()->prepare('SELECT 1 FROM role_permissions rp JOIN permissions p ON p.id=rp.permission_id WHERE rp.role_id=? AND p.code=?');
  $st->execute([$_SESSION['role_id'], $code]);
  if (!$st->fetch()) json_out(['erreur'=>'Accès refusé'], 403);
  return $_SESSION['uid'];
}
