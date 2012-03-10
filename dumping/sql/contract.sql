CREATE TABLE credit_runs ("id" serial NOT NULL PRIMARY KEY ,"run_id" numeric DEFAULT 0,"amount" numeric(16, 2) DEFAULT 0,"proc_date" date ,"printed" varchar ,"handed_over" varchar ,"received" varchar ,"cheq_num" numeric DEFAULT 0,"supid" numeric DEFAULT 0,"bankid" numeric DEFAULT 0,"conid" numeric DEFAULT 0,"remit" numeric DEFAULT 0,"remarks" varchar ,"entry_id" int4 DEFAULT 0) WITH OIDS;
SELECT setval('credit_runs_id_seq',1);
CREATE TABLE supp_creditor_run_cheques ("id" serial NOT NULL PRIMARY KEY ,"amount" numeric(16, 2) DEFAULT 0,"proc_date" date ,"printed" varchar ,"handed_over" varchar ,"received" varchar ,"cheq_num" numeric DEFAULT 0,"supid" numeric DEFAULT 0,"bankid" numeric DEFAULT 0,"conid" numeric DEFAULT 0,"remit" numeric DEFAULT 0,"remarks" varchar ) WITH OIDS;
SELECT setval('supp_creditor_run_cheques_id_seq',1);

