CREATE TABLE pages (
    tx_llmstxt_llms_description text,
    tx_llmstxt_section int DEFAULT 0 NOT NULL
);

CREATE TABLE tx_llmstxt_section (
    uid int AUTO_INCREMENT PRIMARY KEY,
    pid int DEFAULT 0 NOT NULL,
    tstamp int DEFAULT 0 NOT NULL,
    crdate int DEFAULT 0 NOT NULL,
    cruser_id int DEFAULT 0 NOT NULL,
    deleted tinyint(4) DEFAULT 0 NOT NULL,
    hidden tinyint(4) DEFAULT 0 NOT NULL,
    starttime int DEFAULT 0 NOT NULL,
    endtime int DEFAULT 0 NOT NULL,
    title varchar(255) DEFAULT '' NOT NULL
);

