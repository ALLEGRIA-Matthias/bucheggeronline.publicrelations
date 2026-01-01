#
# Table structure for table 'tx_publicrelations_domain_model_client'
#
CREATE TABLE tx_publicrelations_domain_model_client (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	name tinytext,
	short_name tinytext,
	also_known_as tinytext,
	shortinfo tinytext,
	top tinyint(4) DEFAULT '0' NOT NULL,
	archive tinyint(4) DEFAULT '0' NOT NULL,
	sort int(11) unsigned DEFAULT '0' NOT NULL,
	description mediumtext,
	types int(11) unsigned DEFAULT '0' NOT NULL,
	logo int(11) unsigned NOT NULL default '0',
	since date DEFAULT NULL,
	until date DEFAULT NULL,
	phone tinytext,
	email tinytext,
	slug varchar(2048),
	slug_locked tinyint(4) DEFAULT '0' NOT NULL,
	seo_title tinytext,
	seo_description mediumtext,
	location int(11) unsigned DEFAULT '0',
	links int(11) unsigned DEFAULT '0' NOT NULL,
	contacts int(11) unsigned DEFAULT '0' NOT NULL,
	internal_notes mediumtext,
	activities int(11) unsigned DEFAULT '0' NOT NULL,
	campaigns int(11) unsigned DEFAULT '0' NOT NULL,
	news int(11) unsigned DEFAULT '0' NOT NULL,
	mediagroups int(11) unsigned DEFAULT '0' NOT NULL,
	events int(11) unsigned DEFAULT '0' NOT NULL,
	covers int(11) unsigned DEFAULT '0' NOT NULL,
	access_rights int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY t3ver_oid (t3ver_oid,t3ver_wsid),
	KEY language (sys_language_uid,l10n_parent)

);

#
# Table structure for table 'tx_publicrelations_domain_model_campaign'
#
CREATE TABLE tx_publicrelations_domain_model_campaign (

	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	client int(11) unsigned DEFAULT '0' NOT NULL,
	type int(11) unsigned DEFAULT '0' NOT NULL,

	title tinytext,
	subtitle tinytext,
	also_known_as tinytext,
	description mediumtext,
	archive_date int(11) DEFAULT '0' NOT NULL,
	location_manual tinytext,
	location_note tinytext,
	openend tinyint(4) DEFAULT '0' NOT NULL,
	slug varchar(2048),
	slug_locked tinyint(4) DEFAULT '0' NOT NULL,
	seo_title tinytext,
	seo_description mediumtext,
	location int(11) unsigned DEFAULT '0',
	events int(11) unsigned DEFAULT '0' NOT NULL,
	links int(11) unsigned DEFAULT '0' NOT NULL,
	contacts int(11) unsigned DEFAULT '0' NOT NULL,
	internal_notes mediumtext,
	news int(11) unsigned DEFAULT '0' NOT NULL,
	mediagroups int(11) unsigned DEFAULT '0' NOT NULL,
	logo int(11) unsigned DEFAULT '0' NOT NULL,
	covers int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY t3ver_oid (t3ver_oid,t3ver_wsid),
	KEY language (sys_language_uid,l10n_parent)

);

#
# Table structure for table 'tx_publicrelations_domain_model_news'
#
CREATE TABLE tx_publicrelations_domain_model_news (

	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	type int(11) unsigned DEFAULT '0' NOT NULL,
	date date DEFAULT NULL,
	retention_date int(11) DEFAULT '0' NOT NULL,
	retention_info mediumtext,
	title tinytext,
	text mediumtext,
	slug varchar(2048),
	slug_locked tinyint(4) DEFAULT '0' NOT NULL,
	seo_title tinytext,
	seo_description mediumtext,
	client int(11) unsigned DEFAULT '0',
	content_elements int(11) unsigned DEFAULT '0' NOT NULL,
	media int(11) unsigned DEFAULT '0' NOT NULL,
	links int(11) unsigned DEFAULT '0' NOT NULL,
	contacts int(11) unsigned DEFAULT '0' NOT NULL,
	internal_notes mediumtext,
	campaigns int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY t3ver_oid (t3ver_oid,t3ver_wsid),
	KEY language (sys_language_uid,l10n_parent)

);

