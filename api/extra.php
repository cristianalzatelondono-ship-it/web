<?php require 'init.php';$a=$_GET['a']??'';
if($a==='categories')out(['categories'=>$pdo->query('SELECT id,nombre,color,icono FROM categorias_ambientales')->fetchAll()]);
if($a==='stats'){$n=fn($q)=>(int)$pdo->query($q)->fetchColumn();
 $P="estado IN ('validado','remitido','seguimiento','solucionado')"; // solo reportes verificados
 out(['total'=>$n("SELECT COUNT(*) FROM reportes WHERE $P"),'activities'=>$n('SELECT COUNT(*) FROM actividades_comunitarias'),'participants'=>$n('SELECT COUNT(*) FROM participantes_actividad'),
 'solved'=>$n("SELECT COUNT(DISTINCT r.id) FROM reportes r JOIN evidencias_reporte e ON e.reporte_id=r.id WHERE r.estado='solucionado'"),
 'by_cat'=>$pdo->query("SELECT c.nombre,c.color,COUNT(r.id) n FROM categorias_ambientales c LEFT JOIN reportes r ON r.categoria_id=c.id AND r.$P GROUP BY c.id")->fetchAll(),
 'by_status'=>$pdo->query("SELECT estado,COUNT(*) n FROM reportes WHERE $P GROUP BY estado")->fetchAll(),
 'by_month'=>$pdo->query("SELECT DATE_FORMAT(creado_en,'%Y-%m') m,COUNT(*) n FROM reportes WHERE $P GROUP BY m ORDER BY m")->fetchAll()]);}
if($a==='activities'){$u=me();$s=$pdo->prepare('SELECT a.id,a.titulo,a.descripcion,a.fecha_evento,a.lugar,a.organizador,a.como_participar,(SELECT COUNT(*) FROM participantes_actividad WHERE actividad_id=a.id) n,EXISTS(SELECT 1 FROM participantes_actividad WHERE actividad_id=a.id AND usuario_id=?) mine FROM actividades_comunitarias a ORDER BY fecha_evento DESC');$s->execute([$u['id']??0]);out(['activities'=>$s->fetchAll()]);}
if($a==='users'){need(['administrador']);out(['users'=>$pdo->query('SELECT id,nombre,correo,rol FROM perfiles ORDER BY id')->fetchAll()]);}
if(!$post)fail('Acción desconocida.',404);
if($a==='join'){$u=need();$i=(int)(body()['id']??0);
 $s=$pdo->prepare('SELECT 1 FROM actividades_comunitarias WHERE id=?');$s->execute([$i]);if(!$s->fetch())fail('Actividad no encontrada.',404);
 $s=$pdo->prepare('SELECT 1 FROM participantes_actividad WHERE actividad_id=? AND usuario_id=?');$s->execute([$i,$u['id']]);
 if($s->fetch())$pdo->prepare('DELETE FROM participantes_actividad WHERE actividad_id=? AND usuario_id=?')->execute([$i,$u['id']]);else $pdo->prepare('INSERT INTO participantes_actividad(actividad_id,usuario_id) VALUES(?,?)')->execute([$i,$u['id']]);out(['ok'=>1]);}
if($a==='activity_new'){$u=need(['moderador','administrador']);$b=body();$f=[];
 foreach(['title'=>120,'description'=>2000,'event_date'=>30,'location'=>160,'organizer'=>100,'how_to'=>255] as $k=>$m){$v=trim($b[$k]??'');if($v===''||mb_strlen($v)>$m)bad('Completa todos los campos (revisa los límites de longitud).');$f[]=$v;}
 if(strtotime($f[2])===false)bad('La fecha no es válida.');$f[2]=date('Y-m-d H:i:s',strtotime($f[2]));
 $pdo->prepare('INSERT INTO actividades_comunitarias(titulo,descripcion,fecha_evento,lugar,organizador,como_participar,creado_por) VALUES(?,?,?,?,?,?,?)')->execute([...$f,$u['id']]);out(['ok'=>1]);}
if($a==='reset'){$u=need(['administrador']);$id=(int)(body()['id']??0);if($id===$u['id'])bad('No puedes restablecer tu propia clave.');
 $t=substr(bin2hex(random_bytes(6)),0,10);$s=$pdo->prepare('UPDATE perfiles SET clave_cifrada=? WHERE id=?');$s->execute([password_hash($t,PASSWORD_DEFAULT),$id]);
 if(!$s->rowCount())fail('Usuario no encontrado.',404);out(['temp'=>$t]);}
if($a==='role'){$u=need(['administrador']);$b=body();$id=(int)($b['id']??0);
 if(!in_array($b['role']??'',['ciudadano','moderador','administrador'],true)||$id===$u['id'])bad('No se puede aplicar ese cambio.');
 $pdo->prepare('UPDATE perfiles SET rol=? WHERE id=?')->execute([$b['role'],$id]);out(['ok'=>1]);}
fail('Acción desconocida.',404);
