RewriteEngine on
RewriteCond %{HTTP_HOST} ^(.*)abc.com [NC]
RewriteRule ^(.*)$ http://xyz.com [R=301,L]