#
# Table structure for table 'tx_publicrelations_domain_model_event'
#
CREATE TABLE tx_publicrelations_domain_model_event (

	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	client int(11) unsigned DEFAULT '0' NOT NULL,
	partners int(11) unsigned DEFAULT '0' NOT NULL,
	campaign int(11) unsigned DEFAULT '0' NOT NULL,
	accreditations int(11) unsigned DEFAULT '0' NOT NULL,

	title tinytext,
	date int(11) DEFAULT '0' NOT NULL,
	date_fulltime tinyint(4) DEFAULT '0' NOT NULL,
	accreditation int(11) DEFAULT '0' NOT NULL,
	private tinyint(4) DEFAULT '0' NOT NULL,
	canceled tinyint(4) DEFAULT '0' NOT NULL,
	online tinyint(4) DEFAULT '0' NOT NULL,
	overwrite_theaterevent int(11) unsigned DEFAULT '0' NOT NULL,
	duration int(11) DEFAULT '0' NOT NULL,
	duration_approx tinyint(4) DEFAULT '0' NOT NULL,
	duration_with_break tinyint(4) DEFAULT '0' NOT NULL,
	opening tinyint(4) DEFAULT '0' NOT NULL,
	type int(11) unsigned DEFAULT '0' NOT NULL,
	type_overwrite mediumtext,
	notes int(11) unsigned DEFAULT '0' NOT NULL,
	notes_overwrite mediumtext,
	notes_manual mediumtext,
	invitations int(11) unsigned DEFAULT '0' NOT NULL,
	invitation_image int(11) unsigned DEFAULT '0' NOT NULL,
	invitation_logo int(11) unsigned DEFAULT '0' NOT NULL,
	invitation_subject tinytext,
	invitation_from mediumtext,
	invitation_from_personally mediumtext,
	invitation_text mediumtext,
	invitation_text_personally mediumtext,
	invitation_report_stop int(11) DEFAULT '0' NOT NULL,
	invitation_notes_required tinyint(4) DEFAULT '0' NOT NULL,
	invitation_notes_title tinytext,
	invitation_notes_description mediumtext,
	location_note tinytext,
	location int(11) unsigned DEFAULT '0',
	checkin tinyint(4) DEFAULT '0' NOT NULL,
	links int(11) unsigned DEFAULT '0' NOT NULL,
	old_event int(11) unsigned DEFAULT '0',
	new_event int(11) unsigned DEFAULT '0',
	manual_confirmation tinyint(4) DEFAULT '0' NOT NULL,
	tickets_quota int(11) DEFAULT '0' NOT NULL,
	waiting_quota int(11) DEFAULT '0' NOT NULL,
	additional_fields int(11) unsigned DEFAULT '0' NOT NULL,
	logs int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY t3ver_oid (t3ver_oid,t3ver_wsid),
	KEY language (sys_language_uid,l10n_parent)

);

#
# Table structure for table 'tx_publicrelations_event_client_mm'
#
CREATE TABLE tx_publicrelations_event_client_mm (
	uid_local int(11) unsigned DEFAULT '0' NOT NULL,
	uid_foreign int(11) unsigned DEFAULT '0' NOT NULL,
	sorting int(11) unsigned DEFAULT '0' NOT NULL,
	sorting_foreign int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid_local,uid_foreign),
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign)
);

#
# Table structure for table 'tx_publicrelations_domain_model_invitation'
#
CREATE TABLE tx_publicrelations_domain_model_invitation (

	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	event int(11) unsigned DEFAULT '0' NOT NULL,

	type varchar(100) DEFAULT '' NOT NULL,
	title tinytext,
	blank tinyint(4) DEFAULT '0' NOT NULL,
	no_event_overview tinyint(4) DEFAULT '0' NOT NULL,
	image int(11) unsigned DEFAULT '0' NOT NULL,
	no_salutation tinyint(4) DEFAULT '0' NOT NULL,
	no_signature tinyint(4) DEFAULT '0' NOT NULL,
	no_logo tinyint(4) DEFAULT '0' NOT NULL,
	logo int(11) unsigned DEFAULT '0' NOT NULL,
	no_header tinyint(4) DEFAULT '0' NOT NULL,
	header int(11) unsigned NOT NULL default '0',
	feedback_date int(11) DEFAULT NULL,
	subject tinytext,
	from mediumtext,
	from_personally mediumtext,
	contents int(11) unsigned DEFAULT '0' NOT NULL,
	contents_personally int(11) unsigned DEFAULT '0' NOT NULL,
	alt_sender tinytext,
	alt_template tinytext,
	from_name tinytext,
	reply_name tinytext,
	reply_email tinytext,
	invitation_title_overwrite tinytext,
	invitation_subtitle_overwrite tinytext,
	invitation_header_overwrite tinytext,
    variants int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY t3ver_oid (t3ver_oid,t3ver_wsid),
	KEY language (sys_language_uid,l10n_parent)

);

