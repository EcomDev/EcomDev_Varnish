probe healthcheck {
      .url = "/status";
      .interval = 60s;
      .timeout = 0.3 s;
      .window = 8;
      .threshold = 3;
      .initial = 3;
      .expected_response = 200;
}

backend node1 {
    .host = "127.0.0.1";
    .port = "8080";
    .probe = healthcheck;
    .first_byte_timeout = 300s;
    .connect_timeout = 5s;
    .between_bytes_timeout = 2s;
}

backend node2 {
    .host = "127.0.0.1";
    .port = "8080";
    .probe = healthcheck;
    .first_byte_timeout = 300s;
    .connect_timeout = 5s;
    .between_bytes_timeout = 2s;
}

backend admin {
    .host = "127.0.0.1";
    .port = "http";
    .first_byte_timeout = 6000s;
    .connect_timeout = 1000s;
    .between_bytes_timeout = 2s;
}

director balancer client {
    { 
      .backend = node1; 
      .weight = 1;
    }
    { 
      .backend = node2; 
      .weight = 1;
    }
}