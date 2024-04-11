#
# Table structure for table 'tx_events2_domain_model_event'
#
CREATE TABLE tx_events2_domain_model_event
(
	release_date     int(11) DEFAULT '0',
	social_teaser    varchar(255) DEFAULT '',
	theater_details  text,
	deadline         int(11) DEFAULT '0' NOT NULL,
	reserve_period   int(11) DEFAULT '0' NOT NULL
);

#
# Table structure for table 'tx_reserve_domain_model_period'
#
CREATE TABLE tx_reserve_domain_model_period
(
	events2_event int(11) DEFAULT '0' NOT NULL
);