#
# Table structure for table 'tx_publicrelations_domain_model_additionalfield'
#
CREATE TABLE tx_publicrelations_domain_model_additionalfield (

	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	event int(11) unsigned DEFAULT '0',

	position int(11) DEFAULT '0' NOT NULL,
	label tinytext,
	description mediumtext,
	icon tinytext,
	type int(11) DEFAULT '0' NOT NULL,
	options mediumtext,
	required tinyint(4) DEFAULT '0' NOT NULL,
	summary tinyint(4) DEFAULT '0' NOT NULL,
	accreditation tinyint(4) DEFAULT '0' NOT NULL,
	invitation tinyint(4) DEFAULT '0' NOT NULL,
	confirmation tinyint(4) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY t3ver_oid (t3ver_oid,t3ver_wsid),
	KEY language (sys_language_uid,l10n_parent)

);

#
# Table structure for table 'tx_publicrelations_domain_model_additionalanswer'
#
CREATE TABLE tx_publicrelations_domain_model_additionalanswer (

	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	accreditation int(11) unsigned DEFAULT '0',
	field int(11) unsigned DEFAULT '0',

	type int(11) DEFAULT '0' NOT NULL,
	value mediumtext,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY t3ver_oid (t3ver_oid,t3ver_wsid),
	KEY language (sys_language_uid,l10n_parent)

);

#
# Table structure for table 'tx_publicrelations_domain_model_link'
#
CREATE TABLE tx_publicrelations_domain_model_link (

	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	client int(11) unsigned DEFAULT '0' NOT NULL,
	campaign int(11) unsigned DEFAULT '0' NOT NULL,
	news int(11) unsigned DEFAULT '0' NOT NULL,
	event int(11) unsigned DEFAULT '0' NOT NULL,
	mediagroup int(11) unsigned DEFAULT '0' NOT NULL,
	slide int(11) unsigned DEFAULT '0' NOT NULL,
	report int(11) unsigned DEFAULT '0',

	type int(11) unsigned DEFAULT '0' NOT NULL,
	url tinytext,
	title tinytext,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY t3ver_oid (t3ver_oid,t3ver_wsid),
	KEY language (sys_language_uid,l10n_parent)

);

#
# Table structure for table 'tx_publicrelations_domain_model_location'
#
CREATE TABLE tx_publicrelations_domain_model_location (

	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	name tinytext,
	notes mediumtext,
	street tinytext,
	additional tinytext,
	zip tinytext,
	city tinytext,
	country int(11) unsigned DEFAULT '0',
	internal_notes mediumtext,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY t3ver_oid (t3ver_oid,t3ver_wsid),
	KEY language (sys_language_uid,l10n_parent)

);

#
# Table structure for table 'tx_publicrelations_domain_model_mediagroup'
#
CREATE TABLE tx_publicrelations_domain_model_mediagroup (

	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	client int(11) unsigned DEFAULT '0' NOT NULL,
	campaign int(11) unsigned DEFAULT '0' NOT NULL,
	types int(11) unsigned DEFAULT '0' NOT NULL,

	title tinytext,
	media int(11) unsigned DEFAULT '0' NOT NULL,
	links int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY t3ver_oid (t3ver_oid,t3ver_wsid),
	KEY language (sys_language_uid,l10n_parent)

);

