<?php require 'init.php';$a=$_GET['a']??'me';
if($a==='me')out(['user'=>me()]);
if(!$post)fail('Método no permitido.',405);
if($a==='logout'){$_SESSION=[];session_destroy();out(['ok'=>1]);}
if($a==='password'){$u=need();$b=body();$s=$pdo->prepare('SELECT clave_cifrada FROM perfiles WHERE id=?');$s->execute([$u['id']]);
 if(!password_verify((string)($b['old']??''),(string)$s->fetchColumn()))bad('La contraseña actual no es correcta.');
 if(strlen((string)($b['new']??''))<8)bad('La nueva contraseña debe tener mínimo 8 caracteres.');
 $pdo->prepare('UPDATE perfiles SET clave_cifrada=? WHERE id=?')->execute([password_hash($b['new'],PASSWORD_DEFAULT),$u['id']]);out(['ok'=>1]);}
$b=body();$em=strtolower(trim($b['email']??''));$pw=(string)($b['password']??'');
if(!filter_var($em,FILTER_VALIDATE_EMAIL))bad('Escribe un correo válido.');
if($a==='register'){$n=trim($b['name']??'');
 if(mb_strlen($n)<2||mb_strlen($n)>80)bad('El nombre debe tener entre 2 y 80 caracteres.');
 if(strlen($pw)<8)bad('La contraseña debe tener mínimo 8 caracteres.');
 $s=$pdo->prepare('SELECT 1 FROM perfiles WHERE correo=?');$s->execute([$em]);if($s->fetch())bad('Ese correo ya está registrado.');
 $pdo->prepare('INSERT INTO perfiles(nombre,correo,clave_cifrada) VALUES(?,?,?)')->execute([$n,$em,password_hash($pw,PASSWORD_DEFAULT)]);}
$s=$pdo->prepare('SELECT * FROM perfiles WHERE correo=?');$s->execute([$em]);$u=$s->fetch();
if(!$u||!password_verify($pw,$u['clave_cifrada']))bad('Correo o contraseña incorrectos.');
session_regenerate_id(true);$_SESSION['u']=['id'=>(int)$u['id'],'name'=>$u['nombre'],'role'=>$u['rol']];out(['user'=>$_SESSION['u']]);
