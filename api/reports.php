<?php require 'init.php';$a=$_GET['a']??'list';
if($a==='list'){$u=me();$w=['1=1'];$p=[];
 if(!empty($_GET['mine'])){$u=need();$w[]='r.usuario_id='.(int)$u['id'];}
 elseif(!(!empty($_GET['all'])&&$u&&$u['role']!=='ciudadano'))$w[]="r.estado IN ('validado','remitido','seguimiento','solucionado')";
 if(!empty($_GET['c'])){$w[]='r.categoria_id=?';$p[]=(int)$_GET['c'];}
 if(!empty($_GET['s'])&&in_array($_GET['s'],ST,true)){$w[]='r.estado=?';$p[]=$_GET['s'];}
 $q=trim($_GET['q']??'');if($q!==''){$w[]='(r.titulo LIKE ? OR r.ubicacion_texto LIKE ?)';$p[]="%$q%";$p[]="%$q%";}
 $l=min(200,max(1,(int)($_GET['limit']??200)));
 $s=$pdo->prepare("SELECT r.id,r.titulo,r.estado,r.ubicacion_texto,r.creado_en,ROUND(r.latitud,3) latitud,ROUND(r.longitud,3) longitud,c.nombre cat,c.color,c.icono,p.nombre author FROM reportes r JOIN categorias_ambientales c ON c.id=r.categoria_id JOIN perfiles p ON p.id=r.usuario_id WHERE ".implode(' AND ',$w)." ORDER BY r.id DESC LIMIT $l");$s->execute($p);out(['reports'=>$s->fetchAll()]);}
if($a==='get'){$id=(int)($_GET['id']??0);$u=me();
 $s=$pdo->prepare('SELECT r.id,r.usuario_id,r.titulo,r.descripcion,r.ubicacion_texto,ROUND(r.latitud,3) latitud,ROUND(r.longitud,3) longitud,r.foto,r.estado,r.fecha_reporte,r.creado_en,c.nombre cat,c.icono,p.nombre author FROM reportes r JOIN categorias_ambientales c ON c.id=r.categoria_id JOIN perfiles p ON p.id=r.usuario_id WHERE r.id=?');$s->execute([$id]);$r=$s->fetch();
 if(!$r||(!in_array($r['estado'],['validado','remitido','seguimiento','solucionado'],true)&&!($u&&($u['role']!=='ciudadano'||$u['id']==$r['usuario_id']))))fail('Reporte no encontrado.',404);
 unset($r['usuario_id']);
 $h=$pdo->prepare('SELECT h.estado_anterior,h.estado_nuevo,h.nota,h.creado_en,p.nombre FROM historial_estados h JOIN perfiles p ON p.id=h.cambiado_por WHERE reporte_id=? ORDER BY h.id');$h->execute([$id]);
 $e=$pdo->prepare('SELECT nota,foto,creado_en FROM evidencias_reporte WHERE reporte_id=? ORDER BY id');$e->execute([$id]);
 out(['report'=>$r,'history'=>$h->fetchAll(),'evidence'=>$e->fetchAll()]);}
if(!$post)fail('Método no permitido.',405);
if($a==='create'){$u=need();
 $t=trim($_POST['title']??'');$d=trim($_POST['description']??'');$c=(int)($_POST['category']??0);$f=$_POST['date']??'';
 $loc=trim($_POST['location_text']??'')?:null;$la=$_POST['lat']??'';$ln=$_POST['lng']??'';
 if(mb_strlen($t)<5||mb_strlen($t)>120)bad('El título debe tener entre 5 y 120 caracteres.');
 if(mb_strlen($d)<20||mb_strlen($d)>2000)bad('La descripción debe tener entre 20 y 2000 caracteres.');
 $s=$pdo->prepare('SELECT 1 FROM categorias_ambientales WHERE id=?');$s->execute([$c]);if(!$s->fetch())bad('Elige una categoría.');
 $dt=DateTime::createFromFormat('Y-m-d',$f);if(!$dt||$dt>new DateTime('today'))bad('La fecha no es válida ni puede ser futura.');
 if($loc&&mb_strlen($loc)>160)bad('La referencia de ubicación es muy larga.');
 if($la===''||$ln==='')$la=$ln=null;elseif(!is_numeric($la)||!is_numeric($ln)||abs($la)>90||abs($ln)>180)bad('Coordenadas no válidas.');
 if(empty($_POST['ok']))bad('Debes confirmar que la información es veraz.');
 $s=$pdo->prepare('SELECT 1 FROM reportes WHERE usuario_id=? AND titulo=? AND categoria_id=? AND creado_en>NOW()-INTERVAL 1 DAY');$s->execute([$u['id'],$t,$c]);
 if($s->fetch())bad('Ya enviaste un reporte igual en las últimas 24 horas.');
 $ph=up($_FILES['photo']??null);$pdo->beginTransaction();
 $pdo->prepare('INSERT INTO reportes(usuario_id,categoria_id,titulo,descripcion,ubicacion_texto,latitud,longitud,foto,fecha_reporte) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$u['id'],$c,$t,$d,$loc,$la,$ln,$ph,$f]);$id=(int)$pdo->lastInsertId();
 $pdo->prepare("INSERT INTO historial_estados(reporte_id,estado_anterior,estado_nuevo,nota,cambiado_por) VALUES(?,NULL,'pendiente','Reporte creado',?)")->execute([$id,$u['id']]);$pdo->commit();out(['id'=>$id]);}
if($a==='status'){$u=need(['moderador','administrador']);$b=body();$id=(int)($b['id']??0);$n=$b['status']??'';$note=trim($b['note']??'');
 if(!in_array($n,ST,true))bad('Estado no válido.');
 $s=$pdo->prepare('SELECT estado FROM reportes WHERE id=?');$s->execute([$id]);$old=$s->fetchColumn();if($old===false)fail('Reporte no encontrado.',404);
 if(in_array($n,['rechazado','solucionado'])&&mb_strlen($note)<10)bad('Escribe una justificación de al menos 10 caracteres.');
 if($n==='solucionado'){$c=$pdo->prepare('SELECT COUNT(*) FROM evidencias_reporte WHERE reporte_id=?');$c->execute([$id]);if(!$c->fetchColumn())bad('Para marcar como solucionado debes registrar evidencia antes.');}
 $pdo->beginTransaction();$pdo->prepare('UPDATE reportes SET estado=? WHERE id=?')->execute([$n,$id]);
 $pdo->prepare('INSERT INTO historial_estados(reporte_id,estado_anterior,estado_nuevo,nota,cambiado_por) VALUES(?,?,?,?,?)')->execute([$id,$old,$n,mb_substr($note,0,500)?:null,$u['id']]);$pdo->commit();out(['ok'=>1]);}
if($a==='evidence'){$u=need(['moderador','administrador']);$id=(int)($_POST['id']??0);$note=trim($_POST['note']??'');
 if(mb_strlen($note)<10||mb_strlen($note)>500)bad('La evidencia debe tener entre 10 y 500 caracteres.');
 $s=$pdo->prepare('SELECT 1 FROM reportes WHERE id=?');$s->execute([$id]);if(!$s->fetch())fail('Reporte no encontrado.',404);
 $pdo->prepare('INSERT INTO evidencias_reporte(reporte_id,nota,foto,agregado_por) VALUES(?,?,?,?)')->execute([$id,$note,up($_FILES['photo']??null),$u['id']]);out(['ok'=>1]);}
fail('Acción desconocida.',404);