#
# Table structure for table 'tx_publicrelations_domain_model_contact'
#
CREATE TABLE tx_publicrelations_domain_model_contact (

	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	client int(11) unsigned DEFAULT '0' NOT NULL,
	campaign int(11) unsigned DEFAULT '0' NOT NULL,
	news int(11) unsigned DEFAULT '0' NOT NULL,

	types int(11) unsigned DEFAULT '0' NOT NULL,
	types_overwrite tinytext,
	staff int(11) unsigned DEFAULT '0',

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY t3ver_oid (t3ver_oid,t3ver_wsid),
	KEY language (sys_language_uid,l10n_parent)

);

#
# Table structure for table 'tx_publicrelations_domain_model_accreditation'
#
CREATE TABLE tx_publicrelations_domain_model_accreditation (

	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	event int(11) unsigned DEFAULT '0',
	status int(11) DEFAULT '0' NOT NULL,
	invitation_type int(11) unsigned DEFAULT '0',
	invitation_status int(11) DEFAULT '0' NOT NULL,
	type int(11) DEFAULT '0' NOT NULL,
	guest_type int(11) DEFAULT '0' NOT NULL,
	facie tinyint(4) DEFAULT '0' NOT NULL,
	guest int(11) unsigned DEFAULT '0',
	gender tinytext,
	title tinytext,
	first_name tinytext,
	middle_name tinytext,
	last_name tinytext,
	email tinytext,
	phone tinytext,
	request_note mediumtext,
	dsgvo tinyint(4) DEFAULT '0' NOT NULL,
	ip tinytext,
	medium tinytext,
	medium_type int(11) unsigned DEFAULT '0' NOT NULL,
	photographer tinyint(4) DEFAULT '0' NOT NULL,
	camerateam tinyint(4) DEFAULT '0' NOT NULL,
	opened int(11) DEFAULT '0' NOT NULL,
	tickets_wish int(11) DEFAULT '0' NOT NULL,
	tickets_approved int(11) DEFAULT '0' NOT NULL,
	tickets_received int(11) DEFAULT '0' NOT NULL,
	notes_received mediumtext,
	locking_be_user_uid tinyint(4) DEFAULT '0' NOT NULL,
	locking_tstamp int(11) DEFAULT '0' NOT NULL,
	program int(11) DEFAULT '0' NOT NULL,
	pass int(11) DEFAULT '0' NOT NULL,
	seats mediumtext,
	tickets mediumtext,
	notes mediumtext,
	notes_select int(11) unsigned DEFAULT '0' NOT NULL,
	additional_answers int(11) unsigned DEFAULT '0' NOT NULL,
	logs int(11) unsigned DEFAULT '0' NOT NULL,
	internal_notes mediumtext,
	duplicate_of int(11) DEFAULT '0' NOT NULL,
	is_master tinyint(4) DEFAULT '0' NOT NULL,
	ignored_duplicates mediumtext,
	distribution_job int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY t3ver_oid (t3ver_oid,t3ver_wsid),
	KEY language (sys_language_uid,l10n_parent)

);

#
# Table structure for table 'tx_publicrelations_domain_model_log'
#
CREATE TABLE tx_publicrelations_domain_model_log (

	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	accreditation int(11) unsigned DEFAULT '0' NOT NULL,
	event int(11) unsigned DEFAULT '0' NOT NULL,
	address int(11) unsigned DEFAULT '0' NOT NULL,
	mailing int(11) unsigned DEFAULT '0' NOT NULL,
	mail int(11) unsigned DEFAULT '0' NOT NULL,
	report int(11) unsigned DEFAULT '0',
	cruser_id int(11) unsigned DEFAULT '0' NOT NULL,

	code tinytext,
	function tinytext,
	subject tinytext,
	notes mediumtext,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY t3ver_oid (t3ver_oid,t3ver_wsid),
	KEY language (sys_language_uid,l10n_parent)

);

#
# Table structure for table 'sys_category'
#
CREATE TABLE sys_category (

	icon int(11) unsigned NOT NULL default '0',
	svg tinytext,
	plural tinytext,
	css_class tinytext,
	schedule tinyint(4) DEFAULT '0' NOT NULL,
	theaterevent tinyint(4) DEFAULT '0' NOT NULL,
	client int(11) unsigned DEFAULT '0' NOT NULL,

);

