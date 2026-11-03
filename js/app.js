// Utilidades compartidas: llamadas a la API, creación segura de elementos, menú y pie.
const ST={pendiente:'Esperando verificación',en_revision:'En revisión',validado:'Validado',remitido:'Remitido a una entidad',seguimiento:'En seguimiento',solucionado:'Solucionado',rechazado:'Rechazado'};
const PUB=['validado','remitido','seguimiento','solucionado'];
const $=s=>document.querySelector(s);
function el(t,p={},...k){const n=document.createElement(t);for(const[a,v]of Object.entries(p)){if(a==='class')n.className=v;else if(a==='text')n.textContent=v;else n.setAttribute(a,v)}n.append(...k);return n}
async function api(u,o={}){const h={'X-Requested-With':'fetch'};if(o.json){h['Content-Type']='application/json';o.body=JSON.stringify(o.json);o.method='POST'}o.headers=h;let r,d={};
 try{r=await fetch(u,o);d=await r.json()}catch(e){throw new Error('No hay respuesta del servidor. ¿Está Apache iniciado y abriste la página con http://localhost/ecoalerta/ ?')}
 if(!r.ok)throw new Error(d.error||'Error del servidor');return d}
const badge=s=>el('span',{class:'st st-'+s,text:ST[s]||s});
function flash(m,t='ok'){const b=el('p',{class:t,role:t==='err'?'alert':'status',text:m});$('#c').prepend(b);setTimeout(()=>b.remove(),7000)}
const ready=(async()=>{let me=null;try{me=(await api('api/auth.php?a=me')).user}catch(e){}
 const cur=location.pathname.split('/').pop()||'index.html',n=el('nav',{class:'w'},el('a',{class:'logo',href:'index.html',text:'🌿 EcoAlerta'}));
 const bt=el('button',{class:'tg',type:'button','aria-expanded':'false','aria-controls':'mn','aria-label':'Abrir o cerrar el menú',text:'☰'}),mn=el('div',{class:'mn',id:'mn'});
 const L=[['mapa.html','Mapa'],['reportar.html','Reportar'],['estadisticas.html','Estadísticas'],['actividades.html','Actividades']];
 if(me)L.push(['mis-reportes.html','Mis reportes']);
 if(me&&me.role!=='ciudadano')L.push(['admin.html','Panel']);
 L.forEach(([h,t])=>{const a=el('a',{href:h,text:t});if(h===cur)a.setAttribute('aria-current','page');mn.append(a)});
 if(me){const o=el('a',{href:'#',class:'btn sm out2',text:'Salir'});o.onclick=async e=>{e.preventDefault();await api('api/auth.php?a=logout',{method:'POST'});location='index.html'};mn.append(el('span',{class:'us der',text:me.name}),o)}
 else if(cur!=='login.html')mn.append(el('a',{class:'btn sm out2 der',href:'login.html',text:'Ingresar'}));
 bt.onclick=()=>{const o=mn.classList.toggle('abierto');bt.setAttribute('aria-expanded',o)};n.append(bt,mn);
 $('#cab').append(n);$('#pie').append(el('div',{class:'w'},el('p',{text:'Aviso: publicar un reporte en EcoAlerta no significa que una autoridad lo haya recibido oficialmente.'})));
 const f=sessionStorage.getItem('f');if(f){sessionStorage.removeItem('f');flash(f)}return me})();
