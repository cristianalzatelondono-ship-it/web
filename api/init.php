<?php
ini_set('display_errors','0'); // los avisos de PHP no deben romper el JSON
// Conexión a MySQL (valores por defecto de XAMPP) y utilidades de la API JSON.
const DB_HOST='localhost', DB_NAME='ecoalerta', DB_USER='root', DB_PASS='';
session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax']);session_start();
header('Content-Type: application/json; charset=utf-8');
class UserErr extends Exception{}
// La base de datos usa nombres en español; la API los traduce a las claves que lee el JavaScript.
const ES_EN=['titulo'=>'title','descripcion'=>'description','ubicacion_texto'=>'location_text','latitud'=>'lat','longitud'=>'lng','foto'=>'photo','estado'=>'status','fecha_reporte'=>'reported_on','creado_en'=>'created_at','estado_anterior'=>'old_status','estado_nuevo'=>'new_status','nota'=>'note','nombre'=>'name','correo'=>'email','rol'=>'role','icono'=>'icon','lugar'=>'location','organizador'=>'organizer','como_participar'=>'how_to','fecha_evento'=>'event_date'];
function en($x){if(!is_array($x))return $x;$r=[];foreach($x as $k=>$v)$r[is_string($k)?(ES_EN[$k]??$k):$k]=en($v);return $r;}
function out($d,$c=200){http_response_code($c);echo json_encode(en($d),JSON_UNESCAPED_UNICODE);exit;}
function fail($m,$c=400){out(['error'=>$m],$c);}
function bad($m){throw new UserErr($m);}
set_exception_handler(function($x){$x instanceof UserErr?fail($x->getMessage()):fail('Error interno del servidor.',500);});
try{$pdo=new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);}
catch(PDOException $x){fail('Sin conexión con la base de datos. Inicia MySQL en XAMPP e importa sql/ecoalerta.sql.',500);}
$post=$_SERVER['REQUEST_METHOD']==='POST';
if($post&&($_SERVER['HTTP_X_REQUESTED_WITH']??'')!=='fetch')fail('Solicitud no válida.');   // defensa CSRF
const ST=['pendiente','en_revision','validado','remitido','seguimiento','solucionado','rechazado'];
function me(){return $_SESSION['u']??null;}
function need($roles=null){$u=me();if(!$u)fail('Debes iniciar sesión.',401);if($roles&&!in_array($u['role'],$roles,true))fail('No tienes permiso para esta acción.',403);return $u;}
function body(){return json_decode(file_get_contents('php://input'),true)?:[];}
function up($f){ // subida segura: tipo real, tamaño máx. 5 MB, nombre aleatorio
 if(!$f||$f['error']===UPLOAD_ERR_NO_FILE)return null;
 if($f['error']!==0||$f['size']>5242880)bad('La imagen debe pesar máximo 5 MB.');
 $x=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name'])]??null;
 if(!$x)bad('Solo se permiten imágenes JPG, PNG o WebP.');
 $n=bin2hex(random_bytes(12)).".$x";if(!move_uploaded_file($f['tmp_name'],__DIR__.'/../uploads/'.$n))bad('No se pudo guardar la imagen en el servidor.');return $n;}