#
# Table structure for table 'tt_address'
#
CREATE TABLE tt_address (
	client int(11) unsigned DEFAULT '0' NOT NULL,
	contact_types int(11) unsigned DEFAULT '0' NOT NULL,
	working_for text,
	personally tinyint(4) DEFAULT '0' NOT NULL,
	special_title tinytext,
	mailing_exclude tinyint(4) DEFAULT '0' NOT NULL,
	mailing_no_html tinyint(4) DEFAULT '0' NOT NULL,
	logs int(11) unsigned DEFAULT '0' NOT NULL,
	duplicates int(11) unsigned DEFAULT '0' NOT NULL,
	duplicate_of int(11) unsigned DEFAULT '0' NOT NULL,
	valid tinyint(4) DEFAULT '0' NOT NULL,
	copy_to_pid int(11) DEFAULT '0' NOT NULL,
	original_address int(11) unsigned DEFAULT '0' NOT NULL,
	groups int(11) unsigned DEFAULT '0' NOT NULL,
	social_profiles int(11) unsigned DEFAULT '0' NOT NULL,
	tags int(11) unsigned NOT NULL DEFAULT '0'
);

#
# Table structure for table 'tt_address_mm'
#
CREATE TABLE tt_address_mm (
    uid_local int(11) DEFAULT '0' NOT NULL,
    uid_foreign int(11) DEFAULT '0' NOT NULL,
    sorting int(11) DEFAULT '0' NOT NULL,

    KEY uid_local (uid_local),
    KEY uid_foreign (uid_foreign)
);

#
# Table structure for table 'tx_publicrelations_domain_model_slide'
#
CREATE TABLE tx_publicrelations_domain_model_slide (

	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	type int(11) unsigned DEFAULT '0' NOT NULL,
	image int(11) unsigned NOT NULL default '0',
	title_overwrite tinytext,
	subtitle_overwrite tinytext,
	works_overwrite tinytext,
	no_works tinyint(4) DEFAULT '0' NOT NULL,
	logo_overwrite int(11) unsigned NOT NULL default '0',
	works int(11) unsigned DEFAULT '0' NOT NULL,
	buttons int(11) unsigned DEFAULT '0' NOT NULL,
	client int(11) unsigned DEFAULT '0',
	campaign int(11) unsigned DEFAULT '0',
	news int(11) unsigned DEFAULT '0',
	manual tinytext,
	internal_notes mediumtext,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY t3ver_oid (t3ver_oid,t3ver_wsid),
	KEY language (sys_language_uid,l10n_parent)

);

#
# Table structure for table 'tx_publicrelations_domain_model_mailing'
#
CREATE TABLE tx_publicrelations_domain_model_mailing (

	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	client int(11) unsigned DEFAULT '0' NOT NULL,
	type tinytext,
	title tinytext,
	subject tinytext,
	preview mediumtext,
	alt_sender tinytext,
	alt_template tinytext,
	reply_name tinytext,
	reply_email tinytext,
	blank tinyint(4) DEFAULT '0' NOT NULL,
	no_salutation tinyint(4) DEFAULT '0' NOT NULL,
	no_signature tinyint(4) DEFAULT '0' NOT NULL,
	no_logo tinyint(4) DEFAULT '0' NOT NULL,
	no_header tinyint(4) DEFAULT '0' NOT NULL,
	header int(11) unsigned NOT NULL default '0',
	personally tinyint(4) DEFAULT '0' NOT NULL,
	contents int(11) unsigned DEFAULT '0' NOT NULL,
	attachment int(11) unsigned DEFAULT '0' NOT NULL,
	status int(11) DEFAULT '0' NOT NULL,
	test tinyint(4) DEFAULT '0' NOT NULL,
	planed int(11) DEFAULT '0' NOT NULL,
	started int(11) DEFAULT '0' NOT NULL,
	sent int(11) DEFAULT '0' NOT NULL,
	mails int(11) unsigned DEFAULT '0' NOT NULL,
	logs int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY t3ver_oid (t3ver_oid,t3ver_wsid),
	KEY language (sys_language_uid,l10n_parent)

);

