sub detect_admin {
   unset req.http.is-admin;
   unset req.http.is-admin-user;
   unset req.http.is-local-user;

   if (req.url ~ "^(/index.php)?/panel") {
      set req.http.is-admin = "1";
   }
   
   if (req.http.X-Real-Ip) {
      if (req.http.X-Real-Ip ~ "^(88\.20\.237\.56)$") {
         set req.http.is-admin-user = 1;
      }
      if (req.http.X-Real-Ip ~ "^(88\.20\.237\.56)$") {
         set req.http.is-local-user = 1;
      }
   }
}