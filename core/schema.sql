CREATE TABLE %BOT_TBL_PREFIX%_user_list (
	n_index INT NOT NULL AUTO_INCREMENT, PRIMARY KEY(n_index), 
	n_user BIGINT, KEY n_user (n_user), 
	n_working INT NOT NULL DEFAULT 0,
	s_description TEXT NOT NULL,
	s_consumer_key TEXT NOT NULL,
	s_consumer_secret TEXT NOT NULL,
	s_user_token TEXT NOT NULL,
	s_user_token_secret TEXT NOT NULL
)
---
CREATE TABLE %BOT_TBL_PREFIX%_tweet (
	n_index BIGINT NOT NULL AUTO_INCREMENT,PRIMARY KEY(n_index), 
	n_user INT, KEY n_user (n_user), FOREIGN KEY (n_user) REFERENCES `%BOT_TBL_PREFIX%_user_list`(n_index) ON DELETE CASCADE, 
	s_data TEXT NOT NULL,
	n_time_start INT NOT NULL DEFAULT 0,
	n_time_end INT NOT NULL DEFAULT 1439,
	n_interval INT NOT NULL DEFAULT 30, 
	n_runcount INT NOT NULL DEFAULT 0
)
---
CREATE TABLE %BOT_TBL_PREFIX%_reply (
	n_index BIGINT NOT NULL AUTO_INCREMENT, PRIMARY KEY(n_index),
	n_user INT, KEY n_user (n_user), FOREIGN KEY (n_user) REFERENCES `%BOT_TBL_PREFIX%_user_list`(n_index) ON DELETE CASCADE, 
	s_trigger_user TEXT NOT NULL,
	s_trigger_text TEXT NOT NULL,
	s_data TEXT NOT NULL,
	n_time_start INT NOT NULL DEFAULT 0,
	n_time_end INT NOT NULL DEFAULT 1439,
	n_runcount INT NOT NULL DEFAULT 0
)
---
CREATE TABLE %BOT_TBL_PREFIX%_randomreply (
	n_index BIGINT NOT NULL AUTO_INCREMENT,PRIMARY KEY(n_index), 
	n_user INT, KEY n_user (n_user), FOREIGN KEY (n_user) REFERENCES `%BOT_TBL_PREFIX%_user_list`(n_index) ON DELETE CASCADE, 
	s_trigger_user TEXT NOT NULL,
	s_data TEXT NOT NULL,
	n_time_start INT NOT NULL DEFAULT 0,
	n_time_end INT NOT NULL DEFAULT 1439,
	n_runcount INT NOT NULL DEFAULT 0
)
---
CREATE TABLE %BOT_TBL_PREFIX%_tl_reply (
	n_index BIGINT NOT NULL AUTO_INCREMENT, PRIMARY KEY(n_index),
	n_user INT, KEY n_user(n_user), FOREIGN KEY (n_user) REFERENCES `%BOT_TBL_PREFIX%_user_list`(n_index) ON DELETE CASCADE, 
	s_trigger_user TEXT NOT NULL,
	s_trigger_text TEXT NOT NULL,
	s_data TEXT NOT NULL,
	n_time_start INT NOT NULL DEFAULT 0,
	n_time_end INT NOT NULL DEFAULT 1439,
	n_runcount INT NOT NULL DEFAULT 0
)
---
CREATE TABLE %BOT_TBL_PREFIX%_time_tweet (
	n_index BIGINT NOT NULL AUTO_INCREMENT, PRIMARY KEY(n_index), 
	n_user INT, KEY n_user(n_user), FOREIGN KEY (n_user) REFERENCES `%BOT_TBL_PREFIX%_user_list`(n_index) ON DELETE CASCADE, 
	n_month INT NOT NULL DEFAULT -1,
	n_date INT NOT NULL DEFAULT -1,
	n_hour INT NOT NULL DEFAULT -1,
	n_minute INT NOT NULL DEFAULT 0,
	s_data TEXT NOT NULL,
	n_runcount INT NOT NULL DEFAULT 0,
	n_lastrun BIGINT NOT NULL DEFAULT 0
)