#
# Table structure for table 'tx_publicrelations_domain_model_content'
#
CREATE TABLE tx_publicrelations_domain_model_content (

	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	mailing int(11) unsigned DEFAULT '0',
	invitation int(11) unsigned DEFAULT '0',
	role tinytext,

	type int(11) unsigned DEFAULT '0' NOT NULL,
	listtype int(11) unsigned DEFAULT '0' NOT NULL,
	content mediumtext,
	event_title tinytext,
	event_date tinytext,
	event_location tinytext,
	event_description mediumtext,
	content_element int(11) unsigned DEFAULT '0',
	padding tinytext,
	color tinytext,
	bgcolor tinytext,
	image int(11) unsigned NOT NULL default '0',
	image_full_width tinyint(4) DEFAULT '0' NOT NULL,
	media int(11) unsigned DEFAULT '0' NOT NULL,
	news int(11) unsigned DEFAULT '0',
	event int(11) unsigned DEFAULT '0',
	news_media tinyint(4) DEFAULT '0' NOT NULL,
	event_link tinyint(4) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY t3ver_oid (t3ver_oid,t3ver_wsid),
	KEY language (sys_language_uid,l10n_parent)

);

#
# Table structure for table 'tx_publicrelations_domain_model_mail'
#
CREATE TABLE tx_publicrelations_domain_model_mail (

	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	mailing int(11) unsigned DEFAULT '0',
	accreditation int(11) unsigned DEFAULT '0',

	type int(11) DEFAULT '0' NOT NULL,
	code tinytext,
	receiver int(11) unsigned DEFAULT '0',
	email tinytext,
	subject tinytext,
	content longtext,
	sent int(11) DEFAULT '0' NOT NULL,
	opened int(11) DEFAULT '0' NOT NULL,
	logs int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY t3ver_oid (t3ver_oid,t3ver_wsid),
	KEY language (sys_language_uid,l10n_parent)

);

#
# Table structure for table 'tx_publicrelations_domain_model_socialprofile'
#
CREATE TABLE tx_publicrelations_domain_model_socialprofile (
	contact int(11) unsigned DEFAULT '0',

    type tinytext,
    handle tinytext,
    follower int(11) DEFAULT '0' NOT NULL,
    follower_updated int(11) DEFAULT '0' NOT NULL,
    notes text
);

#
# Table structure for table 'tx_publicrelations_domain_model_contactgroup'
#
CREATE TABLE tx_publicrelations_domain_model_contactgroup (
	parent int(11) unsigned DEFAULT '0',

    type tinytext,
    name varchar(255) DEFAULT '' NOT NULL,
    description text,
    logo int(11) unsigned DEFAULT '0' NOT NULL,
    contacts int(11) unsigned DEFAULT '0' NOT NULL
);

CREATE TABLE tx_publicrelations_contactgroup_ttaddress_mm (
    uid_local INT(11) UNSIGNED DEFAULT 0 NOT NULL,
    uid_foreign INT(11) UNSIGNED DEFAULT 0 NOT NULL,
    sorting INT(11) UNSIGNED DEFAULT 0 NOT NULL,
	sorting_foreign int(11) unsigned DEFAULT '0' NOT NULL
);

#
# Table structure for table 'tx_publicrelations_news_campaign_mm'
#
CREATE TABLE tx_publicrelations_news_campaign_mm (
	uid_local int(11) unsigned DEFAULT '0' NOT NULL,
	uid_foreign int(11) unsigned DEFAULT '0' NOT NULL,
	sorting int(11) unsigned DEFAULT '0' NOT NULL,
	sorting_foreign int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid_local,uid_foreign),
	KEY uid_local (uid_local),
	KEY uid_foreign (uid_foreign)
);

#
# Table structure for table 'tt_content'
#
CREATE TABLE tt_content (
	publicrelations_news int(11) DEFAULT '0' NOT NULL,
	publicrelations_content int(11) DEFAULT '0' NOT NULL,
	KEY index_prcontent (publicrelations_news)
);

CREATE TABLE sys_tag (
	parent int(11) DEFAULT 0 NOT NULL,
	color tinytext,
	icon tinytext
);

