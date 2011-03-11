CREATE TABLE ngconnect (
  user_id int(11) NOT NULL,
  login_method varchar(100) NOT NULL,
  network_user_id varchar(100) NOT NULL,
  PRIMARY KEY (user_id, login_method, network_user_id),
  KEY ngconnect_lmethod_nuser_id (login_method, network_user_id)
);
