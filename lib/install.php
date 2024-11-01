<?php

global $wpdb;

$sql_files = 'CREATE TABLE IF NOT EXISTS '.$wpdb->prefix.'rapid_files (
				  ident INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
				  fileid INTEGER UNSIGNED NOT NULL DEFAULT 0,
				  downloads INTEGER UNSIGNED NOT NULL DEFAULT 0,
				  lastdownload INTEGER UNSIGNED NOT NULL DEFAULT 0,
				  filename VARCHAR(255) NOT NULL DEFAULT "",
				  size INTEGER UNSIGNED NOT NULL DEFAULT 0,
				  killcode TEXT,
				  uploadtime INTEGER UNSIGNED NOT NULL DEFAULT 0,
				  PRIMARY KEY(ident),
				  INDEX fileid(fileid)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8';
$sql_links = 'CREATE TABLE IF NOT EXISTS '.$wpdb->prefix.'rapid_links (
				  ident INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
				  fileid INTEGER UNSIGNED NOT NULL DEFAULT 0,
				  short VARCHAR(45) NOT NULL DEFAULT "",
				  PRIMARY KEY(ident),
				  INDEX Index_2(fileid)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8';
$wpdb->query($sql_files);
$wpdb->query($sql_links);