#
# Tabelle für Client-Zugriffsberechtigungen (Haupt-Tabelle)
#
CREATE TABLE tx_publicrelations_domain_model_accessclient (
    client int(11) unsigned DEFAULT '0' NOT NULL,
    fe_users int(11) unsigned DEFAULT '0' NOT NULL,
    fe_groups int(11) unsigned DEFAULT '0' NOT NULL,

    view_clippings tinyint(1) unsigned DEFAULT '0' NOT NULL,
    view_contacts tinyint(1) unsigned DEFAULT '0' NOT NULL,
    edit_contacts tinyint(1) unsigned DEFAULT '0' NOT NULL,
    delete_contacts tinyint(1) unsigned DEFAULT '0' NOT NULL,
    view_media tinyint(1) unsigned DEFAULT '0' NOT NULL,
    view_news tinyint(1) unsigned DEFAULT '0' NOT NULL,
    view_events tinyint(1) unsigned DEFAULT '0' NOT NULL,

    advanced_events int(11) unsigned DEFAULT '0' NOT NULL
);

#
# Tabelle für Event-Zugriffsberechtigungen (geschachtelte Tabelle)
#
CREATE TABLE tx_publicrelations_domain_model_accessevent (
    access_client int(11) unsigned DEFAULT '0' NOT NULL,
    event int(11) unsigned DEFAULT '0' NOT NULL,
    access_level varchar(255) DEFAULT '' NOT NULL,
    invitation_type int(11) unsigned DEFAULT '0' NOT NULL
);

#
# Neue Tabelle: tx_publicrelations_domain_model_invitationvariant
#
CREATE TABLE tx_publicrelations_domain_model_invitationvariant (
    code tinytext,
    html varchar(1024) DEFAULT '' NOT NULL,
    subject tinytext,
    from_name tinytext,
    reply_email tinytext,
    reply_name tinytext,
    preheader text,
	attachments int(11) unsigned DEFAULT '0' NOT NULL,
    invitation int(11) unsigned DEFAULT '0' NOT NULL
);

#
# Neue Tabelle für Reports (Clippings, PR, etc.)
#
CREATE TABLE tx_publicrelations_domain_model_report (
    client int(11) unsigned DEFAULT '0',
    campaign int(11) unsigned DEFAULT '0',

    title varchar(255) DEFAULT '' NOT NULL,
	subtitle text,
    status varchar(100) DEFAULT 'new' NOT NULL,
    notes text,
	content text,
	data JSON DEFAULT NULL,
    type varchar(100) DEFAULT 'clipping' NOT NULL,
    files int(11) unsigned DEFAULT '0' NOT NULL,

    medium varchar(255) DEFAULT '' NOT NULL,
    department varchar(255) DEFAULT '' NOT NULL,
    media_type varchar(255) DEFAULT '' NOT NULL,
    publication_frequency varchar(100) DEFAULT '' NOT NULL,
    publication_id varchar(255) DEFAULT '' NOT NULL,
    page_number varchar(50) DEFAULT '' NOT NULL,
    reach int(11) DEFAULT '0' NOT NULL,
    ad_value int(11) DEFAULT '0' NOT NULL,

    apa_guid varchar(255) DEFAULT '' NOT NULL,
    reported tinyint(1) unsigned DEFAULT '0' NOT NULL,
    approval_token varchar(64) DEFAULT '' NOT NULL,

	KEY type (type),
    KEY status (status),
    KEY apa_guid (apa_guid(191))
);

CREATE TABLE tx_publicrelations_domain_model_clippingroute (
    keyword varchar(255) DEFAULT '' NOT NULL,
    client int(11) unsigned DEFAULT '0' NOT NULL,
    project int(11) unsigned DEFAULT '0' NOT NULL,
    drive varchar(1024) DEFAULT '' NOT NULL,
    send_immediate tinyint(1) unsigned DEFAULT '1' NOT NULL,
    to_emails text,
    cc_emails text,
    bcc_emails text,

    UNIQUE KEY `keyword_unique` (`keyword`)
);

CREATE TABLE tx_acmailer_domain_model_mailing (
	client int(11) unsigned DEFAULT '0' NOT NULL,
);

CREATE TABLE tx_acmailer_domain_model_content (
	event int(11) unsigned DEFAULT '0' NOT NULL,
	news int(11) unsigned DEFAULT '0' NOT NULL,
);