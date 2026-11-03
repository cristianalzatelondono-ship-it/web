ECOALERTA (HTML + CSS + JavaScript + PHP/MySQL en XAMPP)
1. Inicia Apache y MySQL en el panel de XAMPP.
2. Copia la carpeta "ecoalerta" a C:\xampp\htdocs\
3. http://localhost/phpmyadmin -> Importar -> sql/ecoalerta.sql
4. Abre http://localhost/ecoalerta/index.html  (NO con doble clic)
5. Regístrate y, para ser administrador, en phpMyAdmin (SQL):
   UPDATE perfiles SET rol='administrador' WHERE correo='TU_CORREO';
6. Si MySQL tiene clave, edita DB_PASS en api/init.php
Estructura: *.html (páginas) | css/ | js/ | api/ (PHP que consulta MySQL y responde JSON) | sql/ | uploads/
