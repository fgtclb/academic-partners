CREATE TABLE pages
	partner_name varchar(255) DEFAULT '' NOT NULL,
	partner_abbreviation varchar(255) DEFAULT '' NOT NULL,
	link varchar(255) DEFAULT '' NOT NULL,
	address_street varchar(255) DEFAULT '' NOT NULL,
	address_street_number varchar(8) DEFAULT '' NOT NULL,
	address_zip varchar(20) DEFAULT '' NOT NULL,
	address_city varchar(255) DEFAULT '' NOT NULL,
	address_country varchar(2) DEFAULT '' NOT NULL,
	address_additional text,
	geocode_longitude VARCHAR(20) DEFAULT NULL,
	geocode_latitude VARCHAR(20) DEFAULT NULL,
	geocode_last_run int(11) DEFAULT NULL,
	geocode_status varchar(40) DEFAULT '' NOT NULL,
	geocode_message varchar(255) DEFAULT '' NOT NULL,
	show_on_map tinyint(1) unsigned DEFAULT '1' NOT NULL
);
