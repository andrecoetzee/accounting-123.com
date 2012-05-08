CREATE TABLE emp_deductions ("id" serial NOT NULL PRIMARY KEY ,"empnum" numeric DEFAULT 0,"code" varchar ,"description" varchar ,"clearance_no" varchar ,"amount" varchar ) WITH OIDS;
SELECT setval('emp_deductions_id_seq',1);
CREATE TABLE assetgrp ("grpid" serial NOT NULL PRIMARY KEY ,"grpname" varchar ,"costacc" numeric DEFAULT 0,"accdacc" numeric DEFAULT 0,"depacc" numeric DEFAULT 0,"div" numeric DEFAULT 0) WITH OIDS;
SELECT setval('assetgrp_grpid_seq',1);
CREATE TABLE sj ("id" serial NOT NULL PRIMARY KEY ,"cid" int4 DEFAULT 0,"name" varchar ,"des" varchar ,"date" date ,"exl" numeric(16, 2) DEFAULT 0,"vat" numeric(16, 2) DEFAULT 0,"inc" numeric(16, 2) DEFAULT 0,"div" int4 DEFAULT 0) WITH OIDS;
SELECT setval('sj_id_seq',1);
CREATE TABLE document_types ("id" serial NOT NULL PRIMARY KEY ,"type_name" varchar ) WITH OIDS;
SELECT setval('document_types_id_seq',1);
CREATE TABLE report_types ("id" serial NOT NULL PRIMARY KEY ,"type" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
SELECT setval('report_types_id_seq',1);
CREATE TABLE emp_frin ("id" serial NOT NULL PRIMARY KEY ,"emp" int4 DEFAULT 0,"year" int4 DEFAULT 0,"period" int4 DEFAULT 0,"fdate" date ,"payslip" int4 DEFAULT 0,"code" varchar ,"description" varchar ,"qty" int4 DEFAULT 0,"amount" numeric(16, 2) DEFAULT 0,"week" varchar ) WITH OIDS;
SELECT setval('emp_frin_id_seq',1);
CREATE TABLE sa ("name" varchar ,"date" date ,"amount" numeric DEFAULT 0,"div" numeric DEFAULT 0) WITH OIDS;
CREATE TABLE recon_balance_ct ("id" serial NOT NULL PRIMARY KEY ,"supid" numeric DEFAULT 0,"date" date DEFAULT ('now'::text)::date,"reason_id" numeric DEFAULT 0,"amount" numeric(16, 2) DEFAULT 0,"descr" varchar DEFAULT ''::character varying) WITH OIDS;
SELECT setval('recon_balance_ct_id_seq',1);
CREATE TABLE stock_transfer ("id" serial NOT NULL PRIMARY KEY ,"stkid" numeric DEFAULT 0,"whid_from" numeric DEFAULT 0,"whid_to" numeric DEFAULT 0,"units" numeric(16, 3) DEFAULT 0,"reference" varchar DEFAULT ''::character varying,"remark" varchar DEFAULT ''::character varying,"location_shelf" varchar DEFAULT ''::character varying,"location_row" varchar DEFAULT ''::character varying,"level_min" varchar DEFAULT ''::character varying,"level_max" varchar DEFAULT ''::character varying,"transfer_date" date DEFAULT now()) WITH OIDS;
SELECT setval('stock_transfer_id_seq',1);
CREATE TABLE cubitnet_settings ("setting_name" varchar ,"setting_value" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
CREATE TABLE customer_branches ("id" serial NOT NULL PRIMARY KEY ,"cusnum" int4 DEFAULT 0,"branch_name" varchar ,"branch_descrip" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
SELECT setval('customer_branches_id_seq',1);
CREATE TABLE auditor_report ("id" serial NOT NULL PRIMARY KEY ,"cat" varchar ,"detail" varchar ,"date_added" date ,"user_added" varchar ) WITH OIDS;
SELECT setval('auditor_report_id_seq',1);
CREATE TABLE cancelled_quo ("quoid" numeric DEFAULT 0,"username" varchar ,"date" date ,"deptid" numeric DEFAULT 0,"deptname" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
CREATE TABLE cf ("id" serial NOT NULL PRIMARY KEY ,"description" varchar ,"amount" numeric(16, 2) DEFAULT 0,"date" date ,"div" int4 DEFAULT 0) WITH OIDS;
SELECT setval('cf_id_seq',1);
CREATE TABLE esettings ("id" serial NOT NULL PRIMARY KEY ,"sig" varchar ,"fromname" varchar ,"reply" varchar ,"smtp_host" varchar ,"smtp_auth" varchar ,"smtp_user" varchar ,"smtp_pass" varchar ) WITH OIDS;
SELECT setval('esettings_id_seq',1);
CREATE TABLE serial2 ("stkid" numeric DEFAULT 0,"serno" varchar ,"rsvd" varchar ,"warranty" date ) WITH OIDS;
CREATE TABLE actions ("id" serial NOT NULL PRIMARY KEY ,"doc_id" numeric DEFAULT 0,"title" varchar ,"description" varchar ,"date" date ) WITH OIDS;
SELECT setval('actions_id_seq',1);
CREATE TABLE mail_msgbodies ("msgbody_id" serial NOT NULL PRIMARY KEY ,"type_id" int4 DEFAULT 0,"data" text ) WITH OIDS;
SELECT setval('mail_msgbodies_msgbody_id_seq',1);
CREATE TABLE rec ("id" varchar ,"val1" varchar ,"val2" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
CREATE TABLE pinv_data ("invid" numeric DEFAULT 0,"dept" varchar ,"customer" varchar ,"addr1" varchar ,"addr2" varchar ,"addr3" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
CREATE TABLE todos ("id" serial NOT NULL PRIMARY KEY ,"datemade" date ,"timemade" varchar ,"des" varchar ,"com" varchar ,"op" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
SELECT setval('todos_id_seq',1);
CREATE TABLE cubitnet_sitesettings ("id" serial NOT NULL PRIMARY KEY ,"div" numeric DEFAULT 0,"cn_username" varchar ,"cn_password" varchar ,"cn_name" varchar ,"cn_tel" varchar ,"cn_cell" varchar ,"cn_email" varchar ) WITH OIDS;
SELECT setval('cubitnet_sitesettings_id_seq',1);
CREATE TABLE vatreport ("id" serial NOT NULL PRIMARY KEY ,"cid" int4 DEFAULT 0,"date" date ,"sdate" date ,"type" varchar ,"code" varchar ,"ref" varchar ,"description" varchar ,"amount" numeric(16, 2) DEFAULT 0,"vat" numeric(16, 2) DEFAULT 0) WITH OIDS;
SELECT setval('vatreport_id_seq',1);
CREATE TABLE manu_hist_main ("id" serial NOT NULL PRIMARY KEY ,"stkcod" varchar ,"stkdes" varchar ,"stkid" numeric DEFAULT 0,"qty" numeric DEFAULT 0,"cost_per_unit" numeric(16, 2) DEFAULT 0,"timestamp" timestamp DEFAULT now()) WITH OIDS;
SELECT setval('manu_hist_main_id_seq',1);
CREATE TABLE customers_note ("id" serial NOT NULL PRIMARY KEY ,"cusnum" int4 DEFAULT 0,"note" text DEFAULT ''::text) WITH OIDS;
SELECT setval('customers_note_id_seq',1);
CREATE TABLE inv_nitems ("id" serial NOT NULL PRIMARY KEY ,"invid" numeric DEFAULT 0,"cod" varchar ,"des" varchar ,"qty" numeric DEFAULT 0,"invp" numeric(16, 2) DEFAULT 0,"invup" numeric(16, 2) DEFAULT 0,"unitcost" numeric(16, 2) DEFAULT 0,"amt" numeric(16, 2) DEFAULT 0,"csprice" numeric(16, 2) DEFAULT 0,"div" numeric DEFAULT 0,"funitcost" numeric(16, 2) DEFAULT 0,"famt" numeric(16, 2) DEFAULT 0,"pinv" numeric(16, 2) DEFAULT 0) WITH OIDS;
SELECT setval('inv_nitems_id_seq',1);
CREATE TABLE saved_statement_comments ("id" serial NOT NULL PRIMARY KEY ,"comment" varchar ,"cusnum" varchar ) WITH OIDS;
SELECT setval('saved_statement_comments_id_seq',1);
CREATE TABLE nons_inv_items ("id" serial NOT NULL PRIMARY KEY ,"invid" numeric DEFAULT 0,"qty" numeric DEFAULT 0,"description" varchar ,"div" numeric DEFAULT 0,"amt" numeric(16, 2) DEFAULT 0,"unitcost" numeric(16, 2) DEFAULT 0,"accid" numeric DEFAULT 0,"rqty" numeric DEFAULT 0,"vatex" varchar ,"cunitcost" numeric(16, 2) DEFAULT 0,"asset_id" numeric DEFAULT 0) WITH OIDS;
SELECT setval('nons_inv_items_id_seq',1);
CREATE TABLE ratio_account_owners ("id" serial NOT NULL PRIMARY KEY ,"accid" numeric DEFAULT 0,"type_id" numeric DEFAULT 0) WITH OIDS;
SELECT setval('ratio_account_owners_id_seq',1);
CREATE TABLE occ_cat ("id" serial NOT NULL PRIMARY KEY ,"cat" varchar ) WITH OIDS;
SELECT setval('occ_cat_id_seq',9);
INSERT INTO occ_cat ("id","cat") VALUES('1','Executive Management');
INSERT INTO occ_cat ("id","cat") VALUES('2','Other/Senior Managers');
INSERT INTO occ_cat ("id","cat") VALUES('3','Professionals');
INSERT INTO occ_cat ("id","cat") VALUES('4','Technicians & Assoc Prof.');
INSERT INTO occ_cat ("id","cat") VALUES('5','Clerical, Secretarial & Admin');
INSERT INTO occ_cat ("id","cat") VALUES('6','Services & Sales');
INSERT INTO occ_cat ("id","cat") VALUES('7','Crafts & Related Trades');
INSERT INTO occ_cat ("id","cat") VALUES('8','Plant & Machine Operators and assemblers');
INSERT INTO occ_cat ("id","cat") VALUES('9','Labourers & Related/Elementary Occupations');
CREATE TABLE doc_dates ("id" serial NOT NULL PRIMARY KEY ,"user_id" numeric DEFAULT 0,"doc_id" numeric DEFAULT 0,"date" date ,"notes" varchar ) WITH OIDS;
SELECT setval('doc_dates_id_seq',1);
CREATE TABLE documents ("docid" serial NOT NULL PRIMARY KEY ,"typeid" varchar ,"typename" varchar ,"xin" varchar ,"docref" varchar ,"docname" varchar ,"filename" varchar ,"mimetype" varchar ,"descrip" varchar ,"docu" varchar ,"div" numeric DEFAULT 0,"docaccess" varchar ,"status" varchar DEFAULT 'inactive'::character varying,"doc_type" varchar ,"revision" varchar ,"title" varchar ,"comments" varchar ,"team_id" varchar ,"location" varchar ,"wordproc" varchar ) WITH OIDS;
SELECT setval('documents_docid_seq',1);
CREATE TABLE irp5_exports ("c1120" serial NOT NULL PRIMARY KEY ,"dummy" varchar ) WITH OIDS;
SELECT setval('irp5_exports_c1120_seq',1);
CREATE TABLE sord_data ("sordid" numeric DEFAULT 0,"dept" varchar ,"customer" varchar ,"addr1" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
CREATE TABLE saved_bs_accounts ("id" serial NOT NULL PRIMARY KEY ,"accid" numeric DEFAULT 0,"topacc" numeric DEFAULT 0,"accnum" numeric DEFAULT 0,"accname" varchar ,"note" varchar ,"toptype" varchar ) WITH OIDS;
SELECT setval('saved_bs_accounts_id_seq',1);
CREATE TABLE pr ("id" serial NOT NULL PRIMARY KEY ,"userid" int4 DEFAULT 0,"username" varchar ,"amount" numeric(16, 2) DEFAULT 0,"pdate" date ,"inv" int4 DEFAULT 0,"cust" varchar ,"t" varchar ) WITH OIDS;
SELECT setval('pr_id_seq',1);
CREATE TABLE userscripts ("username" varchar ,"script" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
CREATE TABLE serial1 ("stkid" numeric DEFAULT 0,"serno" varchar ,"rsvd" varchar ,"warranty" date ) WITH OIDS;
CREATE TABLE email_attachments ("id" serial NOT NULL PRIMARY KEY ,"attach_data" text ,"attach_mime" text ,"attach_filename" varchar DEFAULT ''::character varying) WITH OIDS;
SELECT setval('email_attachments_id_seq',1);
CREATE TABLE emp_com ("id" serial NOT NULL PRIMARY KEY ,"emp" int4 DEFAULT 0,"year" int4 DEFAULT 0,"period" int4 DEFAULT 0,"date" date ,"payslip" int4 DEFAULT 0,"type" varchar ,"code" varchar ,"description" varchar ,"qty" int4 DEFAULT 0,"rate" numeric(16, 2) DEFAULT 0,"amount" numeric(16, 2) DEFAULT 0,"ex" varchar ,"week" varchar ) WITH OIDS;
SELECT setval('emp_com_id_seq',1);
CREATE TABLE conpers ("id" serial NOT NULL PRIMARY KEY ,"con" int4 DEFAULT 0,"name" varchar ,"pos" varchar ,"tell" varchar ,"cell" varchar ,"fax" varchar ,"email" varchar ,"notes" varchar ,"div" int4 DEFAULT 0) WITH OIDS;
SELECT setval('conpers_id_seq',1);
CREATE TABLE salesrec ("edate" date ,"invid" numeric DEFAULT 0,"invnum" numeric DEFAULT 0,"debtacc" numeric DEFAULT 0,"vat" numeric(16, 2) DEFAULT 0,"total" numeric(16, 2) DEFAULT 0,"typ" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
CREATE TABLE ages ("id" serial NOT NULL PRIMARY KEY ,"cust" int4 DEFAULT 0,"curr" numeric(16, 2) DEFAULT 0,"age30" numeric(16, 2) DEFAULT 0,"age60" numeric(16, 2) DEFAULT 0,"age90" numeric(16, 2) DEFAULT 0,"age120" numeric(16, 2) DEFAULT 0) WITH OIDS;
SELECT setval('ages_id_seq',1);
CREATE TABLE pslip_nitems ("id" serial NOT NULL PRIMARY KEY ,"slipid" numeric DEFAULT 0,"cod" varchar ,"des" varchar ,"qty" numeric DEFAULT 0,"pqty" numeric DEFAULT 0,"unitcost" numeric(16, 2) DEFAULT 0,"amt" numeric(16, 2) DEFAULT 0,"csprice" numeric(16, 2) DEFAULT 0,"div" numeric DEFAULT 0) WITH OIDS;
SELECT setval('pslip_nitems_id_seq',1);
CREATE TABLE possets ("opt" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
CREATE TABLE asset_types ("id" serial NOT NULL PRIMARY KEY ,"name" varchar ,"description" varchar ) WITH OIDS;
SELECT setval('asset_types_id_seq',1);
CREATE TABLE document_files ("id" serial NOT NULL PRIMARY KEY ,"doc_id" numeric DEFAULT 0,"filename" varchar ,"file" varchar ,"type" varchar ,"size" varchar ) WITH OIDS;
SELECT setval('document_files_id_seq',1);
CREATE TABLE grpadd ("id" serial NOT NULL PRIMARY KEY ,"grpname" varchar ) WITH OIDS;
SELECT setval('grpadd_id_seq',1);
CREATE TABLE ratio_account_types ("id" serial NOT NULL PRIMARY KEY ,"rtype" varchar ,"rname" varchar ) WITH OIDS;
SELECT setval('ratio_account_types_id_seq',2);
CREATE TABLE rec_invoices ("invid" serial NOT NULL PRIMARY KEY ,"deptid" numeric DEFAULT 0,"cusnum" numeric DEFAULT 0,"cordno" varchar ,"ordno" varchar ,"chrgvat" varchar ,"terms" numeric DEFAULT 0,"salespn" varchar ,"odate" date ,"comm" varchar ,"done" varchar ,"username" varchar ,"deptname" varchar ,"cusacc" varchar ,"cusname" varchar ,"surname" varchar ,"cusaddr" varchar ,"cusordno" varchar ,"cusvatno" varchar ,"prd" numeric DEFAULT 0,"div" numeric DEFAULT 0,"age" numeric DEFAULT 0,"prints" numeric DEFAULT 0,"nbal" numeric DEFAULT 0,"disc" numeric(16, 2) DEFAULT 0,"discp" numeric(16, 2) DEFAULT 0,"delchrg" numeric(16, 2) DEFAULT 0,"subtot" numeric(16, 2) DEFAULT 0,"traddisc" numeric(16, 2) DEFAULT 0,"balance" numeric(16, 2) DEFAULT 0,"vat" numeric(16, 2) DEFAULT 0,"total" numeric(16, 2) DEFAULT 0,"serd" varchar ,"docref" varchar ,"discount" numeric(16, 2) DEFAULT 0,"delivery" numeric(16, 2) DEFAULT 0,"delvat" int4 DEFAULT 0) WITH OIDS;
SELECT setval('rec_invoices_invid_seq',1);
CREATE TABLE pserec ("recid" serial NOT NULL PRIMARY KEY ,"purid" numeric DEFAULT 0,"purnum" numeric DEFAULT 0,"stkid" numeric DEFAULT 0,"serno" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
SELECT setval('pserec_recid_seq',1);
CREATE TABLE paye ("id" serial NOT NULL PRIMARY KEY ,"percentage" numeric DEFAULT 0,"extra" int4 DEFAULT 0,"div" numeric DEFAULT 0,"min" numeric DEFAULT 0,"max" numeric DEFAULT 0) WITH OIDS;
SELECT setval('paye_id_seq',127);
INSERT INTO paye ("id","percentage","extra","div","min","max") VALUES('69','18','0','0','0','100000.99');
INSERT INTO paye ("id","percentage","extra","div","min","max") VALUES('70','25','18000','0','100001','160000.99');
INSERT INTO paye ("id","percentage","extra","div","min","max") VALUES('71','30','33000','0','160001','220000.99');
INSERT INTO paye ("id","percentage","extra","div","min","max") VALUES('72','35','51000','0','220001','300000.99');
INSERT INTO paye ("id","percentage","extra","div","min","max") VALUES('73','38','79000','0','300001','400000.99');
INSERT INTO paye ("id","percentage","extra","div","min","max") VALUES('74','40','117000','0','400001','999999999');
CREATE TABLE saved_tb_accounts ("id" serial NOT NULL PRIMARY KEY ,"accid" numeric DEFAULT 0,"topacc" numeric DEFAULT 0,"accnum" numeric DEFAULT 0,"accname" varchar ,"note" varchar ) WITH OIDS;
SELECT setval('saved_tb_accounts_id_seq',1);
CREATE TABLE serial5 ("stkid" numeric DEFAULT 0,"serno" varchar ,"rsvd" varchar ,"warranty" date ) WITH OIDS;
CREATE TABLE occ_level ("id" serial NOT NULL PRIMARY KEY ,"level" varchar ) WITH OIDS;
SELECT setval('occ_level_id_seq',6);
INSERT INTO occ_level ("id","level") VALUES('1','Top Management');
INSERT INTO occ_level ("id","level") VALUES('2','Senior Management');
INSERT INTO occ_level ("id","level") VALUES('3','Qualified Specialists/Mid-management');
INSERT INTO occ_level ("id","level") VALUES('4','Qualified workers,Junior management,Superintendents');
INSERT INTO occ_level ("id","level") VALUES('5','Semi-skilled and discretionary decision making');
INSERT INTO occ_level ("id","level") VALUES('6','Unskilled & Defined Decision Making');
CREATE TABLE perm ("script" varchar ) WITH OIDS;
CREATE TABLE bankacctypes ("acctype_id" serial NOT NULL PRIMARY KEY ,"accname" varchar ) WITH OIDS;
SELECT setval('bankacctypes_acctype_id_seq',1);
CREATE TABLE recipies ("id" serial NOT NULL PRIMARY KEY ,"m_stock_id" numeric DEFAULT 0,"s_stock_id" numeric DEFAULT 0,"qty" numeric DEFAULT 0) WITH OIDS;
SELECT setval('recipies_id_seq',1);
CREATE TABLE vat_returns_archive ("id" serial NOT NULL PRIMARY KEY ,"start_date" date ,"end_date" date ,"registration_number" varchar ,"enquire_telephone" varchar ,"rendering_date" varchar ,"payment_amount" varchar ,"remittance_rec_date" varchar ,"payment_method" varchar ,"payment_area" varchar ,"payment_tax_period" varchar ,"client_data1" varchar ,"client_data2" varchar ,"client_data3" varchar ,"client_data4" varchar ,"client_data5" varchar ,"trading_name" varchar ,"tax_period_end1" varchar ,"tax_period_end2" varchar ,"vat_registration_number" varchar ,"acc_number1" varchar ,"acc_number2" varchar ,"acc_number3" varchar ,"acc_number4" varchar ,"date_received" varchar ,"area" varchar ,"area2" varchar ,"field_1" varchar ,"field_1a" varchar ,"field_2" varchar ,"field_3" varchar ,"field_4" varchar ,"field_4a" varchar ,"field_5" varchar ,"field_6" varchar ,"field_7" varchar ,"field_8" varchar ,"field_9" varchar ,"field_10" varchar ,"field_11" varchar ,"field_12" varchar ,"field_13" varchar ,"field_14" varchar ,"field_15" varchar ,"field_16" varchar ,"field_17" varchar ,"field_18" varchar ,"field_19" varchar ,"field_20" varchar ,"field_25" varchar ,"field_26" varchar ,"field_26_1" varchar ,"field_27" varchar ,"field_28" varchar ,"field_29" varchar ,"field_31" varchar ,"field_32" varchar ,"field_32_1" varchar ,"field_33" varchar ,"field_34" varchar ,"field_36" varchar ,"field_37" varchar ,"field_37_1" varchar ,"field_38" varchar ,"field_39" varchar ,"field_40" varchar ) WITH OIDS;
SELECT setval('vat_returns_archive_id_seq',1);
CREATE TABLE emp_subsistence ("id" serial NOT NULL PRIMARY KEY ,"empnum" int8 DEFAULT 0,"subid" int8 DEFAULT 0,"amount" numeric(16, 2) DEFAULT 0,"accid" int8 DEFAULT 0,"days" int4 DEFAULT 0) WITH OIDS;
SELECT setval('emp_subsistence_id_seq',1);
CREATE TABLE grievances ("grievnum" serial NOT NULL PRIMARY KEY ,"empnum" varchar ,"first_rec_date" date ,"griev_details" varchar ,"company_date" date ,"ccma_date" date ,"ccma_app_date" date ,"court_date" date ,"court_app_date" date ,"div" varchar ,"closed" varchar ) WITH OIDS;
SELECT setval('grievances_grievnum_seq',1);
CREATE TABLE settlement_cus ("customer" numeric DEFAULT 0,"amt" numeric(16, 2) DEFAULT 0,"setamt" numeric(16, 2) DEFAULT 0,"setvat" varchar DEFAULT ''::character varying,"setvatamt" numeric(16, 2) DEFAULT 0,"setvatcode" numeric DEFAULT 0,"tdate" date ,"sdate" date ,"refnum" varchar DEFAULT ''::character varying) WITH OIDS;
CREATE TABLE emp_loaninstallments ("id" serial NOT NULL PRIMARY KEY ,"empnum" numeric DEFAULT 0,"fdate" date ,"fperiod" int4 DEFAULT 0,"fmonth" int4 DEFAULT 0,"fyear" int4 DEFAULT 0,"installment" numeric(16, 2) DEFAULT 0,"interest" numeric(16, 2) DEFAULT 0,"fringe" numeric(16, 2) DEFAULT 0) WITH OIDS;
SELECT setval('emp_loaninstallments_id_seq',1);
CREATE TABLE purch_batch_entries_costcenters ("id" serial NOT NULL PRIMARY KEY ,"project" varchar ,"costcenter" varchar ,"costperc" varchar ,"batch_entry" numeric DEFAULT 0) WITH OIDS;
SELECT setval('purch_batch_entries_costcenters_id_seq',1);
CREATE TABLE nons_pur_itemsn ("id" serial NOT NULL PRIMARY KEY ,"noteid" numeric DEFAULT 0,"cod" varchar ,"des" varchar ,"qty" numeric DEFAULT 0,"ddate" date ,"div" numeric DEFAULT 0,"rqty" numeric DEFAULT 0,"unitcost" numeric(16, 2) DEFAULT 0,"amt" numeric(16, 2) DEFAULT 0,"accid" numeric DEFAULT 0,"svat" numeric(16, 2) DEFAULT 0,"description" varchar ) WITH OIDS;
SELECT setval('nons_pur_itemsn_id_seq',1);
CREATE TABLE nons_purch_int ("purid" serial NOT NULL PRIMARY KEY ,"deptid" numeric DEFAULT 0,"supid" numeric DEFAULT 0,"supplier" varchar ,"supaddr" varchar ,"terms" numeric DEFAULT 0,"pdate" date ,"ddate" date ,"remarks" varchar ,"received" varchar ,"done" varchar ,"refno" varchar ,"prd" numeric DEFAULT 0,"order" varchar ,"ordernum" varchar ,"part" varchar ,"div" numeric DEFAULT 0,"purnum" varchar ,"cusid" int4 DEFAULT 0,"shipchrg" numeric(16, 2) DEFAULT 0,"xrate" numeric(16, 2) DEFAULT 0,"fcid" numeric DEFAULT 0,"currency" varchar ,"subtot" numeric(16, 2) DEFAULT 0,"total" numeric(16, 2) DEFAULT 0,"balance" numeric(16, 2) DEFAULT 0,"tax" numeric(16, 2) DEFAULT 0,"spurnum" numeric DEFAULT 0,"spurtype" varchar ,"spurprd" varchar ,"assid" numeric DEFAULT 0,"grpid" numeric DEFAULT 0,"mpurid" numeric DEFAULT 0,"mpurnum" numeric DEFAULT 0,"shipping" numeric(16, 2) DEFAULT 0,"fshipchrg" numeric(16, 2) DEFAULT 0,"duty" numeric(16, 2) DEFAULT 0,"curr" varchar ,"fsubtot" numeric(16, 2) DEFAULT 0,"fbalance" numeric(16, 2) DEFAULT 0,"ctyp" varchar ,"typeid" numeric DEFAULT 0) WITH OIDS;
SELECT setval('nons_purch_int_purid_seq',1);
CREATE TABLE lemployees ("empnum" serial NOT NULL PRIMARY KEY ,"sname" varchar ,"fnames" varchar ,"sex" bpchar ,"marital" varchar ,"resident" bool ,"hiredate" date ,"telno" varchar ,"email" varchar ,"basic_sal" numeric DEFAULT 0,"commission" numeric DEFAULT 0,"paytype" varchar ,"bankname" varchar ,"bankcode" varchar ,"bankacctype" varchar ,"bankaccno" varchar ,"leave_vac" numeric DEFAULT 0,"leave_sick" numeric DEFAULT 0,"leave_study" numeric DEFAULT 0,"res1" varchar ,"res2" varchar ,"res3" varchar ,"res4" varchar ,"pos1" varchar ,"pos2" varchar ,"pcode" varchar ,"contsname" varchar ,"contfnames" varchar ,"contres1" varchar ,"contres2" varchar ,"contres3" varchar ,"conttelno" varchar ,"loanamt" numeric DEFAULT 0,"loaninstall" numeric DEFAULT 0,"loanint" numeric DEFAULT 0,"loanperiod" numeric DEFAULT 0,"gotloan" bool ,"lastpay" varchar ,"div" numeric DEFAULT 0,"idnum" varchar ,"saltyp" varchar ,"payprd" varchar ,"novert" numeric DEFAULT 0,"hovert" numeric DEFAULT 0,"hpweek" numeric DEFAULT 0,"vaclea" numeric DEFAULT 0,"siclea" numeric DEFAULT 0,"stdlea" numeric DEFAULT 0,"taxref" varchar ,"enum" varchar ,"leavedate" date ,"leavereason" varchar ,"leavedescription" varchar ,"dissat_payben" varchar ,"dissat_jobcon" varchar ,"dissat_env" varchar ,"emigration" varchar ,"incom_supman" varchar ,"incom_orgcul" varchar ,"incom_collea" varchar ,"lack_perdev" varchar ,"lack_caradv" varchar ,"lack_recogn" varchar ,"lack_culsen" varchar ,"self_empl" varchar ,"unsuit_locorg" varchar ,"redundantretrench" varchar ,"dismissmisconduct" varchar ,"incapablepoorperc" varchar ,"negosettle" varchar ,"desertion" varchar ,"death" varchar ,"retirement" varchar ,"illhealth" varchar ,"pregnan" varchar ,"famcircums" varchar ,"intercomptrans" varchar ,"designation" varchar DEFAULT ''::character varying,"bonus" numeric DEFAULT 0,"initials" varchar DEFAULT ''::character varying,"comp_pension" numeric DEFAULT 0,"emp_pension" numeric DEFAULT 0,"comp_medical" numeric DEFAULT 0,"emp_medical" numeric DEFAULT 0,"pay_periods" int4 DEFAULT 0,"pay_periods_worked" int4 DEFAULT 0,"dependants" int4 DEFAULT 0,"tall" numeric(16, 2) DEFAULT 0,"emp_ret" numeric(16, 2) DEFAULT 0,"comp_ret" numeric(16, 2) DEFAULT 0,"basic_sal_annum" numeric(16, 2) DEFAULT 0,"sal_bonus" numeric(16, 2) DEFAULT 0,"all_travel" numeric(16, 2) DEFAULT 0,"comp_uif" numeric(16, 2) DEFAULT 0,"comp_other" numeric(16, 2) DEFAULT 0,"comp_providence" numeric(16, 2) DEFAULT 0,"emp_uif" numeric(16, 2) DEFAULT 0,"emp_other" numeric(16, 2) DEFAULT 0,"emp_providence" numeric(16, 2) DEFAULT 0,"expacc_pension" numeric DEFAULT 0,"expacc_providence" numeric DEFAULT 0,"expacc_medical" numeric DEFAULT 0,"expacc_ret" numeric DEFAULT 0,"expacc_uif" numeric DEFAULT 0,"expacc_other" numeric DEFAULT 0,"sal_bonus_month" varchar DEFAULT ''::character varying,"comp_sdl" numeric(16, 2) DEFAULT 0,"comp_provident" numeric(16, 2) DEFAULT 0,"emp_provident" numeric(16, 2) DEFAULT 0,"expacc_provident" numeric DEFAULT 0,"expacc_salwages" numeric DEFAULT 0,"expacc_sdl" numeric DEFAULT 0,"fringe_car1" numeric DEFAULT 0,"fringe_car2" numeric DEFAULT 0,"loanfringe" numeric DEFAULT 0,"loandate" date ,"fringe_car1_contrib" numeric DEFAULT 0,"fringe_car1_fuel" numeric DEFAULT 0,"fringe_car1_service" numeric DEFAULT 0,"fringe_car2_contrib" numeric DEFAULT 0,"fringe_car2_fuel" numeric DEFAULT 0,"fringe_car2_service" numeric DEFAULT 0,"payprd_day" varchar DEFAULT ''::character varying,"loanpayslip" numeric DEFAULT 0,"expacc_loan" numeric DEFAULT 0,"passportnum" varchar DEFAULT ''::character varying,"loanid" numeric DEFAULT 0,"loanint_amt" numeric DEFAULT 0,"loanint_unpaid" numeric DEFAULT 0,"loanamt_tot" numeric DEFAULT 0,"tax_number" varchar DEFAULT ''::character varying,"fixed_rate" varchar DEFAULT ''::character varying,"flag" varchar DEFAULT ''::character varying,"emp_meddeps" int4 DEFAULT 0,"department" varchar DEFAULT ''::character varying,"occ_cat" varchar DEFAULT ''::character varying,"occ_level" varchar DEFAULT ''::character varying,"pos_filled" varchar DEFAULT ''::character varying,"temporary" varchar DEFAULT ''::character varying,"termination_date" date ,"recruitment_from" varchar DEFAULT ''::character varying,"employment_reason" varchar DEFAULT ''::character varying,"union_name" varchar DEFAULT ''::character varying,"union_mem_num" varchar DEFAULT ''::character varying,"union_pos" varchar DEFAULT ''::character varying,"race" varchar DEFAULT ''::character varying,"disabled_stat" varchar DEFAULT ''::character varying,"all_reimburs" numeric(16, 2) DEFAULT 0,"expacc_reimburs" numeric DEFAULT 0,"prevemp_remun" numeric DEFAULT 0,"prevemp_tax" numeric DEFAULT 0,"cyear" varchar DEFAULT ''::character varying,"emp_group" numeric DEFAULT 0,"person_nature" varchar DEFAULT ''::character varying) WITH OIDS;
SELECT setval('lemployees_empnum_seq',1);
CREATE TABLE stock_tbimport ("id" serial NOT NULL PRIMARY KEY ,"stkid" numeric DEFAULT 0,"stkcod" varchar ,"balance" numeric(16, 2) DEFAULT 0,"units" numeric DEFAULT 0) WITH OIDS;
SELECT setval('stock_tbimport_id_seq',1);
CREATE TABLE doctypes ("typeid" serial NOT NULL PRIMARY KEY ,"typeref" varchar ,"typename" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
SELECT setval('doctypes_typeid_seq',1);
CREATE TABLE corders_items ("sordid" numeric DEFAULT 0,"whid" numeric DEFAULT 0,"stkid" numeric DEFAULT 0,"qty" numeric DEFAULT 0,"div" numeric DEFAULT 0,"amt" numeric(16, 2) DEFAULT 0,"discp" numeric(16, 2) DEFAULT 0,"disc" numeric(16, 2) DEFAULT 0,"unitcost" numeric(16, 2) DEFAULT 0,"vatcode" int4 DEFAULT 0,"description" varchar ,"account" int4 DEFAULT 0) WITH OIDS;
CREATE TABLE transerial ("tid" numeric DEFAULT 0,"stkid" numeric DEFAULT 0,"serno" varchar ) WITH OIDS;
CREATE TABLE empleave ("id" serial NOT NULL PRIMARY KEY ,"empnum" int4 DEFAULT 0,"date" date ,"startdate" date ,"enddate" date ,"approvedby" varchar ,"type" varchar ,"workingdays" numeric DEFAULT 0,"nonworking" numeric DEFAULT 0,"approved" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
SELECT setval('empleave_id_seq',1);
CREATE TABLE nons_purchases ("purid" serial NOT NULL PRIMARY KEY ,"deptid" numeric DEFAULT 0,"supid" numeric DEFAULT 0,"supplier" varchar ,"supaddr" varchar ,"terms" numeric DEFAULT 0,"pdate" date ,"ddate" date ,"remarks" varchar ,"received" varchar ,"done" varchar ,"refno" varchar ,"vatinc" varchar ,"prd" numeric DEFAULT 0,"order" varchar ,"ordernum" varchar ,"part" varchar ,"div" numeric DEFAULT 0,"purnum" varchar ,"cusid" int4 DEFAULT 0,"shipchrg" numeric(16, 2) DEFAULT 0,"subtot" numeric(16, 2) DEFAULT 0,"total" numeric(16, 2) DEFAULT 0,"balance" numeric(16, 2) DEFAULT 0,"vat" numeric(16, 2) DEFAULT 0,"spurnum" numeric DEFAULT 0,"spurtype" varchar ,"spurprd" varchar ,"assid" numeric DEFAULT 0,"grpid" numeric DEFAULT 0,"mpurid" numeric DEFAULT 0,"mpurnum" numeric DEFAULT 0,"shipping" numeric(16, 2) DEFAULT 0,"ctyp" varchar ,"typeid" numeric DEFAULT 0,"supinv" varchar ,"delvat" int4 DEFAULT 0,"purs" varchar ,"is_asset" varchar ) WITH OIDS;
SELECT setval('purchasesids_seq',1);
CREATE TABLE pslip_dispatched ("id" serial NOT NULL PRIMARY KEY ,"invid" numeric DEFAULT 0,"reason_id" numeric DEFAULT 0,"timestamp" timestamp DEFAULT now(),"sordid" numeric DEFAULT 0) WITH OIDS;
SELECT setval('pslip_dispatched_id_seq',1);
CREATE TABLE budgets ("budid" serial NOT NULL PRIMARY KEY ,"budname" varchar ,"budtype" varchar ,"budfor" varchar ,"fromprd" numeric DEFAULT 0,"toprd" numeric DEFAULT 0,"edate" date ,"div" numeric DEFAULT 0,"prdtyp" varchar ) WITH OIDS;
SELECT setval('budgets_budid_seq',1);
CREATE TABLE jobstock ("jsid" serial NOT NULL PRIMARY KEY ,"jobid" numeric DEFAULT 0,"jobnum" varchar ,"stkid" numeric DEFAULT 0,"stkcod" varchar ,"units" numeric DEFAULT 0,"csamt" numeric(16, 2) DEFAULT 0,"div" numeric DEFAULT 0) WITH OIDS;
SELECT setval('jobstock_jsid_seq',1);
CREATE TABLE dispatch_types ("id" serial NOT NULL PRIMARY KEY ,"type_name" varchar ) WITH OIDS;
SELECT setval('dispatch_types_id_seq',1);
CREATE TABLE payperiods ("payperiod" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
CREATE TABLE cancelled_purch ("purnum" numeric DEFAULT 0,"pdate" date ,"div" numeric DEFAULT 0,"username" varchar ,"purid" numeric DEFAULT 0,"deptid" numeric DEFAULT 0,"supid" numeric DEFAULT 0,"supaddr" varchar ,"terms" numeric DEFAULT 0,"ddate" date ,"remarks" varchar ,"received" varchar ,"refno" varchar ,"vatinc" varchar ,"prd" numeric DEFAULT 0,"ordernum" varchar ,"part" varchar ,"edit" numeric DEFAULT 0,"supname" varchar ,"supno" varchar ,"shipchrg" numeric(16, 2) DEFAULT 0,"subtot" numeric(16, 2) DEFAULT 0,"total" numeric(16, 2) DEFAULT 0,"balance" numeric(16, 2) DEFAULT 0,"vat" numeric(16, 2) DEFAULT 0,"supinv" varchar ,"apprv" varchar ,"appname" varchar ,"appdate" date ,"rvat" numeric(16, 2) DEFAULT 0,"rshipchrg" numeric(16, 2) DEFAULT 0,"rsubtot" numeric(16, 2) DEFAULT 0,"rtotal" numeric(16, 2) DEFAULT 0,"jobid" numeric DEFAULT 0,"jobnum" varchar ,"toggle" varchar ,"cash" varchar ,"shipping" numeric(16, 2) DEFAULT 0,"invcd" varchar ,"rshipping" numeric(16, 2) DEFAULT 0,"noted" varchar ,"returned" varchar ,"iamount" numeric(16, 2) DEFAULT 0,"ivat" numeric(16, 2) DEFAULT 0,"delvat" int4 DEFAULT 0) WITH OIDS;
CREATE TABLE emp_inc ("id" serial NOT NULL PRIMARY KEY ,"emp" int4 DEFAULT 0,"year" int4 DEFAULT 0,"period" int4 DEFAULT 0,"date" date ,"payslip" int4 DEFAULT 0,"type" varchar ,"code" varchar ,"description" varchar ,"pension" varchar ,"qty" int4 DEFAULT 0,"rate" numeric(16, 2) DEFAULT 0,"amount" numeric(16, 2) DEFAULT 0,"ex" varchar ,"week" varchar ) WITH OIDS;
SELECT setval('emp_inc_id_seq',1);
CREATE TABLE cancelled_inv ("invid" numeric DEFAULT 0,"username" varchar ,"date" date ,"deptid" numeric DEFAULT 0,"deptname" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
CREATE TABLE forecasts ("id" serial NOT NULL PRIMARY KEY ,"prd" varchar ,"prd_val" numeric DEFAULT 0,"inc_perc" numeric DEFAULT 0,"dec_perc" numeric DEFAULT 0,"timestamp" timestamp DEFAULT now(),"user_id" numeric DEFAULT 0) WITH OIDS;
SELECT setval('forecasts_id_seq',1);
CREATE TABLE manu_history ("m_stock_id" numeric DEFAULT 0,"s_stock_id" numeric DEFAULT 0,"qty" numeric DEFAULT 0,"timestamp" timestamp DEFAULT now(),"cost" numeric(16, 2) DEFAULT 0) WITH OIDS;
CREATE TABLE batch_cashbook ("cashid" serial NOT NULL PRIMARY KEY ,"trantype" varchar ,"bankid" numeric DEFAULT 0,"date" date ,"name" varchar ,"descript" varchar ,"cheqnum" numeric DEFAULT 0,"amount" numeric DEFAULT 0,"banked" varchar ,"accinv" varchar ,"lnk" numeric DEFAULT 0,"supid" numeric DEFAULT 0,"cusnum" numeric DEFAULT 0,"rinvids" text ,"amounts" text ,"invprds" text ,"ids" text ,"purids" text ,"pamounts" text ,"pdates" text ,"div" numeric DEFAULT 0,"accids" text ,"suprec" numeric DEFAULT 0,"vat" numeric DEFAULT 0,"chrgvat" varchar ,"vats" varchar ,"chrgvats" varchar ,"rages" text ,"famount" numeric(16, 2) DEFAULT 0,"fpamounts" varchar ,"famounts" varchar ,"lcashid" varchar ,"fcid" numeric DEFAULT 0,"currency" varchar ,"location" varchar ,"opt" varchar ,"rid" int4 DEFAULT 0,"vatcode" int4 DEFAULT 0,"vatcodes" varchar ,"bt" varchar ,"reference" varchar ) WITH OIDS;
SELECT setval('batch_cashbook_cashid_seq',1);
CREATE TABLE splash ("id" serial NOT NULL PRIMARY KEY ,"message" text ,"div" numeric DEFAULT 0) WITH OIDS;
SELECT setval('splash_id_seq',1);
INSERT INTO splash ("id","message","div") VALUES('1','PLEASE CHANGE YOUR DEFAULT PASSWORDS
(and everything is CaSe senSitiVe - so check your caps lock...)

USERNAME : admin
PASSWORD: 123

You can display your own message for your company in this space by
editing this text from the admin, settings menu.

If you are using Cubit over the Internet please make sure that your
webserver has been properly hardened and that you are using Cubit
over HTTPS.
Ask your Cubit certified technician to configure this for you.

Cubit has different versions. Some of these are Property, Manufacturing,
Hospitality and others. Please send e-mail to sales@cubit.co.za
for more information

Cubit also has FREE Point of Sale Modules, these have to be
installed on each system and configured to link to the main Cubit.
(Ask your Cubit certified technician to configure this for you)

SUPPORT Every user of Cubit has automatic access to limited e-mail
support. Please send support questions to support@cubit.co.za

You may purchase telephonic support, please call us during office hours
on support@cubit.co.za for the latest rates or closest dealer.

If you see anything in any version of the software that you are
not satisfied with or would like to have added please send your
suggestion to support@cubit.co.za
','0');
CREATE TABLE statement_arefs ("id" serial NOT NULL PRIMARY KEY ,"amount" varchar ,"des1" varchar ,"des2" varchar ,"pn" varchar ,"action" varchar ,"account" int4 DEFAULT 0,"by" varchar ) WITH OIDS;
SELECT setval('statement_arefs_id_seq',1);
CREATE TABLE pur_items ("id" serial NOT NULL PRIMARY KEY ,"purid" numeric DEFAULT 0,"whid" numeric DEFAULT 0,"stkid" numeric DEFAULT 0,"qty" numeric DEFAULT 0,"ddate" date ,"div" numeric DEFAULT 0,"qpack" numeric DEFAULT 0,"upack" numeric DEFAULT 0,"ppack" numeric DEFAULT 0,"svat" numeric DEFAULT 0,"rqty" numeric DEFAULT 0,"tqty" numeric DEFAULT 0,"unitcost" numeric(16, 2) DEFAULT 0,"amt" numeric(16, 2) DEFAULT 0,"iqty" numeric(16, 2) DEFAULT 0,"vatcode" int4 DEFAULT 0,"description" varchar ,"account" int4 DEFAULT 0,"sup_stkcod" varchar ,"udiscount" numeric(16, 2) DEFAULT 0) WITH OIDS;
SELECT setval('pur_items_id_seq',1);
CREATE TABLE cons_dates ("id" serial NOT NULL PRIMARY KEY ,"user_id" numeric DEFAULT 0,"con_id" numeric DEFAULT 0,"date" date ) WITH OIDS;
SELECT setval('cons_dates_id_seq',1);
CREATE TABLE dnote_nitems ("id" serial NOT NULL PRIMARY KEY ,"noteid" numeric DEFAULT 0,"cod" varchar ,"des" varchar ,"qty" numeric DEFAULT 0,"dqty" numeric DEFAULT 0,"unitcost" numeric(16, 2) DEFAULT 0,"amt" numeric(16, 2) DEFAULT 0,"csprice" numeric(16, 2) DEFAULT 0,"div" numeric DEFAULT 0) WITH OIDS;
SELECT setval('dnote_nitems_id_seq',1);
CREATE TABLE document ("docid" serial NOT NULL PRIMARY KEY ,"typeid" varchar ,"typename" varchar ,"xin" varchar ,"docref" varchar ,"docdate" date ,"docname" varchar ,"filename" varchar ,"mimetype" varchar ,"descrip" varchar ,"docu" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
SELECT setval('document_docid_seq',1);
CREATE TABLE stock ("stkid" serial NOT NULL PRIMARY KEY ,"stkcod" varchar ,"stkdes" varchar ,"prdcls" varchar ,"csamt" numeric DEFAULT 0,"units" numeric DEFAULT 0,"buom" varchar ,"suom" varchar ,"rate" numeric DEFAULT 0,"minlvl" numeric DEFAULT 0,"maxlvl" numeric DEFAULT 0,"selamt" numeric DEFAULT 0,"accid" numeric DEFAULT 0,"catid" numeric DEFAULT 0,"ordered" numeric DEFAULT 0,"whid" numeric DEFAULT 0,"shelf" varchar ,"row" varchar ,"alloc" numeric DEFAULT 0,"blocked" varchar ,"type" varchar ,"catname" varchar ,"classname" varchar ,"com" numeric DEFAULT 0,"bar" varchar ,"div" numeric DEFAULT 0,"bp" numeric DEFAULT 0,"margin" numeric DEFAULT 0,"vatded" varchar ,"exvat" varchar ,"csprice" numeric(16, 2) DEFAULT 0,"serd" varchar ,"lcsprice" numeric(16, 2) DEFAULT 0,"vatcode" int4 DEFAULT 0,"supplier1" int4 DEFAULT 0,"supplier2" int4 DEFAULT 0,"supplier3" int4 DEFAULT 0,"serial" varchar ,"serno" varchar ,"markup" numeric(16, 2) DEFAULT 0,"warranty" varchar ,"rfidtype" varchar ,"rfidfreq" varchar ,"rfidrate" varchar ,"size" varchar ,"post_production" varchar ,"treatment" varchar ) WITH OIDS;
SELECT setval('stock_stkid_seq',1);
CREATE TABLE usradd ("id" serial NOT NULL PRIMARY KEY ,"username" varchar ,"name" varchar ,"email" varchar ,"cell" varchar ) WITH OIDS;
SELECT setval('usradd_id_seq',1);
CREATE TABLE serial3 ("stkid" numeric DEFAULT 0,"serno" varchar ,"rsvd" varchar ,"warranty" date ) WITH OIDS;
CREATE TABLE settlement_sup ("supplier" numeric DEFAULT 0,"amt" numeric(16, 2) DEFAULT 0,"setamt" numeric(16, 2) DEFAULT 0,"setvat" varchar DEFAULT ''::character varying,"setvatamt" numeric(16, 2) DEFAULT 0,"setvatcode" numeric DEFAULT 0,"tdate" date ,"sdate" date ,"refnum" varchar DEFAULT ''::character varying) WITH OIDS;
CREATE TABLE supp_grpowners ("grpid" numeric DEFAULT 0,"supid" numeric DEFAULT 0,"id" serial NOT NULL PRIMARY KEY ) WITH OIDS;
SELECT setval('supp_grpowners_id_seq',1);
CREATE TABLE projects ("id" serial NOT NULL PRIMARY KEY ,"project_name" varchar ,"code" varchar ) WITH OIDS;
SELECT setval('projects_id_seq',3);
INSERT INTO projects ("id","project_name","code") VALUES('1','No Project','');
INSERT INTO projects ("id","project_name","code") VALUES('3','Project 1','aaaa');
CREATE TABLE subsistence ("id" serial NOT NULL PRIMARY KEY ,"name" varchar ,"in_republic" varchar ,"meals" varchar ,"accid" int8 DEFAULT 0,"div" int8 DEFAULT 0) WITH OIDS;
SELECT setval('subsistence_id_seq',1);
CREATE TABLE buditems ("budid" numeric DEFAULT 0,"id" numeric DEFAULT 0,"prd" numeric DEFAULT 0,"amt" numeric(16, 2) DEFAULT 0) WITH OIDS;
CREATE TABLE diary_locations ("id" serial NOT NULL PRIMARY KEY ,"location" varchar ) WITH OIDS;
SELECT setval('diary_locations_id_seq',1);
CREATE TABLE compinfo ("compname" varchar ,"slogan" varchar ,"logoimg" varchar ,"addr1" varchar ,"addr2" varchar ,"addr3" varchar ,"addr4" varchar ,"paddr1" varchar ,"paddr2" varchar ,"paddr3" varchar ,"pcode" numeric DEFAULT 0,"tel" varchar ,"fax" varchar ,"vatnum" varchar ,"regnum" varchar ,"imgtype" varchar ,"img" text ,"div" numeric DEFAULT 0,"paye" varchar ,"terms" varchar ,"postcode" varchar ,"img2" text ,"imgtype2" varchar ,"logoimg2" varchar ,"diplomatic_indemnity" varchar ,"sdl" varchar DEFAULT ''::character varying,"uif" varchar DEFAULT ''::character varying) WITH OIDS;
CREATE TABLE salded_scales ("id" serial NOT NULL PRIMARY KEY ,"saldedid" int4 DEFAULT 0,"scale_from" numeric(16, 2) DEFAULT 0,"scale_to" numeric(16, 2) DEFAULT 0,"scale_amount" numeric(16, 2) DEFAULT 0) WITH OIDS;
SELECT setval('salded_scales_id_seq',1);
CREATE TABLE credit_invoices ("ordnum" int4 DEFAULT 0,"cusname" varchar ,"addr1" varchar ,"addr2" varchar ,"addr3" varchar ,"tel" varchar ,"fax" varchar ,"invnum" varchar ,"orddate" date ,"invdate" date ,"orddes" varchar ,"email" varchar ,"grdtot" numeric DEFAULT 0,"salesrep" varchar ,"stockacc" varchar ,"terms" numeric DEFAULT 0,"cusnum" numeric DEFAULT 0,"div" numeric DEFAULT 0) WITH OIDS;
CREATE TABLE invc ("id" serial NOT NULL PRIMARY KEY ,"cid" int4 DEFAULT 0,"inv" int4 DEFAULT 0,"amount" numeric(16, 2) DEFAULT 0) WITH OIDS;
SELECT setval('invc_id_seq',1);
CREATE TABLE saved_vat201 ("id" serial NOT NULL PRIMARY KEY ,"returnname" varchar ,"from_date" date ,"to_date" date ,"system_date" date ,"registration_number" varchar ,"enquire_telephone" varchar ,"client_data1" varchar ,"rendering_date" varchar ,"client_data2" varchar ,"payment_amount" varchar ,"client_data3" varchar ,"remittance_rec_date" varchar ,"client_data4" varchar ,"client_data5" varchar ,"payment_method" varchar ,"area" varchar ,"taxperiod" varchar ,"trading_name" varchar ,"acc_number1" varchar ,"acc_number2" varchar ,"acc_number3" varchar ,"acc_number4" varchar ,"tax_period_end1" varchar ,"tax_period_end2" varchar ,"date_received" varchar ,"vat_registration_number" varchar ,"vat_area" varchar ,"vat_area2" varchar ,"field_1" varchar ,"field_4" varchar ,"field_1a" varchar ,"field_4a" varchar ,"field_2" varchar ,"field_3" varchar ,"field_5" varchar ,"field_6" varchar ,"field_7" varchar ,"field_8" varchar ,"field_9" varchar ,"field_10" varchar ,"field_11" varchar ,"field_12" varchar ,"field_13" varchar ,"field_14" varchar ,"field_15" varchar ,"field_16" varchar ,"field_17" varchar ,"field_18" varchar ,"field_19" varchar ,"field_20" varchar ,"field_25" varchar ,"field_26" varchar ,"field_27" varchar ,"field_28" varchar ,"field_26_1" varchar ,"field_29" varchar ,"field_31" varchar ,"field_32" varchar ,"field_33" varchar ,"field_32_1" varchar ,"field_34" varchar ,"field_36" varchar ,"field_37" varchar ,"field_38" varchar ,"field_37_1" varchar ,"field_39" varchar ,"field_40" varchar ,"contact_telno" varchar ,"contact_capacity" varchar ,"contact_date" varchar ) WITH OIDS;
SELECT setval('saved_vat201_id_seq',1);
CREATE TABLE workshop_settings ("id" serial NOT NULL PRIMARY KEY ,"setting" varchar ,"value" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
SELECT setval('workshop_settings_id_seq',1);
CREATE TABLE emp_attendance ("id" serial NOT NULL PRIMARY KEY ,"user_id" numeric DEFAULT 0,"in_out" numeric DEFAULT 0) WITH OIDS;
SELECT setval('emp_attendance_id_seq',1);
CREATE TABLE locks ("id" serial NOT NULL PRIMARY KEY ,"lockid" int4 DEFAULT 0) WITH OIDS;
SELECT setval('locks_id_seq',1);
CREATE TABLE inv_items ("id" serial NOT NULL PRIMARY KEY ,"invid" numeric DEFAULT 0,"whid" numeric DEFAULT 0,"stkid" numeric DEFAULT 0,"qty" numeric(16, 2) DEFAULT 0,"ss" varchar ,"div" numeric DEFAULT 0,"noted" numeric DEFAULT 0,"amt" numeric(16, 2) DEFAULT 0,"discp" numeric(16, 2) DEFAULT 0,"disc" numeric(16, 2) DEFAULT 0,"unitcost" numeric(16, 2) DEFAULT 0,"serno" varchar ,"ebp" numeric DEFAULT 0,"invp" numeric(16, 2) DEFAULT 0,"invup" numeric(16, 2) DEFAULT 0,"hidden" varchar ,"funitcost" numeric(16, 2) DEFAULT 0,"famt" numeric(16, 2) DEFAULT 0,"pinv" numeric(16, 2) DEFAULT 0,"vatcode" int4 DEFAULT 0,"description" varchar ,"account" int4 DEFAULT 0,"del" numeric(16, 2) DEFAULT 0,"dem1" numeric(16, 2) DEFAULT 0,"dem2" numeric(16, 2) DEFAULT 0) WITH OIDS;
SELECT setval('inv_items_id_seq',1);
CREATE TABLE sub_projects ("id" serial NOT NULL PRIMARY KEY ,"sub_project_name" varchar ,"project_id" numeric DEFAULT 0) WITH OIDS;
SELECT setval('sub_projects_id_seq',3);
INSERT INTO sub_projects ("id","sub_project_name","project_id") VALUES('1','No Sub Project','1');
INSERT INTO sub_projects ("id","sub_project_name","project_id") VALUES('3','Sub Project 1','3');
CREATE TABLE titles ("id" serial NOT NULL PRIMARY KEY ,"title" varchar DEFAULT ''::character varying) WITH OIDS;
SELECT setval('titles_id_seq',6);
INSERT INTO titles ("id","title") VALUES('1','Mr');
INSERT INTO titles ("id","title") VALUES('2','Mrs');
INSERT INTO titles ("id","title") VALUES('3','Miss');
INSERT INTO titles ("id","title") VALUES('4','Prof');
INSERT INTO titles ("id","title") VALUES('5','Dr');
INSERT INTO titles ("id","title") VALUES('6','Rev');
CREATE TABLE cancelled_cord ("username" varchar ,"date" date ,"deptid" numeric DEFAULT 0,"deptname" varchar ,"sordid" numeric DEFAULT 0,"div" numeric DEFAULT 0) WITH OIDS;
CREATE TABLE ch ("id" serial NOT NULL PRIMARY KEY ,"comp" varchar ,"code" varchar ,"des" varchar ,"f" varchar ,"t" varchar ,"date" date ) WITH OIDS;
SELECT setval('ch_id_seq',1);
CREATE TABLE dispatch_type_values ("id" serial NOT NULL PRIMARY KEY ,"type_id" numeric DEFAULT 0,"value_name" varchar ) WITH OIDS;
SELECT setval('dispatch_type_values_id_seq',1);
CREATE TABLE dnote_items ("id" serial NOT NULL PRIMARY KEY ,"noteid" numeric DEFAULT 0,"whid" numeric DEFAULT 0,"stkid" numeric DEFAULT 0,"qty" numeric DEFAULT 0,"dqty" numeric DEFAULT 0,"unitcost" numeric(16, 2) DEFAULT 0,"amt" numeric(16, 2) DEFAULT 0,"div" numeric DEFAULT 0) WITH OIDS;
SELECT setval('dnote_items_id_seq',1);
CREATE TABLE scr ("id" serial NOT NULL PRIMARY KEY ,"inv" int4 DEFAULT 0,"stkid" int4 DEFAULT 0,"invid" int4 DEFAULT 0,"amount" numeric(16, 4) DEFAULT 0) WITH OIDS;
SELECT setval('scr_id_seq',1);
CREATE TABLE departments ("id" serial NOT NULL PRIMARY KEY ,"department" varchar ,"date_added" date ,"div" numeric DEFAULT 0) WITH OIDS;
SELECT setval('departments_id_seq',3);
INSERT INTO departments ("id","department","date_added","div") VALUES('2','Temporary','2006-08-03','0');
INSERT INTO departments ("id","department","date_added","div") VALUES('1','Permanent','2006-08-03','0');
CREATE TABLE non_purchases_account_list ("id" serial NOT NULL PRIMARY KEY ,"accid" numeric DEFAULT 0,"accname" varchar DEFAULT ''::character varying) WITH OIDS;
SELECT setval('non_purchases_account_list_id_seq',1);
CREATE TABLE branches ("div" serial NOT NULL PRIMARY KEY ,"brancod" varchar ,"branname" varchar ,"brandet" varchar ) WITH OIDS;
SELECT setval('branches_div_seq',2);
INSERT INTO branches ("div","brancod","branname","brandet") VALUES('2','HO','Head Office','Head Office');
CREATE TABLE req_new ("for_user" varchar ) WITH OIDS;
CREATE TABLE salset ("id" serial NOT NULL PRIMARY KEY ,"name" varchar ) WITH OIDS;
SELECT setval('salset_id_seq',1);
CREATE TABLE seq ("type" varchar ,"last_value" numeric DEFAULT 0,"div" numeric DEFAULT 0) WITH OIDS;
INSERT INTO seq ("type","last_value","div") VALUES('inv','0','2');
INSERT INTO seq ("type","last_value","div") VALUES('pur','0','2');
INSERT INTO seq ("type","last_value","div") VALUES('note','0','2');
INSERT INTO seq ("type","last_value","div") VALUES('quo','0','2');
INSERT INTO seq ("type","last_value","div") VALUES('cred_note','0','2');
INSERT INTO seq ("type","last_value","div") VALUES('receipt','0','2');
CREATE TABLE serial9 ("stkid" numeric DEFAULT 0,"serno" varchar ,"rsvd" varchar ,"warranty" date ) WITH OIDS;
CREATE TABLE diary_privileges ("diary_owner" varchar ,"priv_owner" varchar ,"privilege" varchar ,"id" serial NOT NULL PRIMARY KEY ) WITH OIDS;
SELECT setval('diary_privileges_id_seq',1);
CREATE TABLE quote_items ("quoid" numeric DEFAULT 0,"whid" numeric DEFAULT 0,"stkid" numeric DEFAULT 0,"qty" numeric DEFAULT 0,"div" numeric DEFAULT 0,"amt" numeric(16, 2) DEFAULT 0,"discp" numeric(16, 2) DEFAULT 0,"disc" numeric(16, 2) DEFAULT 0,"unitcost" numeric(16, 2) DEFAULT 0,"funitcost" numeric(16, 2) DEFAULT 0,"famt" numeric(16, 2) DEFAULT 0,"vatcode" int4 DEFAULT 0,"description" varchar ,"account" int4 DEFAULT 0) WITH OIDS;
CREATE TABLE salpaid ("id" serial NOT NULL PRIMARY KEY ,"empnum" numeric DEFAULT 0,"month" varchar ,"bankid" numeric DEFAULT 0,"salary" numeric DEFAULT 0,"comm" numeric DEFAULT 0,"uifperc" numeric DEFAULT 0,"uif" numeric DEFAULT 0,"payeperc" numeric DEFAULT 0,"paye" numeric DEFAULT 0,"totded" numeric DEFAULT 0,"totben" numeric DEFAULT 0,"totallow" numeric DEFAULT 0,"loanins" numeric DEFAULT 0,"div" numeric DEFAULT 0,"showex" varchar ,"display" varchar ,"saldate" date ,"week" int4 DEFAULT 0,"totded_employer" numeric DEFAULT 0,"cyear" varchar ,"true_ids" int4 ,"hovert" numeric DEFAULT 0,"novert" numeric DEFAULT 0,"taxed_sal" numeric DEFAULT 0,"tot_fringe" numeric DEFAULT 0,"hours" numeric DEFAULT 0,"salrate" numeric DEFAULT 0,"bonus" numeric DEFAULT 0) WITH OIDS;
SELECT setval('salpaid_id_seq',1);
CREATE TABLE supp_payment_print_items ("id" serial NOT NULL PRIMARY KEY ,"payment_id" numeric DEFAULT 0,"supid" numeric DEFAULT 0,"purchase" varchar DEFAULT ''::character varying,"outstanding_amt" numeric(16, 2) DEFAULT 0,"tdate" date ,"sdate" date ,"paid_amt" numeric(16, 2) DEFAULT 0,"sett_amt" numeric(16, 2) DEFAULT 0) WITH OIDS;
SELECT setval('supp_payment_print_items_id_seq',1);
CREATE TABLE ss8 ("stock" int4 DEFAULT 0,"code" varchar ,"div" numeric DEFAULT 0,"active" varchar DEFAULT 'yes'::character varying) WITH OIDS;
CREATE TABLE email_dates ("id" serial NOT NULL PRIMARY KEY ,"user_id" numeric DEFAULT 0,"message_id" numeric DEFAULT 0,"date" date ) WITH OIDS;
SELECT setval('email_dates_id_seq',1);
CREATE TABLE emp_loanarchive ("id" serial NOT NULL PRIMARY KEY ,"empnum" numeric DEFAULT 0,"loanamt" numeric DEFAULT 0,"loaninstall" numeric DEFAULT 0,"loanint" numeric DEFAULT 0,"loanperiod" numeric DEFAULT 0,"loandate" date ,"donedata" date ,"div" numeric DEFAULT 0,"loan_type" numeric DEFAULT 0,"status" varchar ,"archdate" varchar ) WITH OIDS;
SELECT setval('emp_loanarchive_id_seq',1);
CREATE TABLE movinv ("id" serial NOT NULL PRIMARY KEY ,"invtype" varchar ,"invnum" numeric DEFAULT 0,"prd" numeric DEFAULT 0,"docref" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
SELECT setval('movinv_id_seq',1);
CREATE TABLE scripts ("id" serial NOT NULL PRIMARY KEY ,"script" varchar ,"name" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
SELECT setval('scripts_id_seq',1186);
INSERT INTO scripts ("id","script","name","div") VALUES('116','VIEW STOCK','stock-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1','NEW CONTACT','new_con.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('2','SET ACCOUNT CREATION','set.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('3','NEW PURCHASE','purchase-new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('4','CASH BOOK ANALYSIS','banked.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('5','ACCOUNTING INDEX','index-accounts.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('6','ADD JOURNAL ENTRY','trans-new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('7','ADD MULTIPLE JOURNAL ENTRIES','multi-trans.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('8','ACCOUNTING REPORTS INDEX','index-reports.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('9','BANK RECONCILIATION','bank-recon.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('10','LIST OUTSTANDING PAYMENTS/RECEIPTS','not-banked.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('11','VIEW CURRENT PERIOD','period-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('12','VAT REPORT','vat-report.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('13','VIEW STORES','whouse-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('15','VIEW SAVED INCOME STATEMENTS','income-stmnt-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('16','GENERATE BALANCE SHEET','bal-sheet.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('17','VIEW SAVED BALANCE SHEETS','bal-sheet-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('18','JOURNAL ENTRIES REPORT','alltrans.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('19','JOURNAL ENTRIES REPORT PER CATEGORY','cat-trans.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('20','LIST ALL CATEGORIES AND ACCOUNTS','allcat.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('21','CONFIGURE BALANCE SHEET','set-bal-sheet.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('22','ADD TRANSACTION TO BATCH','trans-batch-new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('23','VIEW BATCH ENTRIES','batch-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('24','EDIT BATCH TRANSACTION','batch-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('25','REMOVE BATCH TRANSACTION','batch-rem.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('26','PROCESS/REMOVE BATCH TRANSACTIONS','batch-procs.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('27','ADD NEW ACCOUNT','acc-new2.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('28','VIEW ACCOUNTS','acc-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('29','ADD ACCOUNT CATEGORY','accat-new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('30','VIEW ACCOUNT CATEGORIES','accat-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('31','SET FINANCIAL YEAR NAMES','finyearnames-new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('32','VIEW FINANCIAL YEAR NAMES','finyearnames-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('33','SET FINANCIAL YEAR RANGE','finyear-range.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('34','OPEN FINANCIAL YEAR','yr-open.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('35','CLOSE PERIOD','prd-close.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('36','CLOSE YEAR','yr-close.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('37','ADMIN INDEX','index-admin.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('38','ADD USER','admin-usradd.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('39','VIEW USERS','admin-usrview.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('40','EDIT USER','admin-usredit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('41','REMOVE USER','admin-usrrem.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('42','COMPANY DETAILS','compinfo-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('43','ADD USER DEPARTMENT','admin-deptadd.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('44','VIEW USER DEPARTMENTS','admin-deptview.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('45','EDIT USER DEPARTMENT','admin-deptedit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('46','REMOVE USER DEPARTMENT','admin-deptrem.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('47','SET DEFAULT ACCOUNTS','defdep-slct.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('48','ADD PREVIOUS YEAR TRANSACTION','yr-prd-trans-new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('49','VIEW PREVIOUS YEAR TRANSACTIONS','trans-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('50','GENERATE PREVIOUS YEAR TRAIL BALANCE','trail-bal.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('52','GENERATE PREVIOUS YEAR BALANCE SHEET','balance-sheet.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('53','PURCHASES INDEX','index-pchs.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('54','VIEW PURCHASES','purchase-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('55','VIEW PURCHASE DETIALS','purch-det.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('56','RECIEVE PURCHASE','purch-recv.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('57','CANCEL PURCHASE','purch-cancel.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('58','NEW INTERNATIONAL PURCHASE','purch-int-new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('59','VIEW INTERNATIONAL PURCHASES','purch-int-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('60','ADD SUPPLIER','supp-new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('61','VIEW SUPPLIERS','supp-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('62','VIEW SUPPLIER DETAILS','supp-det.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('63','EDIT SUPPLIER','supp-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('64','REMOVE SUPPLIER','supp-rem.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('65','SALES INDEX','index-sales.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('66','NEW INVOICE','cust-credit-stockinv.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('67','FIND CONTACT','find_con.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('68','COMPLETE/PRINT INVOICE','invoice-print.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('69','VIEW INVOICES','invoice-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('70','VIEW INVOICE DETAILS','invoice-details.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('71','CREDIT NOTE','invoice-note.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('72','VIEW INCOMPLETE INVOICES','invoice-unf-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('73','VIEW CANCELED INVOICES','invoice-canc-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('74','NEW QUOTE','quote-new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('75','VIEW QUOTES','quote-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('76','VIEW QUOTE DETIALS','quote-details.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('77','ACCEPT QUOTE','quote-accept.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('78','CANCEL QUOTE','quote-cancel.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('79','VIEW INCOMPLETE QUOTES','quote-unf-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('80','VIEW CANCELED QUOTES','quote-canc-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('81','ADD CUSTOMER','customers-new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('82','VIEW CUSTOMERS','customers-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('83','VIEW CUSTOMER DETAILS','cust-det.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('84','EDIT CUSTOMER','cust-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('85','PRODUCE CUSTOMER STATEMENT','cust-stmnt.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('86','BLOCK CUSTOMER','cust-block.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('87','UNBLOCK CUSTOMER','cust-unblock.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('88','REMOVE CUSTOMER','cust-rem.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('89','INCREASE ALL SELLING PRICES','whouse-selamt-inc.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('90','SET INVOICE STARTING NUMBER','invid-set.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('91','ADD CUSTOMER/SUPPLIER DEPARTMENT','dept-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('92','VIEW CUSTOMER/SUPPLIER DEPARTMENTs','dept-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('93','EDIT CUSTOMER/SUPPLIER DEPARTMENT','dept-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('94','REMOVE CUSTOMER/SUPPLIER DEPARTMENT','dept-rem.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('95','ADD SALES REP','salesp-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('96','VIEW SALES REPS','salesp-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('97','EDIT SALES REP','salesp-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('98','REMOVE SALES REP','salesp-rem.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('99','ADD CUSTOMER CATEGORY','cat-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('100','VIEW CUSTOMER CATEGORIES','cat-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('101','EDIT CUSTOMER CATEGORY','cat-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('102','REMOVE CUSTOMER CATEGORY','cat-rem.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('103','ADD CUSTOMER CLASSIFICATION','class-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('104','VIEW CUSTOMER CLASSIFICATIONS','class-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('105','ADD PRICE LIST','pricelist-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('106','EDIT PRICE LIST','pricelist-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('107','COPY PRICE LIST','pricelist-copy.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('108','REMOVE PRICE LIST','pricelist-rem.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('109','SET VAT ACCOUNT','sales-link.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('110','CUSTOMER DISCOUNT/DELIVERY REPORT','invoice-disc-rep.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('111','STOCK INDEX','index-stock.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('112','ADD STOCK CATEGORY','stockcat-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('113','VIEW STOCK CATEGORY','stockcat-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('114','VIEW STOCK CATEGORY DETAILS','stockcat-det.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('115','EDIT STOCK CATEGORIES','stockcat-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('117','STOCK REPORT','stock-amt-det.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('118','STOCK DETAILS','stock-det.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('119','EDIT STOCK','stock-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('120','BLOCK STOCK','stock-block.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('121','UNBLOCK STOCK','stock-unblock.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('122','REMOVE STOCK','stock-rem.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('123','SHOW STOCK ALLOCATION','stock-alloc.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('124','FIND STOCK','stock-search.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('125','ADD STORE','whouse-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('126','ADD STOCK CLASSIFICATION','stockclass-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('127','VIEW STOCK CLASSIFICATIONS','stockclass-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('128','EDIT STOCK CLASSIFICATIONS','stockclass-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('129','REMOVE STOCK CLASSIFICATIONS','stockclass-rem.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('130','SALARIES INDEX','index-salaries.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('131','PROCESS SALARY','salaries-staff.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('132','VIEW ALL PROCESSED SALARIES','payslips.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('133','VIEW PROCESSED SALARIES','payslip.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('134','EMPLOYEE RESOURCES INDEX','employee-resources.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('135','APPROVE LEAVE','employee-leave-approve.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('136','ADD SALARY DEDUCTION','salded-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('137','VIEW SALARY DEDUCTIONS','salded-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('138','ADD ALLOWANCE','allowance-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('139','VIEW ALLOWANCES','allowances-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('140','GRANT EMPLOYEE LOAN','loan-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('141','GENERAL SALARY SETTINGS','settings-acc-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('142','ADD EMPLOYEE','admin-employee-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('143','VIEW EMPLOYEES','admin-employee-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('144','EDIT EMPLOYEE','admin-employee-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('145','REMOVE EMPLOYEE','admin-employee-rem.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('146','SET SALARY ACCOUNT LINKS','sal-link.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('147','BANKING INDEX','index-bankaccnt.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('148','ADD BANK ACCOUNT','bankacct-new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('149','VIEW BANK ACCOUNTS','bankacct-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('150','EDIT BANK ACCOUNT','bankacct-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('151','REMOVE BANK ACCOUNT','bankacct-rem.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('152','ADD BANK TRANSACTIONS','bank-stmnt.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('153','CASHBOOK INDEX','index-cashbook.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('154','ADD BANK PAYMENT','bank-pay-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('155','ADD BANK RECEIPT','bank-recpt-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('156','VIEW CASH BOOK','cashbook-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('157','CANCEL CHEQUE','cheq-cancel.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('158','CONTACTS INDEX','index_cons.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('159','VIEW CONTACTS','list_cons.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('160','VIEW CONTACT DETAILS','view_con.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('161','EDIT CONTACT','mod_con.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('162','REMOVE CONTACT','rem_con.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('163','LEAVE MESSAGES','req_gen.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('164','VIEW MESSAGES','view_req.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('165','ADD MULTIPLE TRANSACTIONS TO BATCH','trans-batch.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('166','SUPPLIER TRANSACTION REPORT','supp-tran-rep.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('167','EDIT ACCOUNT','acc-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('168','MOVE ACCOUNT BETWEEN CATEGORIES','acc-mov.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('169','REMOVE ACCOUNT','acc-rem.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('170','REMOVE ACCOUNT CATEGORY','accat-rem.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('171','DEBTORS AGE ANALYSIS','debt-age-analysis.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('172','CREDITORS AGE ANALYSIS','cred-age-analysis.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('173','JOURNAL ENTRIES REPORT PER ACCOUNT','acc-trans.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('174','JOURNAL ENTRIES REPORT PER MAIN ACCOUNT','accsub-trans.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('175','EDIT ACCOUNT CATEGORY','accat-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('176','GENERATE TRAIL BALANCE','trial_bal.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('177','VIEW SAVED TRAIL BALANCES','trial_bal-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('178','SUPPLIER STATEMENT','supp-stmnt.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('179','SUPPLIER/ACCOUNT JOURNAL ENTRY','supp-trans.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('180','CUSTOMER/ACCOUNT JOURNAL ENTRY','cust-trans.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('181','MESSAGES INDEX','index_reqs.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('182','CANCEL INCOMPLETE INVOICE','invoice-unf-cancel.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('183','VIEW INTERNATIONAL PURCHASE DETAILS','purch-int-det.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('184','CANCEL INTERNATIONAL PURCHASE','purch-int-cancel.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('185','RECEIVE INTERNATIONAL PURCHASE','purch-int-recv.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('186','VIEW PRICE LIST DETAILS','pricelist-det.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('187','VIEW PRICE LISTS','pricelist-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('188','ADD EMPLOYEE REPORT','employee-reports-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('189','VIEW EMPLOYEE REPORTS','employee-reports-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('190','ADD EMPLOYEE REPORT TYPE','report-type-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('191','VIEW EMPLOYEE LOANS','loans-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('192','ADD PAYE BRACKETS','paye-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('193','VIEW/EDIT PAYE BRACKETS','paye-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('194','SALARY SETTINGS INDEX','sal-settings.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('195','GENERATE PREVIOUS YEAR TRIAL BALANCE','trial-bal.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('196','STOCK REPORTS INDEX','stock-report.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('197','AVAILABLE STOCK REPORT','stock-avail.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('198','SALES SETTINGS INDEX','sales-settings.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('199','JOURNAL ENTRIES REPORT BY PERIOD RANGE','alltrans-prd.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('200','JOURNAL ENTRIES REPORT PER ACCOUNT BY PERIOD RANGE','acc-trans-prd.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('201','ADD BANK PAYMENT FOR SUPPLIERS','bank-pay-supp.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('202','VIEW EMPLOYEE DETAILS','admin-employee-detail.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('203','VIEW SAVED BANK RECONS','bank-recon-saved.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('204','PRINT SAVED BANK RECONS','bank-recon-print.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('205','STOCK SETTINGS INDEX','stock-settings.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('206','DEFAULT ACCOUNT CREATION','acc-new-dec.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('207','ADD NEW HIGH SPEED INPUT LEDGER','ledger-new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('208','VIEW HIGH SPEED INPUT LEDGERS','ledger-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('209','EDIT HIGH SPEED INPUT LEDGER','ledger-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('210','REMOVE HIGH SPEED INPUT LEDGER','ledger-rem.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('211','RUN HIGH SPEED INPUT LEDGER','ledger-run.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('212','VIEW HIGH SPEED INPUT LEDGER DETAILS','ledger-det.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('213','EDIT SALARY DEDUCTIONS','salded-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('214','REMOVE SALARY DEDUCTIONS','salded-rem.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('215','EDIT EMPLOYEE LOANS','loan-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('216','REMOVE EMPLOYEE LOANS','loan-rem.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('217','EDIT PAYE BRACKETS','admin-paye-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('218','REMOVE PAYE BRACKETS','admin-paye-rem.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('219','EDIT STORE','whouse-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('220','REMOVE STORES','whouse-rem.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('221','EDIT ALLOWANCE','allowance-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('222','REMOVE ALLOWANCE','allowance-rem.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('223','PURCHASES REPORTS INDEX','pchs-reports.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('224','VIEW PAID INVOICES','invoice-view-prd.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('225','VIEW PAID INVOICES DETAILS','invoice-details-prd.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('226','VIEW RECEIVED PURCHASES','purchase-view-prd.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('227','VIEW RECEIVED PURHCASES DETAILS','purch-det-prd.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('228','VIEW RECEIVED INTERNATIONAL PURCHASES','purch-int-view-prd.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('229','VIEW RECEIVED INTERNATIONAL PURCHASES DETAILS','purch-int-det-prd.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('230','REPRINT A PRINTED INVOICE','invoice-reprint.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('231','ADD STOCK','stock-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('232','ALLOCATE STOCK BARCODE','pos.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('233','STOCK PRICE INCREASE','stock-price-inc.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('234','STOCK LEVES REPORT','stock-lvl-rep.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('235','STOCK TRANSFER','stock-transfer.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('236','NEW POINT OF SALES CASH INVOICE','pos-invoice-new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('237','EDIT POINT OF SALES CASH INVOICE','pos-invoice-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('238','PRINT POINT OF SALES CASH INVOICE','pos-invoice-print.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('239','LIST POINT OF SALES CASH INVOICE','pos-invoice-list.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('240','EDIT PURCHASE','purchase-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('241','EDIT SALES REP COMMISION','coms-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('242','SALES REP COMMISION REPORT','coms-report.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('243','VIEW COST PRICE OF ITEMS','cost-price-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('244','SET DEFAULT STORE','defwh-set.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('245','STOCK TAKING','stock-taking.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('246','SALES REPORTS','sales-reports.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('247','STOCK SALES REPORT','stock-sales-rep.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('248','SETTINGS INDEX','settings-index.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('249','EDIT CLIENT CLASS','class-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('250','REMOVE CLIENT CLASS','class-rem.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('251','ADMIN/ACCOUNTS SETTINGS INDEX','index-settings.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('252','VIEW POINT OF SALE CASH INVOICE','pos-invoice-details.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('253','ADD TRANSACTION (ONE DT/CT, MULTIPLE CT/DT) ','trans-new-sep.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('254','ACCEPT SALES ORDER','sorder-accept.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('255','VIEW CANCELLED SALES ORDERS','sorder-canc-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('256','CANCEL SALES ORDER','sorder-cancel.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('257','SALES ORDER DETAILS','sorder-details.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('258','ADD NEW SALES ORDER','sorder-new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('259','CANCEL INCOMPLETE SALES ORDER','sorder-unf-cancel.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('260','VIEW INCOMPLETE SALES ORDER','sorder-unf-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('261','VIEW SALES ODERS','sorder-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('262','REMOVE STOCK CATEGORY','stockcat-rem.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('263','ASSET LEDGER INDEX','index-assets.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('264','ADD ASSET','asset-new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('265','VIEW ASSETS','asset-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('266','REMOVE ASSETS','asset-rem.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('267','PRINT SAVED BALANCE SHEET','bal-sheet-print.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('268','PRINT SAVED INCOME STATEMENT','income-stmnt-print.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('269','PRINT SAVED TRIAL BALANCE','trial_bal-print.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('270','SET PETTY CASH LINK','cash-link.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('271','ADD PETTY CASH REQUISITION','petty-req-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('272','APPROVE PETTY CASH REQUISITION','petty-req-app.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('273','CANCEL PETTY CASH REQUISITION','petty-req-can.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('274','RECORD PETTY CASH RECEIPT','petty-req-recpt.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('275','TRANSFER FUNDS TO PETTY CASH','petty-trans.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('276','PETTY CASH REPORT','pettycash-rep.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('277','VIEW PETTY CASH BOOK','pettycashbook-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('278','AUDITING INDEX','index-audit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('279','PETTY CASH BOOK INDEX','index-pettycashbook.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('280','CANCEL UNFINISHED QUOTE','quote-unf-cancel.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('281','EDIT BALANCE SHEET CONFIGURATION','set-bal-sheet-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('282','ACCOUNT TRANSACTIONS EXECL EXPORT','acc-trans-prd-xls.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('283','SAME YEAR ACCOUNT TRANSACTIONS SPREADSHEET EXPORT','acc-trans-xls.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('284','MAIN ACCOUNTS TRANSACTIONS SPREADSHEET EXPORT','accsub-trans-xls.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('285','ALL ACCOUNTS AND CATEGORIES SPREADSHEET EXPORT','allcat-xls.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('286','ALL TRANSACTIONS PER PERIOD RANGE SPREADSHEET EXPORT','alltrans-prd-xls.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('287','ALL JOURNAL ENTRIES SPREADSHEET EXPORT','alltrans-xls.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('288','BALANCE SHEET SPREADSHEET EXPORT','bal-sheet-xls.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('289','BANK RECONS SPREADSHEET EXPORT','bank-recon-xls.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('290','CASHBOOK ANALYSIS SPREADSHEET EXPORT','banked-xls.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('291','CATEGORY TRANSACTIONS SPREADSHEET EXPORT','cat-trans-xls.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('292','CREDITORS ANALYSIS SPREADSHEET EXPORT','cred-age-analysis-xls.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('293','DEBTORS ANALYSIS SPREADSHEET EXPORT','debt-age-analysis-xls.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('294','INCOME STATEMENT SPREADSHEET EXPORT','income-stmnt-xls.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('295','OUTSTANDING ENTRIES SPREADSHEET EXPORT','not-banked-xls.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('296','TRIAL BALANCE SPREADSHEET EXPORT','trial-bal-xls.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('297','VAT REPORT SPREADSHEET EXPORT','vat-report-xls.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('298','BALANCE SHEET PDF EXPORT','bal-sheet-pdf.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('299','CUSTOMER STATEMENT PDF EXPORT','cust-pdf-stmnt.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('300','INCOME STATEMENT PDF EXPORT','income-stmnt-pdf.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('301','INVOICE REPRINT PDF EXPORT','invoice-pdf-reprint.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('302','TRIAL BALANCE PDF EXPORT','trial-bal-pdf.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('303','PURCHASE RETURN','purch-return.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('304','STOCK TRANSACTION','stock-balance.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('305','CHANGE SPLASH SCREEN','splash.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('306','MAINTENANCE INDEX','index-maint.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('307','DO MAINTENANCE','maint.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('308','MAKE BACKUP','backup-make.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('309','QUICK SETUP','setup.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('310','CUSTOMER STATEMENT BY DATE RANGE','cust-stmnt-date.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('311','ADD BANK RECEIPT FOR CUSTOMER INVOICES','bank-recpt-inv.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('312','APPLY FOR LEAVE','employee-leave-apply.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('313','VIEW LEAVE','employee-leave-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('314','CANCEL LEAVE','employee-leave-cancel.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('315','ADD NON STOCK PURCHASE','nons-purchase-new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('316','VIEW NON STOCK PURCHASES','nons-purchase-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('317','VIEW NON STOCK PURCHASE DETAILS','nons-purch-det.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('318','CANCEL NON STOCK PURCHASE','nons-purch-cancel.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('319','RECIEVE NON STOCK PURCHASE','nons-purch-recv.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('320','VIEW RECIEVED NON STOCK PURCHASE','nons-purchase-view-prd.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('321','VIEW RECIEVED NON STOCK PURCHASE DETAILS','nons-purch-det-prd.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('322','DOWNLOAD BACKUP','backup-download.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('323','VIEW AVAILABLE LEAVE FOR EMPLOYEES','employee-leave-avail.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('324','VIEW EMPLOYEES ON LEAVE','employee-onleave.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('325','POINT OF SALE SETTING','pos-set.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('326','EDIT INVOICE','invoice-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('327','VIEW PRINTED POINT OF SALE INVOICES','pos-invoice-view-prd.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('328','VIEW PRINTED POINT OF SALE INVOICE DETAILS','pos-invoice-details-prd.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('329','REPRINT POINT OF SALE INVOICE','pos-invoice-reprint-prd.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('330','VIEW TEMP/INVOICE NUMBER','find-num.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('331','ADD CONSIGNMENT ORDER','corder-new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('332','VIEW CONSIGNMENT ORDERS','corder-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('333','VIEW INCOMPLETE CONSIGNMENT ORDERS','corder-unf-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('334','VIEW CANCELED CONSIGNMENT ORDERS','corder-canc-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('335','VIEW CONSIGNMENT ORDER DETAILS','corder-details.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('336','CANCEL CONSIGNMENT ORDER','corder-cancel.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('337','ACCEPT CONSIGNMENT ORDER','corder-accept.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('338','PRINT CONSIGNMENT ORDER','corder-print.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('339','PRINT SALES ORDER','sorder-print.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('340','VIEW CANCELLED PURCHASES','purch-canc-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('341','CREDITORS INDEX','index-creditors.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('342','DEBTORS INDEX','index-debtors.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('343','SALES CONSIGNMENT INDEX','index-sales-consignment.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('344','SALES ORDER QUOTES','index-sales-quotes.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('345','SALES ORDER POSINVOICES','index-sales-posinvoices.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('346','SALES ORDER INDEX','index-sales-orders.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('347','MULTIPLE BANK PAYMENT','multi-bank-pay-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('348','MULTIPLE BANK RECEIPT','multi-bank-recpt-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('349','ADD NON STOCK INVOICE','nons-invoice-new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('350','VIEW NON STOCK INVOICES','nons-invoice-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('351','VIEW CREDIT NOTE','invoice-note-view-prd.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('352','ADD POS QUOTE','pos-quote-new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('353','VIEW POS QUOTES','pos-quote-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('354','VIEW CANCELLED POS QUOTES','pos-quote-canc-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('355','VIEW INCOMPLETE POS QUOTES','pos-quote-unf-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('356','MONTH END','month-end.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('357','STOCK TRANSFER - BRANCH ','stock-transfer-bran.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('358','VIEW STOCK IN TRANSIT','stock-transit-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('359','CALCULATE INTEREST','calc-int.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('360','SET INTEREST CALCULATION METHOD','set-int-type.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('361','SET AGE ANALISYS TYPE','set-debt-age.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('362','ADD INTEREST BRACKET','intbrac-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('363','VIEW INTEREST BRACKETS','intbrac-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('366','POS QUOTE DETAILS','pos-quote-details.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('367','EDIT INTEREST BRACKETS','intbrac-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('368','REMOVE INTEREST BRACKETS','intbrac-rem.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('369','CANCELL STOCK TRANSFER','stock-transit-can.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('370','DELETE STOCK FROM TRANSIT','stock-transit-del.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('371','VIEW NON STOCK INVOICE DETAILS','nons-invoice-det.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('372','PRINT NON STOCK INVOICE','nons-invoice-print.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('373','CANCELL POS QUOTE','pos-quote-cancel.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('374','BANK ACCOUNT TYPES','accounttype-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('376','CANCEL INCOMPLETE CONSIGNMENT ORDER','corder-unf-cancel.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('433','GENERATE PREVIOUS YEAR INCOME STATEMENT','income-stmnt.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('724','PRINT CUSTOMER LIST','cust-list.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('725','PRINT SUPPLIER LIST','supp-list.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('726','PERIOD RANGE GENERAL LEDGER','ledger-prd.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('727','BLOCK SUPPLIER','supp-block.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('728','UNBLOCK SUPPLIER','supp-unblock.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('729','DETAILED GENERAL LEDGER','alltrans-refnum.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('730','BANK PETTY CASH','petty-bank.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('731','RECEIVE PETTY CASH FROM SUPPLIER','petty-recpt-supp.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('732','RECEIVE PETTY CASH FROM CUSTOMER','petty-recpt-cust.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('733','PAY PETTY CASH TO SUPPLIER','petty-pay-supp.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('734','PAY PETTY CASH TO CUSTOMER','petty-pay-cust.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('759','ADDING NEW ACCES SCRIPTS','acctab-new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('760','ASSET DEPRECIATION','asset-dep.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('761','ASSET REPORT','asset-rep.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('762','EDIT ASSET GROUP','assetgrp-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('763','ADD ASSET GROUPS','assetgrp-new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('764','REMOVE ASSET GROUP','assetgrp-rem.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('765','VIEW ASSET GROUPS','assetgrp-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('766','ADD BANK RECPT FROM SUPPLIERS','bank-recpt-supp.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('767','DEBTORS LEDGER','cust-ledger.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('768','PRINT CUSTOMER STATEMENTS','cust-pdf-stmnt-all.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('769','PRINT CUSTOMER INVOICES','invoice-pdf-cust.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('771','YEAR REVIEW','ledger-ytd.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('772','GENERAL LEDGER','ledger.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('773','EDIT PETTY REQUISITION','petty-req-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('774','PRINT QUOTES IN PDF','quote-pdf-print.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('775','PRINT QUOTES','quote-print.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('776','EDIT RECURING TRANSACTION','rectrans-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('777','ADD RECURING TRANSACTION','rectrans-new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('778','PROCCESS RECURING TRANSACTIONS','rectrans-run.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('779','VIEW RECURING TRANSACTIONS','rectrans-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('780','INVENTORY LEDGER','stock-ledger.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('781','CREDITORS LEDGER','supp-ledger.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('782','TRANSACTION BY REFNO','trans-amt.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('783','NEW COMPANY','company-new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('784','VIEW COMPANY','company-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('785','ADD BRANCH','admin-branadd.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('786','VIEW BRANCH','admin-branview.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('788','EXPORT COMPANY','company-export.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('789','IMPORT COMPANY','company-import.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('819','ADD DOCUMENT','doc-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('820','DOWNLOAD DOCUMENTS','doc-dload.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('821','EDIT DOCUMENTS','doc-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('822','REMOVE DOCUMENTS','doc-rem.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('823','VIEW DOCUMENTS','doc-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('824','ADD DOCUMENT TYPES','doctype-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('825','REMOVE DOCUMENT TYPES','doctype-rem.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('826','VIEW DOCUMENT TYPES','doctype-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('827','MULTI COMPANY/BRANCH REPORTS','index-multi-reports.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('828','EDIT UNIT COST ON INVOICES','invoice-unitcost-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('829','ACCEPT NON STOCK QUOTES','nons-quote-acc.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('830','NON STOCK QUOTE DETAILS','nons-quote-det.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('831','NEW NON STOCK QUOTE','nons-quote-new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('832','PRINT NON STOCK QUOTE','nons-quote-print.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('833','VIEW NON STOCK QUOTES','nons-quote-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('834','SET STOCK SELLING AMOUNT VAT TYPE','set-vat-type.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('835','NON STOCK CREDIT NOTE ','nons-invoice-note.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('836','VIEW NONS STOCK CREDIT NOTES','nons-invoice-note-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('837','NON STOCK CREDIT NOTE DETAILS','nons-invoice-note-det.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('838','REPRINT NON STOCK CREDIT NOTES','nons-invoice-note-reprint.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('839','FIND INVOICES','invoice-search.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('840','VIEW LEFT EMPLOYEES','admin-lemployee-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('841','ALLOW USER TO CHANGE OWN PASSWORD','admin-usrpasswd.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('842','VIEW EDIT COMPANY TERMS','company-terms.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('843','ADD COST CENTER','costcenter-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('844','EDIT COST CENTER','costcenter-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('845','VIEW DETAILED COST CENTER REPORT','costcenter-rep-det.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('846','COST CENTER REPORT','costcenter-rep.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('847','ADD COST CENTER MANUAL TRANSACTION','costcenter-tran.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('848','VIEW COST CENTERS','costcenter-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('849','VIEW EMPLOYEE IMAGE','employee-view-image.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('850','LINKED PURCHASE DETAILS','lnons-purch-det.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('851','NEW LINKED PURCHASE','lnons-purch-new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('852','RECV LINKED PURCHASE','lnons-purch-recv.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('853','NEW ASSET PURCHASE','nonsa-purchase-new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('854','ASSET PURCHASE DETAILS','nonsa-purch-det.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('855','RECEIVE ASSET PURCHASE','nonsa-purch-recv.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('856','POS USER REPORT','pos-report-user.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('857','PRINT POS INVOICE','pos-slip.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('858','PRINT SALARY PAYSLIP','payslip-print.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('859','APPROVE PURCHASE','purch-apprv.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('860','COMPLETE PARLTY RECEIVED PURCHASE','purch-complete.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('861','RECEIVE PURCHASE BY NUMBER','purch-recv-purnum.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('862','RECURRING INVOICE DETAILS','rec-invoice-details.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('863','NEW RECURRING INVOICE','rec-invoice-new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('864','REMOVE RECURRING INVOICE','rec-invoice-rem.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('865',' INVOICE FROM RECURRING INVOICE','rec-invoice-run.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('866','VIEW RECURRING INVOICES','rec-invoice-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('867','SET COST CENTER USAGE','set-costcenter-use.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('868','SET BANK DETAILS ACCOUNT','set-inv-bankdetails.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('869','ALLOCATE SERIALS TO STOCK','stock-serials.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('870','ALLOW INVOICE TO LIMITED CUSTOMERS','invoice-limit-override.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('871','ADD CREDIT CARD','creditcard-new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('872','ADD PETROL CARD','petrolcard-new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('873','ADD MULTIPLE PETTY CASH REQUISITIONS','petty-req-multi-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('874','EDIT CREDIT CARD','creditcard-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('875','EDIT PETROL CARD','petrolcard-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('876','RECORD INVOICE FROM SUPPLIER','purch-recinvcd.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('877','SUPPLIER STATEMENT BY DATE RANGE','supp-stmnt-date.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('878','RECORD SUPPLIER CREDIT NOTE','purch-recnote.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('879','REPRINT CREDIT NOTE','invoice-note-reprint.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('880','STOCK MOVEMENT REPORT','stock-move-rep.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('881','PRODUCE POS CREDIT NOTE','pos-invoice-note.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('882','PRINT POS CREDIT NOTE','pos-note-slip.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('883','RECORD INTERNATIONAL INVOICE FROM SUPPLIER','purch-int-recinvcd.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('884','RETURN INTERNATIONAL PURCHASE','purch-int-return.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('885','NEW INTERNATIONAL INVOICE','intinvoice-new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('886','PRINT INTERNATIONAL INVOICE','intinvoice-print.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('887','PRODUCE INTERNATIONAL INVOICE CREDIT NOTE','intinvoice-note.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('888','REPRINT INTERNATIONAL INVOICE CREDIT NOTE','intinvoice-note-reprint.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('889','PRODUCE INTERNATIONAL SALES ORDER','intsorder-new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('890','ACCEPT INTERNATIONAL SALES ORDER','intsorder-accept.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('891','VIEW PAST EMPLOYEE DETAILS','admin-lemployee-detail.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('892','RETURN PAYMENT','cheq-return.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('893','PURCHASES APPROVE SETTING','set-purch-apprv.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('894','FIND SERIAL NUMBER','stock-serno-find.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('895','INTERNATIONAL INVOICE DETAILS','intinvoice-details.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('896','PRINT INTERNATIONAL INVOICE PDF','intinvoice-pdf-reprint.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('897','REPRINT INTERNATIONAL INVOICE','intinvoice-reprint.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('898','REPRINT INTERNATIONAL NON STOCK INVOICE','nons-intinvoice-reprint.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('899','REPRINT NON STOCK INVOICE','nons-invoice-reprint.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('900','PRINT NON STOCK INTERNATIONAL INVOICE PDF','nons-intinvoice-pdf-reprint.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('901','NON STOCK SALES REPORT','nons-sales-rep.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('902','TOTAL SALES REPORT','sn-sales-rep.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('903','PRINT POS QUOTE','pos-quote-print.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('904','ACCEPT POS QUOTE','pos-quote-accept.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('905','LIST OUTSTANDING PAYMENTS/RECEIPTS - ALL BRANCHES','multi-not-banked.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('906','PRODUCE TRIAL BALANCE - ALL BRANCHES','multi-trial-bal.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('907','VIEW CASH BOOK - ALL BRANCHES','multi-banked.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('908','VAT REPORT - ALL BRANCHES','multi-vat-report.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('909','INCOME STATEMENT - ALL BRANCHES','multi-income-stmnt.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('910','CONFIGURE BALANCE SHEET - ALL BRANCHES','config-bal-sheet.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('911','BALANCE SHEET - ALL BRANCHES','multi-bal-sheet.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('912','DEBTORS AGE ANALYSIS - ALL BRANCHES','multi-debt-age-analysis.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('913','CREDITORS AGE ANALYSIS - ALL BRANCHES','multi-cred-age-analysis.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('914','ALL JOURNAL ENTRIES - ALL BRANCHES','multi-alltrans.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('915','ALL JOURNAL ENTRIES(PERIOD RANGE) - ALL BRANCHES','multi-alltrans-prd.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('916','VIEW PAYSLIP','payslip-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('917','ADD BUDGET','budget-new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('918','VIEW BUDGETS','budget-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('919','BUDGET DETAILS','budget-details.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('920','BUDGET REPORT','budget-report.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('921','REMOVE BUDGET','budget-rem.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('922','EDIT BUDGET','budget-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('923','List Unallocated Queries','tokens-list-unall.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('924','Add Query','tokens-new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('925','Manage Queries','tokens-manage.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('926','SMS Index','index-sms.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('927','Add Team','team-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('928','View Teams','team-list.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('929','Select Team Links','team-links.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('930','Edit Team','team-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('931','Remove Team','team-rem.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('932','Add Query Category','tcat-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('933','View Query Categories','tcat-list.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('934','Edit Query Categories','tcat-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('935','Remove Query Category','tcat-rem.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('936','Set default user teams','crms-allocate.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('937','Allocate Unallocated queries to users','tokens-allocate.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('938','Send CRM Email','email-send.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('939','Send Message to other user','message-send.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('940','Search closed queries','reports-tokens-closed.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('941','List closed queries','reports-tokens-closed2.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('942','Outstanding query Statistics','reports-tokens-stats.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('943','Send CRM SMS','sms-send.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('944','Record other Action taken','tokens-action-other.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('945','View Closed query Details','tokens-closed-details.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('946','Close Query','tokens-close.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('947','Forward Query to future date','tokens-forward.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('948','List All Open Queries','tokens-list-open.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('949','Pass Query to other User','tokens-pass.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('950','Add Action','action-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('951','List Actions','action-list.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('952','Remove Action','action-rem.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('953','List Users to select teams for','crms-list.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('954','Select Multiple teams for a user','crms-teams.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('955','Archive query actions','tokens-action-archive.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('956','View Archived query actions','tokens-action-archive-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('957','BANK TRANSFER','bank-trans.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('958','TRANSFER FUNDS TO/FROM FOREIGN ACCOUNTS','bank-trans-int.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('959','ADD SUPLLIER PRICELIST','sup-pricelist-add','0');
INSERT INTO scripts ("id","script","name","div") VALUES('960','VIEW SUPLLIER PRICELISTS','sup-pricelist-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('961','VIEW DETAILS OF SUPPLIER PRICELIST','','0');
INSERT INTO scripts ("id","script","name","div") VALUES('962','EDIT SUPPLIER PRICELIST','sup-pricelist-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('963','COPY SUPPLIER PRICELIST','sup-pricelist-copy.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('964','REMOVE SUPPLIER PRICELIST','sup-pricelist-rem.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('965','APPROVE PURCHASE','purch-apprv','0');
INSERT INTO scripts ("id","script","name","div") VALUES('966','SET COST VARIENCE ACCOUNT','pchs-link.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('967','PRINT PURCHASE','purch-print.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('968','PROCESS RECURRING INVOICES','rec-invoice-proc.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('969','STOCK SALES REPORT','stock-sales-rep-stk.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('970','VIEW RETURNED PURCHASES','purchase-view-ret.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('971','VIEW RETURNED PURCHASE DETAILS','purch-det-ret.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('972','VIEW SAVED BANK RECONCILIATIONS','multi-bank-recon-saved.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('973','ALL CATEGORIES AND RELATED ACCOUNTS','multi-allcat.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('974','PRINT RECURRING INVOICES','invoice-proc.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('975','EXPORT GENERAL LEDGER TO SPREADSHEET','ledger-xls.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('976','LIST CLOSED QUERIES','reports-tokens-closed2.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('977','RECEIPT FOR INTERNATIONAL CUSTOMER','bank-recpt-inv-int.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('978','PAYMENT TO INTERNATIONAL SUPPLIER','bank-pay-supp-int.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('979','ADD PAYMENT TO CUSTOMER','bank-payment-customer.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('980','PRINT INTERNATIONAL SALES ORDER','intsorder-print.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('981','CANCEL INTERNATIONAL PAYMENT/RECEIPT','cheq-return-int.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('982','CANCEL INTERNATIONAL SALES ORDER','intsorder-cancel.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('983','VIEW INTERNATIONAL SALES ORDER DETAILS','intsorder-details.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('984','NEW INTERNATIONAL NON STOCK INVOICE','nons-intinvoice-new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('985','PRINT INTERNATIONAL NON STOCK INVOICE','nons-intinvoice-print.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('986','VIEW INTERNATIONAL NON STOCK INVOICE DETAILS','nons-intinvoice-print.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('987','NEW INTERNATIONAL NON STOCK INVOICE CREDIT NOTE','nons-intinvoice-note.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('988','CANCEL INCOMPLETE POS QUOTE','pos-quote-unf-cancel.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('989','VIEW INTERNATIONAL NON STOCK INVOICE DETAILS','nons-intinvoice-det.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('990','ADD BANK PAYMENT TO CUSTOMER','bank-pay-cus.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('991','REPRINT INTERNATIONAL INVOICE','intinvoice-reprint-prd.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('992','INTERNATIONAL CUSTOMER TRANSACTION','intcust-trans.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('993','VIEW INTERNATIONAL INVOCIE DETAILS','intinvoice-details-prd.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('994','INTERNATIONAL SUPPLIER TRANSACTION','intsupp-trans.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('995','VIEW RETURNED INTERNATIONAL PURCHASE DETAILS','purch-int-view-ret.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('996','RECORD INTERNATIONAL PURCHASE RECEIVED','purch-int-recmode.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('997','EXPORT YEARLY BUDGET','budget-yr-export.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('998','EXPORT MONTHLY BUDGET','budget-export.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('999','ADD SUPPLIER PRICELIST','sup-pricelist-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1000','VIEW INTEREST SETTINGS','set-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1001','VIEW RECEIVED INTERNATIONAL NON STOCK ORDERS','nons-purch-int-view-prd.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1002','PAY EMPLOYEE','employee-pay.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1003','FIND DEBTOR','customers-find.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1004','FIND CREDITOR','supp-find.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1005','SET DETAILED BALANCE SHEET','set-balance-sheet.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1006','GENERATE DETAILED BALANCE SHEET','gen-balance-sheet.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1007','EXPORT DETAILED BALANCE SHEET TO SPREADSHEET','gen-balance-sheet-xls.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1008','GENERATE DETAILED TRIAL BALANCE','gen-trial-balance.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1009','EXPORT DETAILED TRIAL BALANCE TO SPREADSHEET','gen-trial-balance-xls.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1010','SET DETAILED TRIAL BALANCE','set-trial-balance.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1011','GENERATE DETAILED INCOME STATEMENT','gen-income-stmnt.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1012','EXPORT DETAILED INCOME STATEMENT TO SPREADSHEET','gen-income-stmnt-xls.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1013','SET DETAILED INCOME STATEMENT','set-income-stmnt.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1014','NEW INTERNATIONAL NON STOCK PURCHASE','nons-purch-int-new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1015','VIEW INTERNATIONAL NON STOCK PURCHASES','nons-purch-int-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1016','CANCEL INTERNATIONAL NON STOCK PURCHASE','nons-purch-int-cancel.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1017','VIEW INTERNATIONAL NON STOCK PURCHASE DETAILS','nons-purch-int-det.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1018','RECEIVE INTERNATIONAL NON STOCK PURCHASE','nons-purch-int-recv.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1019','RETURN INTERNATIONAL NON STOCK PURCHASE','nons-purch-return.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1020','VIEW RECEIVED INTERNATIONAL NON STOCK PURCHASE DETAILS','nons-purch-int-det-prd.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1021','CREDIT NOTE FOR PAID INVOICE','invoice-note-prd.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1022','CASHFLOW REPORT','cashflow-report.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1023','EMAIL INVOICES','invoices-email.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1024','EMAIL SETTINGS','email-settings.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1025','POS SALES REPORT','pos-report-sales.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1026','RETURN NON STOCK PURCHASE','nons-purch-return.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1027','VIEW RETURNED NON STOCK PURCHASES','nons-note-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1028','VIEW RETURNED NON STOCK PURCHASES DETAILS','nons-note-det.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1029','ADD CASH FLOW ENTRY','cfe-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1030','EDIT CASH FLOW ENTRY','cfe-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1031','REMOVE CASH FLOW ENTRY','cfe-rem.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1032','VIEW CASH FLOW ENTRIES','cfe-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1033','EMAIL STATEMENTS','statements-email.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1034','GO BACK TO PREVIOUS PERIOD','set-period-use.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1035','Add reimbursement','rbs-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1036','View reimbursements','rbs-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1037','Edit reimbursement','rbs-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1038','Delete reimbursement','rbs-rem.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1039','New Recurring Non-Stock Invoice','rec-nons-invoice-new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1040','View Recurring Non-Stock Invoices','rec-nons-invoice-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1041','View Recurring Non-Stock Invoice Details','rec-nons-invoice-det.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1042','Process Recurring Non-Stock Invoices','nons-rec-invoice-proc.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1043','Print Recurring Non-Stock Invoices','nons-invoices-print.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1044','Import bank statement','import-statement.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1045','Bank statement import settings','import-settings.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1046','Batch salaries','salaries-batch.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1047','Reverse salary','salaries-staffr.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1048','Delete Recurring Non-Stock Invoice','rec-nons-invoice-rem.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1049','Edit asset','asset-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1050','New Non-Stock Sales Order','nons-sorder-new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1051','View Non-Stock Sales Orders','nons-sorder-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1052','View Non-Stock Sales Orders Details','nons-sorder-det.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1053','Print Non-Stock Sales Orders','nons-sorder-print.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1054','Print Non-Stock Sales Orders PDF','nons-sorder-pdf-print.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1055','Accept Non-Stock Sales Orders','nons-sorder-acc.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1056','Export customer ledger to spreadsheet','cust-ledger-xls.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1057','Export stock ledger to spreadsheet','stock-ledger-xls.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1058','Export POS cash report to spreadsheet','pos-report-user-xls.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1059','Export POS sales report to spreadsheet','pos-report-sales-xls.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1060','Export supplier ledger to spreadsheet','supp-ledger-xls.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1061','Export period range ledger to spreadsheet','ledger-prd-xls.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1062','Export YTD ledger to spreadsheet','ledger-ytd-xls.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1063','Export All journal enteries by ref to spreadsheet','trans-amt-xls.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1064','PRINT NON STOCK PURCHASE','nons-purch-print.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1065','PRINT NON STOCK PURCHASE','nons-purch-print.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1066','DELIVERY NOTE','invoice-delnote.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1067','VAT REPORTS','reporting/reports-vat.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1068','VAT REPORTS','reports-vat.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1069','BANKING REPORTS INDEX','index-reports-banking.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1070','STATEMENT REPORTS INDEX','index-reports-stmnt.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1071','DEBTORS/CREDITORS REPORTS INDEX','index-reports-debtcred.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1072','JOURNAL REPORTS INDEX','index-reports-journal.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1073','VIEW BATCH CASHBOOK','batch-cashbook-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1074','CASH BOOK ENTRY','cashbook-entry.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1075','ADD POS USER','pos-user-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1076','OTHER REPORTS INDEX','index-reports-other.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1077','PDF TAX INVOICE','pdf-tax-invoice.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1078','INVOICE DELIVERY NOTE','invoice-delnote-prd.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1079','VIEW PROCESSED NON STOCK INVOICES','nons-processed-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1080','VIEW INCOMPLETE NON STOCK INVOICES','nons-unf-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1081','NEW CALL OUT DOCUMENT','callout-new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1082','VIEW CALL OUT DOCUMENTS','callout-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1083','VIEW INCOMPLETE CALL OUT DOCUMENTS','callout-unf-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1084','VIEW CANCELLED CALL OUT DOCUMENTS','callout-canc-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1085','ASA 401 ASSISTANCE REPORT','report_asa401.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1086','ADD POS USER','pos-user-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1087','AUDIT REPORT','audit_record.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1088','PRINT RECEIVED ORDERS','purch-recv-print.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1089','VIEW RETURNED INTERNATIONAL ORDERS','purch-int-det-ret.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1090','PRINT RECEIVED NON STOCK PURCHASES','nons-purch-recv-print.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1091','PRINT INTERNATIONAL NON STOCK PURCHASES','nons-purch-int-print.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1092','VIEW RECEIVED INTERNATIONAL PURCHASES - RETURNED','nons-purch-int-return.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1093','VIEW REVERSED SALARIES','playslipsr.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1094','ADD FRINGE BENEFIT','fringebenefit-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1095','VIEW FRINGE BENEFIT','fringebens-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1096','VIEW REIMBURSEMENT','rbs-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1097','VIEW ARCHIVED LOANS','loans-archive.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1098','VIEW SALARIES - YEAR TO DATE','irp5-data.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1099','VIEW SALARIES - EMPLOYEE TRANSACTION','employee-tran.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1100','GENERATE IRP 5','irp5-pdf.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1101','EXPORT IRP 5','irp5-export.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1102','GENERATE IT 3(A)','it3-pdf.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1104','VIEW EMPLOYEE EXPENSE ACCOUNTS','empacc-link.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1105','VIEW DOCUMENTS','doc-view-type.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1106','ADD SUPPLIER GROUPS','supp-group-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1107','VIEW SUPPLIER GROUPS','supp-group-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1108','GENERATE PREVIOUS YEAR GENERAL LEDGER','ledger-audit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1109','GENERATE PREVIOUS YEAR GENERAL LEDGER - PERIOD','ledger-audit-prd.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1111','GENERATE PREVIOUS YEAR CREDITORS LEDGER','supp-ledger-audit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1112','GENERATE PREVIOUS YEAR STOCK LEDGER','stock-ledger-audit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1110','GENERATE PREVIOUS YEAR DEBTORS LEDGER','cust-ledger-audit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1113','REGISTER CUBIT','register.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1114','IMPORT STOCK','import-stock.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1115','IMPORT CUSTOMERS','import-customers.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1116','IMPORT SUPPLIERS','import-suppliers.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1117','IMPORT TRIAL BALANCE','import-tb.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1118','EMPLOYEE LEDGER','employee-ledger.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1119','VIEW VAT REPORT','reports-vat.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1120','GENERATE STATEMENT OF CASH FLOW','cash-flow.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1121','BANKING REPORTS','index-reports-banking.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1122','STATEMENT REPORTS','index-reports-stmnt.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1123','JOURNAL REPORTS','index-reports-journal.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1124','OTHER REPORTS','index-reports-other.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1125','VIEW ASSETS APPRECIATION','asset-app.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1126','ASSETS APPRECIATION - EXPORT TO SPREADSHEET','asset-export.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1127','CASH BOOK ENTRY','cashbook-entry.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1128','VIEW BATCH CASH BOOK ENTRIES','batch-cashbook-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1129','PRINT BUDGET REPORT','budget-report-print.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1130','MANAGE QUERIES - SEND SMS','https_face.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1131','NEW LEAD','leads_new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1132','VIEW LEADS','leads_list.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1133','ADD TO WORKSHOP','workshop-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1134','VIEW WORKSHOP','workshop-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1135','STOCK MOVEMENT REPORT - EXPORT TO SPREADSHEET','stock-move-report-xls.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1136','DELIVERY NOTE REPORT','delnote-report.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1137','EMAIL CUSTOMERS','customers-email.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1138','SAVE AGE ANALYSIS','save-age.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1139','NEW YEARLY BUDGET','budget-yr-new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1140','CHANGE SPLASH SCREEN','splash.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1141','SET ACCOUNT CREATION','set.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1142','DEBTORS AGE ANALYSIS PERIOD TYPE','set-debt-age.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1143','SET INTEREST CALCULATION METHOD','set-int-type.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1144','SET DEFAULT ACCOUNTS','defdep-slct.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1145','SET/SHOW FINANCIAL YEAR NAMES','finyearnames-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1146','SET PERIOD RANGE','finyear-range.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1147','CLOSE YEAR','yr-close.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1148','VIEW SETTINGS','set-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1149','SET COST CENTER USAGE','set-costcenter-use.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1150','ADD NEW ACCOUNT','acc-new-dec.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1151','VIEW ACCOUNTS','acc-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1152','ADD ACCOUNT CATEGORY','acccat-new.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1153','ADD DEPARTMENT','debt-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1154','VIEW DEPARTMENT','debt-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1155','ADD SALES PERSON','salesp-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1156','VIEW SALES PERSON','salesp-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1157','ADD CATEGORY','cat-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1158','VIEW CATEGORY','cat-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1159','ADD CLASSIFICATION','class-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1160','VIEW CLASSIFICATION','class-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1161','SALES SETTINGS','sales-link.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1162','SALES SETTINGS - MORE','sal-link.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1163','SET INVOICE NUMBER','invid-set.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1164','SET SALES REP COMMISSION','coms-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1165','ADD INTEREST BRACKET','intbrac-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1166','VIEW INTEREST BRACKET','intbrac-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1167','VIEW STOCK CATEGORY','stockcat-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1168','ADD STORE','whouse-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1169','VIEW STORE','whouse-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1170','ADD PRICELIST','pricelist-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1171','VIEW PRICELISTS','pricelist-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1172','ADD SUPPLIER PRICELIST','sup-pricelist-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1173','VIEW SUPPLIER PRICELISTS','sup-pricelist-view.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1174','INCREASE SELLING PRICE','stock-price-inc.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1175','PURCHASES SETTINGS','pchs-link.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1176','SET DEFAULT STORE','defwh-set.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1177','SET SELLING PRICE VAT TYPE','set-vat-type.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1178','SET STOCK PURCHASES APPROVAL','set-purch-apprv.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1179','POINT OF SALE SETTING','pos-set.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1180','BANK ACCOUNT TYPES','accounttype-add.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1181','SALARIES GENERAL SETTINGS','settings-acc-edit.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1182','STATEMENT IMPORT SETTINGS','import-settings.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1183','EMAIL SETTINGS','email-settings.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1184','SET COMPANY BANKING DETAILS','set-inv-bankdetails.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1185','SELECT CURRENT / PREVIOUS YEAR TRANSACTIONS','set-period-use.php','0');
INSERT INTO scripts ("id","script","name","div") VALUES('1186','CASHBOOK ONE RECEIPT FOR MULTIPLE CUSTOMERS','bank-recpt-multi-debtor.php','0');
CREATE TABLE posround ("id" serial NOT NULL PRIMARY KEY ,"setting" varchar ) WITH OIDS;
SELECT setval('posround_id_seq',1);
CREATE TABLE hire_invoice_trans ("id" serial NOT NULL PRIMARY KEY ,"hire_id" numeric DEFAULT 0,"cusnum" numeric DEFAULT 0,"order_num" varchar ,"discount" numeric(16, 2) DEFAULT 0,"delivery" numeric(16, 2) DEFAULT 0,"subtotal" numeric(16, 2) DEFAULT 0,"vat" numeric(16, 2) DEFAULT 0,"user_id" numeric DEFAULT 0,"discount_perc" numeric DEFAULT 0,"total" numeric(16, 2) DEFAULT 0,"timestamp" timestamp DEFAULT now(),"done" bool DEFAULT false,"paid" numeric(16, 2) DEFAULT 0,"type" numeric DEFAULT 1,"pay_type" varchar ,"invnum" numeric DEFAULT 0) WITH OIDS;
SELECT setval('hire_invoice_trans_id_seq',1);
CREATE TABLE recon_reasons ("id" serial NOT NULL PRIMARY KEY ,"reason" varchar ) WITH OIDS;
SELECT setval('recon_reasons_id_seq',1);
CREATE TABLE bankacct ("bankid" serial NOT NULL PRIMARY KEY ,"acctype" varchar ,"bankname" varchar ,"branchname" varchar ,"branchcode" varchar ,"accname" varchar ,"details" varchar ,"div" numeric DEFAULT 0,"cardnum" varchar ,"mon" varchar ,"year" varchar ,"digits" varchar ,"cardtype" varchar ,"type" varchar ,"accnum" varchar ,"balance" numeric(16, 2) DEFAULT 0,"fbalance" numeric(16, 2) DEFAULT 0,"fcid" numeric DEFAULT 0,"btype" varchar ,"currency" varchar ) WITH OIDS;
SELECT setval('bankacct_bankid_seq',1);
CREATE TABLE inv_data ("invid" numeric DEFAULT 0,"dept" varchar ,"customer" varchar ,"addr1" varchar ,"addr2" varchar ,"addr3" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
CREATE TABLE payrec ("id" serial NOT NULL PRIMARY KEY ,"date" date ,"by" varchar ,"inv" int4 DEFAULT 0,"amount" numeric(16, 2) DEFAULT 0,"method" varchar ,"prd" int4 DEFAULT 0,"note" int4 DEFAULT 0,"multiinv" varchar ) WITH OIDS;
SELECT setval('payrec_id_seq',1);
CREATE TABLE video_stock_items ("id" serial NOT NULL PRIMARY KEY ,"stock_id" numeric DEFAULT 0,"hire_id" numeric DEFAULT 0,"qty" numeric DEFAULT 0,"unitprice" numeric(16, 2) DEFAULT 0,"total" numeric(16, 2) DEFAULT 0,"type" numeric DEFAULT 1,"cost_price" numeric(16, 2) DEFAULT 0) WITH OIDS;
SELECT setval('video_stock_items_id_seq',1);
CREATE TABLE emp_income_sources ("id" serial NOT NULL PRIMARY KEY ,"empnum" numeric DEFAULT 0,"code" varchar ,"description" varchar ,"rf_ind" varchar ,"amount" varchar ) WITH OIDS;
SELECT setval('emp_income_sources_id_seq',1);
CREATE TABLE intbracs ("id" serial NOT NULL PRIMARY KEY ,"min" numeric DEFAULT 0,"max" numeric DEFAULT 0,"percentage" numeric DEFAULT 0) WITH OIDS;
SELECT setval('intbracs_id_seq',1);
CREATE TABLE allowances ("id" serial NOT NULL PRIMARY KEY ,"allowance" varchar ,"taxable" varchar ,"accid" numeric DEFAULT 0,"add" varchar ,"div" numeric DEFAULT 0,"type" varchar ) WITH OIDS;
SELECT setval('allowances_id_seq',1);
CREATE TABLE cashbook ("cashid" serial NOT NULL PRIMARY KEY ,"trantype" varchar ,"bankid" numeric DEFAULT 0,"date" date ,"name" varchar ,"descript" varchar ,"cheqnum" numeric DEFAULT 0,"amount" numeric DEFAULT 0,"banked" varchar ,"accinv" varchar ,"lnk" numeric DEFAULT 0,"supid" numeric DEFAULT 0,"cusnum" numeric DEFAULT 0,"rinvids" text ,"amounts" text ,"invprds" text ,"ids" text ,"purids" text ,"pamounts" text ,"pdates" text ,"div" numeric DEFAULT 0,"accids" text ,"suprec" numeric DEFAULT 0,"vat" numeric DEFAULT 0,"chrgvat" varchar ,"vats" varchar ,"chrgvats" varchar ,"rages" text ,"famount" numeric(16, 2) DEFAULT 0,"fpamounts" varchar ,"famounts" varchar ,"lcashid" varchar ,"fcid" numeric DEFAULT 0,"currency" varchar ,"location" varchar ,"opt" varchar ,"rid" int4 DEFAULT 0,"vatcode" int4 DEFAULT 0,"reference" varchar ,"vatcodes" text ,"multicusnum" varchar ,"multicusamt" varchar ,"stkinfo" varchar ,"empnum" int4 DEFAULT 0,"setamts" varchar DEFAULT ''::character varying,"bankrecon_ticked" varchar DEFAULT 'no'::character varying) WITH OIDS;
SELECT setval('cashbook_cashid_seq',1);
CREATE TABLE loan_types ("id" serial NOT NULL PRIMARY KEY ,"loan_type" varchar ) WITH OIDS;
SELECT setval('loan_types_id_seq',1);
CREATE TABLE stock_sold ("id" serial NOT NULL PRIMARY KEY ,"trans_id" numeric DEFAULT 0,"hire_trans_id" numeric DEFAULT 0,"user_id" numeric DEFAULT 0,"till_id" numeric DEFAULT 0,"stkid" numeric DEFAULT 0,"timestamp" timestamp DEFAULT now(),"qty" numeric DEFAULT 0,"cost_price" numeric(16, 2) DEFAULT 0,"selling_price" numeric(16, 2) DEFAULT 0,"description" varchar ) WITH OIDS;
SELECT setval('stock_sold_id_seq',1);
CREATE TABLE inv_discs ("invid" numeric DEFAULT 0,"cusnum" numeric DEFAULT 0,"inv_date" date ,"div" numeric DEFAULT 0,"traddisc" numeric(16, 2) DEFAULT 0,"itemdisc" numeric(16, 2) DEFAULT 0,"delchrg" numeric(16, 2) DEFAULT 0,"total" numeric(16, 2) DEFAULT 0) WITH OIDS;
CREATE TABLE ss9 ("stock" int4 DEFAULT 0,"code" varchar ,"div" numeric DEFAULT 0,"active" varchar DEFAULT 'yes'::character varying) WITH OIDS;
CREATE TABLE salded ("id" serial NOT NULL PRIMARY KEY ,"refno" varchar ,"deduction" varchar ,"creditor" varchar ,"details" varchar ,"accid" numeric DEFAULT 0,"add" varchar ,"div" numeric DEFAULT 0,"expaccid" numeric DEFAULT 0,"type" varchar ,"code" numeric DEFAULT 0) WITH OIDS;
SELECT setval('salded_id_seq',1);
CREATE TABLE saved_cf_accounts ("id" serial NOT NULL PRIMARY KEY ,"accid" numeric DEFAULT 0,"category" varchar ) WITH OIDS;
SELECT setval('saved_cf_accounts_id_seq',1);
CREATE TABLE saved_is_accounts ("id" serial NOT NULL PRIMARY KEY ,"accid" numeric DEFAULT 0,"topacc" numeric DEFAULT 0,"accnum" numeric DEFAULT 0,"accname" varchar ,"note" varchar ) WITH OIDS;
SELECT setval('saved_is_accounts_id_seq',1);
CREATE TABLE recon_comments_ct ("id" serial NOT NULL PRIMARY KEY ,"supid" numeric DEFAULT 0,"comment" varchar ,"date" date DEFAULT ('now'::text)::date) WITH OIDS;
SELECT setval('recon_comments_ct_id_seq',1);
CREATE TABLE document_movement ("id" serial NOT NULL PRIMARY KEY ,"timestamp" timestamp DEFAULT now(),"doc_id" numeric DEFAULT 0,"movement_description" varchar ,"project" varchar ,"area" varchar ,"discipline" varchar ,"doc_type" varchar ,"revision" varchar ,"drawing_num" varchar ,"title" varchar ,"contract" varchar ,"code" varchar ,"issue_for" varchar ,"comments" varchar ,"qs" varchar ,"status" varchar ,"team_id" numeric DEFAULT 0,"location" varchar ) WITH OIDS;
SELECT setval('document_movement_id_seq',1);
CREATE TABLE mail_messages ("message_id" serial NOT NULL PRIMARY KEY ,"account_id" int4 DEFAULT 0,"username" varchar ,"folder_id" int4 DEFAULT 0,"subject" varchar ,"add_from" varchar ,"add_to" varchar ,"add_cc" varchar ,"add_bcc" varchar ,"priority" varchar ,"attachments" varchar ,"msgbody_id" int4 DEFAULT 0,"flag" varchar ,"date" timestamp ) WITH OIDS;
SELECT setval('mail_messages_message_id_seq',1);
CREATE TABLE pcost ("id" serial NOT NULL PRIMARY KEY ,"purnum" int4 DEFAULT 0,"cost" numeric(16, 2) DEFAULT 0,"qty" numeric(16, 2) DEFAULT 0,"rqty" int4 DEFAULT 0,"stkid" int4 DEFAULT 0) WITH OIDS;
SELECT setval('pcost_id_seq',1);
CREATE TABLE pos_trans ("id" serial NOT NULL PRIMARY KEY ,"cusnum" numeric DEFAULT 0,"timestamp" timestamp DEFAULT now(),"user_id" numeric DEFAULT 0,"type" varchar ,"subtotal" numeric(16, 2) DEFAULT 0,"total" numeric(16, 2) DEFAULT 0,"vat" numeric(16, 2) DEFAULT 0,"discount_perc" numeric DEFAULT 0,"discount" numeric(16, 2) DEFAULT 0,"paid" numeric(16, 2) DEFAULT 0,"pay_type" varchar ,"sale_no" numeric DEFAULT 0) WITH OIDS;
SELECT setval('pos_trans_id_seq',1);
CREATE TABLE today_sections ("id" serial NOT NULL PRIMARY KEY ,"name" varchar ,"link" varchar ,"table_name" varchar ,"id_column" varchar ,"title_column" varchar ,"title_link" varchar ) WITH OIDS;
SELECT setval('today_sections_id_seq',7);
INSERT INTO today_sections ("id","name","link","table_name","id_column","title_column","title_link") VALUES('1','Documents','document_view.php','cubit.documents','docid','title','document_det.php?id=');
INSERT INTO today_sections ("id","name","link","table_name","id_column","title_column","title_link") VALUES('2','Customers','../customers-view.php','cubit.customers','cusnum','surname','../cust-det.php?cusnum=');
INSERT INTO today_sections ("id","name","link","table_name","id_column","title_column","title_link") VALUES('3','Diary','diary-index.php','cubit.diary_entries','entry_id','title','diary-appointment.php?key=view&entry_id=');
INSERT INTO today_sections ("id","name","link","table_name","id_column","title_column","title_link") VALUES('4','Contacts','list_cons.php','cubit.cons','id','surname','view_con.php?id=');
INSERT INTO today_sections ("id","name","link","table_name","id_column","title_column","title_link") VALUES('7','Assets','../asset-view.php','cubit.assets','id','des','../asset-edit.php?id=');
INSERT INTO today_sections ("id","name","link","table_name","id_column","title_column","title_link") VALUES('5','Leads','../crmsystem/leads_list.php','crm.leads','id','surname','../crmsystem/leads_view.php?id=');
CREATE TABLE branches_data ("id" serial NOT NULL PRIMARY KEY ,"branch_name" varchar ,"branch_desc" varchar ,"branch_contact" varchar ,"branch_ip" varchar ,"date_added" date ,"last_online" date ,"branch_username" varchar ,"branch_password" varchar ,"last_login_from" date ,"branch_localuser" numeric DEFAULT 0,"branch_company" varchar ) WITH OIDS;
SELECT setval('branches_data_id_seq',1);
CREATE TABLE pettyrec ("cashid" numeric DEFAULT 0,"date" date ,"name" varchar ,"type" varchar ,"det" varchar ,"amount" numeric DEFAULT 0,"div" numeric DEFAULT 0) WITH OIDS;
CREATE TABLE lead_dates ("id" serial NOT NULL PRIMARY KEY ,"user_id" numeric DEFAULT 0,"lead_id" numeric DEFAULT 0,"date" date ,"notes" varchar ) WITH OIDS;
SELECT setval('lead_dates_id_seq',1);
CREATE TABLE credit_notes ("id" serial NOT NULL PRIMARY KEY ,"cusnum" numeric DEFAULT 0,"tdate" date ,"sdate" date ,"refnum" numeric DEFAULT 0,"contra" numeric DEFAULT 0,"charge_vat" varchar ,"vatinc" varchar ,"vatacc" numeric DEFAULT 0,"vatamt" numeric(16, 2) DEFAULT 0,"vatacc_type" varchar ,"used_stock" varchar DEFAULT 'no'::character varying,"creditnote_num" numeric DEFAULT 0,"vatcode" numeric DEFAULT 0,"amount" numeric(16, 2) DEFAULT 0,"totamt" numeric(16, 2) DEFAULT 0) WITH OIDS;
SELECT setval('credit_notes_id_seq',1);
CREATE TABLE unions ("id" serial NOT NULL PRIMARY KEY ,"union_name" varchar ,"date_added" date ,"req_perc" numeric(16, 2) DEFAULT 0) WITH OIDS;
SELECT setval('unions_id_seq',7);
INSERT INTO unions ("id","union_name","date_added","req_perc") VALUES('1','SAUJ','2006-08-03','14.29');
INSERT INTO unions ("id","union_name","date_added","req_perc") VALUES('2','SATU','2006-08-03','14.29');
INSERT INTO unions ("id","union_name","date_added","req_perc") VALUES('3','CEPPWAWU','2006-08-03','14.29');
INSERT INTO unions ("id","union_name","date_added","req_perc") VALUES('4','MWASA','2006-08-03','14.29');
INSERT INTO unions ("id","union_name","date_added","req_perc") VALUES('5','ECCAWUSA','2006-08-03','14.29');
INSERT INTO unions ("id","union_name","date_added","req_perc") VALUES('6','S.A.C.C.A.W.A','2006-08-03','14.29');
INSERT INTO unions ("id","union_name","date_added","req_perc") VALUES('7','NON-UNION','2006-08-03','14.29');
CREATE TABLE debtors ("ordnum" numeric DEFAULT 0,"cusname" varchar ,"addr1" varchar ,"addr2" varchar ,"addr3" varchar ,"tel" varchar ,"fax" varchar ,"email" varchar ,"amount" numeric DEFAULT 0,"terms" int4 DEFAULT 0,"cusnum" numeric DEFAULT 0,"grdtot" numeric DEFAULT 0,"div" numeric DEFAULT 0) WITH OIDS;
CREATE TABLE empallow ("id" serial NOT NULL PRIMARY KEY ,"allowid" varchar ,"empnum" int4 DEFAULT 0,"amount" numeric DEFAULT 0,"div" numeric DEFAULT 0,"type" varchar ,"accid" numeric DEFAULT 0) WITH OIDS;
SELECT setval('empallow_id_seq',1);
CREATE TABLE empdeduct ("id" serial NOT NULL PRIMARY KEY ,"dedid" varchar ,"empnum" int4 DEFAULT 0,"amount" numeric DEFAULT 0,"div" numeric DEFAULT 0,"type" varchar ,"employer_amount" numeric DEFAULT 0,"employer_type" varchar ,"grosdeduct" varchar ,"accid" numeric DEFAULT 0,"clearance_no" varchar ) WITH OIDS;
SELECT setval('empdeduct_id_seq',1);
CREATE TABLE costcenters_links ("id" serial NOT NULL PRIMARY KEY ,"ccid" numeric DEFAULT 0,"project1" numeric DEFAULT 0,"project2" numeric DEFAULT 0,"project3" numeric DEFAULT 0) WITH OIDS;
SELECT setval('costcenters_links_id_seq',2);
INSERT INTO costcenters_links ("id","ccid","project1","project2","project3") VALUES('2','2','3','3','3');
CREATE TABLE hire_invoice_items_trans ("id" serial NOT NULL PRIMARY KEY ,"trans_id" numeric DEFAULT 0,"asset_id" numeric DEFAULT 0,"basis" varchar DEFAULT 'per_day'::character varying,"from_date" date ,"to_date" date ,"half_day" bool DEFAULT false,"qty" numeric DEFAULT 0,"weekends" bool DEFAULT false,"total_days" numeric DEFAULT 0,"total" numeric(16, 2) DEFAULT 0,"returned" bool DEFAULT false) WITH OIDS;
SELECT setval('hire_invoice_items_trans_id_seq',1);
CREATE TABLE vatrec ("edate" date ,"ref" varchar ,"amount" numeric DEFAULT 0,"descript" varchar ,"chrgvat" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
CREATE TABLE nons_purchasesn ("id" serial NOT NULL PRIMARY KEY ,"purid" int4 DEFAULT 0,"notenum" int4 DEFAULT 0,"deptid" numeric DEFAULT 0,"supid" numeric DEFAULT 0,"supplier" varchar ,"supaddr" varchar ,"terms" numeric DEFAULT 0,"pdate" date ,"ddate" date ,"remarks" varchar ,"received" varchar ,"done" varchar ,"refno" varchar ,"vatinc" varchar ,"prd" numeric DEFAULT 0,"order" varchar ,"ordernum" varchar ,"part" varchar ,"div" numeric DEFAULT 0,"purnum" varchar ,"cusid" int4 DEFAULT 0,"shipchrg" numeric(16, 2) DEFAULT 0,"subtot" numeric(16, 2) DEFAULT 0,"total" numeric(16, 2) DEFAULT 0,"balance" numeric(16, 2) DEFAULT 0,"vat" numeric(16, 2) DEFAULT 0,"spurnum" numeric DEFAULT 0,"spurtype" varchar ,"spurprd" varchar ,"assid" numeric DEFAULT 0,"grpid" numeric DEFAULT 0,"mpurid" numeric DEFAULT 0,"mpurnum" numeric DEFAULT 0,"shipping" numeric(16, 2) DEFAULT 0,"ctyp" varchar ,"typeid" numeric DEFAULT 0,"supinv" varchar ) WITH OIDS;
SELECT setval('nons_purchasesn_id_seq',1);
CREATE TABLE assetledger ("id" serial NOT NULL PRIMARY KEY ,"assetid" numeric DEFAULT 0,"asset" varchar ,"date" date ,"depamt" numeric DEFAULT 0,"netval" numeric DEFAULT 0,"div" numeric DEFAULT 0) WITH OIDS;
SELECT setval('assetledger_id_seq',1);
CREATE TABLE die ("id" serial NOT NULL PRIMARY KEY ,"datemade" date ,"datefor" date ,"userfor" varchar ,"time" varchar ,"des" varchar ,"die" varchar ,"rem" int4 DEFAULT 0,"remop" int4 DEFAULT 0,"remdate" date ,"remtime" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
SELECT setval('die_id_seq',1);
CREATE TABLE corders ("sordid" serial NOT NULL PRIMARY KEY ,"deptid" numeric DEFAULT 0,"cusnum" numeric DEFAULT 0,"cordno" varchar ,"ordno" varchar ,"chrgvat" varchar ,"terms" numeric DEFAULT 0,"salespn" varchar ,"odate" date ,"accepted" varchar ,"comm" varchar ,"done" varchar ,"username" varchar ,"deptname" varchar ,"cusacc" varchar ,"cusname" varchar ,"surname" varchar ,"cusaddr" varchar ,"cusordno" varchar ,"cusvatno" varchar ,"prd" numeric DEFAULT 0,"div" numeric DEFAULT 0,"disc" numeric(16, 2) DEFAULT 0,"discp" numeric(16, 2) DEFAULT 0,"delchrg" numeric(16, 2) DEFAULT 0,"subtot" numeric(16, 2) DEFAULT 0,"traddisc" numeric(16, 2) DEFAULT 0,"balance" numeric(16, 2) DEFAULT 0,"vat" numeric(16, 2) DEFAULT 0,"total" numeric(16, 2) DEFAULT 0,"discount" numeric(16, 2) DEFAULT 0,"delivery" numeric(16, 2) DEFAULT 0,"delvat" int4 DEFAULT 0) WITH OIDS;
SELECT setval('corders_sordid_seq',1);
CREATE TABLE rnons_inv_items ("id" serial NOT NULL PRIMARY KEY ,"invid" numeric DEFAULT 0,"qty" numeric DEFAULT 0,"description" varchar ,"div" numeric DEFAULT 0,"amt" numeric(16, 2) DEFAULT 0,"unitcost" numeric(16, 2) DEFAULT 0,"accid" numeric DEFAULT 0,"rqty" numeric DEFAULT 0,"vatex" varchar ,"cunitcost" numeric(16, 2) DEFAULT 0,"account" int4 DEFAULT 0) WITH OIDS;
SELECT setval('rnons_inv_items_id_seq',1);
CREATE TABLE stockclass ("clasid" serial NOT NULL PRIMARY KEY ,"classname" varchar ,"classcode" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
SELECT setval('stockclass_clasid_seq',1);
CREATE TABLE assets ("id" serial NOT NULL PRIMARY KEY ,"serial" varchar ,"locat" varchar ,"des" varchar ,"date" date ,"bdate" date ,"amount" numeric DEFAULT 0,"div" numeric DEFAULT 0,"grpid" numeric DEFAULT 0,"accdep" numeric DEFAULT 0,"dep_perc" numeric DEFAULT 0,"dep_month" varchar ,"team_id" numeric DEFAULT 0,"puramt" numeric(16, 2) DEFAULT 0,"conacc" numeric DEFAULT 0,"remaction" varchar ,"saledate" date ,"saleamt" numeric(16, 2) DEFAULT 0,"invid" int4 DEFAULT 0,"autodepr_date" date ,"sdate" date ,"temp_asset" varchar DEFAULT 'n'::character varying,"nonserial" varchar ,"type_id" numeric DEFAULT 0,"split_from" numeric DEFAULT 1,"serial2" varchar ,"days" numeric DEFAULT 0,"on_hand" numeric DEFAULT 0,"svdate" date ,"cost_acc" numeric DEFAULT 0,"accdep_acc" numeric DEFAULT 0,"dep_acc" numeric DEFAULT 0,"details" varchar ,"units" numeric DEFAULT 0,"damaged" bool DEFAULT false) WITH OIDS;
SELECT setval('assets_id_seq',1);
CREATE TABLE foladd ("id" serial NOT NULL PRIMARY KEY ,"folname" varchar ,"doctype" varchar ,"folder_parent" numeric DEFAULT 0) WITH OIDS;
SELECT setval('foladd_id_seq',1);
CREATE TABLE todo_sub ("id" serial NOT NULL PRIMARY KEY ,"datetime" timestamp ,"description" varchar ,"done" numeric DEFAULT 0,"main_id" numeric DEFAULT 0,"team_id" numeric DEFAULT 0) WITH OIDS;
SELECT setval('todo_sub_id_seq',1);
CREATE TABLE ninvc ("id" serial NOT NULL PRIMARY KEY ,"cid" int4 DEFAULT 0,"inv" int4 DEFAULT 0,"amount" numeric(16, 2) DEFAULT 0) WITH OIDS;
SELECT setval('ninvc_id_seq',1);
CREATE TABLE serial0 ("stkid" numeric DEFAULT 0,"serno" varchar ,"rsvd" varchar ,"warranty" date ) WITH OIDS;
CREATE TABLE stockrec ("edate" date ,"stkid" numeric DEFAULT 0,"stkcod" varchar ,"stkdes" varchar ,"trantype" varchar ,"qty" numeric DEFAULT 0,"details" varchar ,"div" numeric DEFAULT 0,"csamt" numeric(16, 2) DEFAULT 0,"csprice" numeric(16, 2) DEFAULT 0,"id" serial NOT NULL PRIMARY KEY ,"sdate" date DEFAULT now()) WITH OIDS;
SELECT setval('stockrec_id_seq',1);
CREATE TABLE hire_trans ("id" serial NOT NULL PRIMARY KEY ,"cusnum" numeric DEFAULT 0,"order_num" varchar ,"user_id" numeric DEFAULT 0,"trans_type" varchar ,"total" numeric(16, 2) DEFAULT 0,"done" bool DEFAULT false,"invoiced" bool DEFAULT false,"timestamp" timestamp DEFAULT now(),"discount_perc" numeric DEFAULT 0,"discount" numeric(16, 2) DEFAULT 0,"delivery" numeric(16, 2) DEFAULT 0,"subtotal" numeric(16, 2) DEFAULT 0,"deposit" numeric(16, 2) DEFAULT 0,"vat" numeric(16, 2) DEFAULT 0,"type" numeric DEFAULT 1,"hire_num" numeric DEFAULT 0,"pay_type" varchar ,"processed" numeric DEFAULT 0) WITH OIDS;
SELECT setval('hire_trans_id_seq',1);
CREATE TABLE employees ("empnum" serial NOT NULL PRIMARY KEY ,"sname" varchar ,"fnames" varchar ,"sex" bpchar ,"marital" varchar ,"resident" bool ,"hiredate" date ,"telno" varchar ,"email" varchar ,"basic_sal" numeric DEFAULT 0,"commission" numeric DEFAULT 0,"paytype" varchar ,"bankname" varchar ,"bankcode" varchar ,"bankacctype" varchar ,"bankaccno" varchar ,"leave_vac" numeric DEFAULT 0,"leave_sick" numeric DEFAULT 0,"leave_study" numeric DEFAULT 0,"res1" varchar ,"res2" varchar ,"res3" varchar ,"res4" varchar ,"pos1" varchar ,"pos2" varchar ,"pcode" varchar ,"contsname" varchar ,"contfnames" varchar ,"contres1" varchar ,"contres2" varchar ,"contres3" varchar ,"conttelno" varchar ,"loanamt" numeric DEFAULT 0,"loaninstall" numeric DEFAULT 0,"loanint" numeric DEFAULT 0,"loanperiod" numeric DEFAULT 0,"gotloan" bool ,"lastpay" varchar ,"div" numeric DEFAULT 0,"idnum" varchar ,"saltyp" varchar ,"payprd" varchar ,"novert" numeric DEFAULT 0,"hovert" numeric DEFAULT 0,"hpweek" numeric DEFAULT 0,"vaclea" numeric DEFAULT 0,"siclea" numeric DEFAULT 0,"stdlea" numeric DEFAULT 0,"taxref" varchar ,"enum" varchar ,"balance" numeric(16, 2) DEFAULT 0,"designation" varchar ,"bonus" numeric DEFAULT 0,"comp_pension" numeric DEFAULT 0,"emp_pension" numeric DEFAULT 0,"comp_medical" numeric DEFAULT 0,"emp_medical" numeric DEFAULT 0,"diplomatic_indemnity" varchar ,"nature" varchar ,"initials" varchar ,"passport_number" varchar ,"cc_number" varchar ,"firedate" date ,"pay_periods" int4 DEFAULT 0,"pay_periods_worked" int4 DEFAULT 0,"dependants" int4 DEFAULT 0,"tall" numeric(16, 2) DEFAULT 0,"emp_ret" numeric(16, 2) DEFAULT 0,"comp_ret" numeric(16, 2) DEFAULT 0,"basic_sal_annum" numeric(16, 2) DEFAULT 0,"sal_bonus" numeric(16, 2) DEFAULT 0,"all_travel" numeric(16, 2) DEFAULT 0,"comp_uif" numeric(16, 2) DEFAULT 0,"comp_other" numeric(16, 2) DEFAULT 0,"comp_providence" numeric(16, 2) DEFAULT 0,"emp_uif" numeric(16, 2) DEFAULT 0,"emp_other" numeric(16, 2) DEFAULT 0,"emp_providence" numeric(16, 2) DEFAULT 0,"expacc_pension" numeric DEFAULT 0,"expacc_providence" numeric DEFAULT 0,"expacc_medical" numeric DEFAULT 0,"expacc_ret" numeric DEFAULT 0,"expacc_uif" numeric DEFAULT 0,"expacc_other" numeric DEFAULT 0,"sal_bonus_month" varchar ,"comp_sdl" numeric(16, 2) DEFAULT 0,"comp_provident" numeric(16, 2) DEFAULT 0,"emp_provident" numeric(16, 2) DEFAULT 0,"expacc_provident" numeric DEFAULT 0,"expacc_salwages" numeric DEFAULT 0,"expacc_sdl" numeric DEFAULT 0,"fringe_car1" numeric DEFAULT 0,"fringe_car2" numeric DEFAULT 0,"loanfringe" numeric DEFAULT 0,"loandate" date ,"fringe_car1_contrib" numeric DEFAULT 0,"fringe_car1_fuel" numeric DEFAULT 0,"fringe_car1_service" numeric DEFAULT 0,"fringe_car2_contrib" numeric DEFAULT 0,"fringe_car2_fuel" numeric DEFAULT 0,"fringe_car2_service" numeric DEFAULT 0,"payprd_day" varchar ,"loanpayslip" numeric DEFAULT 0,"expacc_loan" numeric DEFAULT 0,"passportnum" varchar ,"loanid" numeric DEFAULT 0,"loanint_amt" numeric DEFAULT 0,"loanint_unpaid" numeric DEFAULT 0,"loanamt_tot" numeric DEFAULT 0,"tax_number" varchar ,"fixed_rate" varchar ,"flag" varchar ,"emp_meddeps" int4 DEFAULT 0,"department" varchar ,"occ_cat" varchar ,"occ_level" varchar ,"pos_filled" varchar ,"temporary" varchar ,"termination_date" date ,"recruitment_from" varchar ,"employment_reason" varchar ,"union_name" varchar ,"union_mem_num" varchar ,"union_pos" varchar ,"race" varchar ,"disabled_stat" varchar ,"all_reimburs" numeric(16, 2) DEFAULT 0,"expacc_reimburs" numeric DEFAULT 0,"prevemp_remun" numeric DEFAULT 0,"prevemp_tax" numeric DEFAULT 0,"cyear" varchar ,"emp_group" numeric DEFAULT 0,"person_nature" varchar DEFAULT ''::character varying,"medical_aid" numeric DEFAULT 0,"medical_aid_number" varchar DEFAULT ''::character varying,"emp_usescales" numeric DEFAULT 0) WITH OIDS;
SELECT setval('employees_empnum_seq',1);
CREATE TABLE sup_stmnt ("supid" numeric DEFAULT 0,"edate" date ,"ref" varchar ,"cacc" numeric DEFAULT 0,"descript" varchar ,"amount" numeric DEFAULT 0,"ex" varchar ,"div" numeric DEFAULT 0,"timeadded" timestamp ,"id" serial NOT NULL PRIMARY KEY ,"allocation_balance" numeric(16, 2) DEFAULT 0,"allocation_processed" numeric DEFAULT 0,"allocation_linked" varchar DEFAULT ''::character varying,"allocation_amounts" varchar DEFAULT ''::character varying) WITH OIDS;
SELECT setval('sup_stmnt_id_seq',1);
CREATE TABLE invoices ("invid" serial NOT NULL PRIMARY KEY ,"deptid" numeric DEFAULT 0,"cusnum" numeric DEFAULT 0,"cordno" varchar ,"ordno" varchar ,"chrgvat" varchar ,"terms" numeric DEFAULT 0,"salespn" varchar ,"odate" date ,"printed" varchar ,"comm" varchar ,"done" varchar ,"username" varchar ,"deptname" varchar ,"cusacc" varchar ,"cusname" varchar ,"surname" varchar ,"cusaddr" varchar ,"cusordno" varchar ,"cusvatno" varchar ,"prd" numeric DEFAULT 0,"invnum" int4 DEFAULT 0,"div" numeric DEFAULT 0,"age" numeric DEFAULT 0,"prints" numeric DEFAULT 0,"nbal" numeric DEFAULT 0,"disc" numeric(16, 2) DEFAULT 0,"discp" numeric(16, 2) DEFAULT 0,"delchrg" numeric(16, 2) DEFAULT 0,"subtot" numeric(16, 2) DEFAULT 0,"traddisc" numeric(16, 2) DEFAULT 0,"balance" numeric(16, 2) DEFAULT 0,"vat" numeric(16, 2) DEFAULT 0,"total" numeric(16, 2) DEFAULT 0,"serd" varchar ,"docref" varchar ,"jobid" numeric DEFAULT 0,"jobnum" varchar ,"dir" varchar ,"location" varchar ,"fcid" numeric DEFAULT 0,"currency" varchar ,"xrate" numeric(16, 2) DEFAULT 0,"fbalance" numeric(16, 2) DEFAULT 0,"fsubtot" numeric(16, 2) DEFAULT 0,"sordid" numeric DEFAULT 0,"discount" numeric(16, 2) DEFAULT 0,"delivery" numeric(16, 2) DEFAULT 0,"rdelchrg" numeric(16, 2) DEFAULT 0,"delvat" int4 DEFAULT 0,"branch" numeric DEFAULT 0,"del_addr" varchar ,"deldate" date ,"systime" timestamp DEFAULT now(),"bankid" numeric DEFAULT 2,"pslip_sordid" numeric DEFAULT 0,"signed" numeric DEFAULT 0,"dispatched" numeric DEFAULT 0) WITH OIDS;
SELECT setval('invoicesids_seq',1);
CREATE TABLE pickslip_stk_tmp ("id" serial NOT NULL PRIMARY KEY ,"pickslip_id" numeric DEFAULT 0,"stock_id" numeric DEFAULT 0,"qty" numeric DEFAULT 0) WITH OIDS;
SELECT setval('pickslip_stk_tmp_id_seq',1);
CREATE TABLE dissasemble_save ("id" serial NOT NULL PRIMARY KEY ,"stkid" numeric DEFAULT 0,"stk_qty" numeric DEFAULT 0,"stk_val" numeric DEFAULT 0,"session_id" varchar ,"m_stock_id" numeric DEFAULT 0) WITH OIDS;
SELECT setval('dissasemble_save_id_seq',1);
CREATE TABLE pettycashbook ("cashid" serial NOT NULL PRIMARY KEY ,"date" date ,"name" varchar ,"det" varchar ,"amount" numeric DEFAULT 0,"accid" numeric DEFAULT 0,"approved" varchar ,"refno" varchar ,"chrgvat" varchar ,"div" numeric DEFAULT 0,"vatcode" int4 DEFAULT 0,"reced" varchar ,"vat_paid" numeric(16, 2) DEFAULT 0) WITH OIDS;
SELECT setval('pettycashbook_cashid_seq',1);
CREATE TABLE depts ("deptid" serial NOT NULL PRIMARY KEY ,"dept" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
SELECT setval('depts_deptid_seq',15);
INSERT INTO depts ("deptid","dept","div") VALUES('10','SERVICES','0');
INSERT INTO depts ("deptid","dept","div") VALUES('4','AUDIT','0');
INSERT INTO depts ("deptid","dept","div") VALUES('8','SALARIES','0');
INSERT INTO depts ("deptid","dept","div") VALUES('5','PURCHASES','0');
INSERT INTO depts ("deptid","dept","div") VALUES('7','STOCK','0');
INSERT INTO depts ("deptid","dept","div") VALUES('2','ACCOUNTING','0');
INSERT INTO depts ("deptid","dept","div") VALUES('6','SALES','0');
INSERT INTO depts ("deptid","dept","div") VALUES('11','SETTINGS','0');
INSERT INTO depts ("deptid","dept","div") VALUES('1','CREDITORS','0');
INSERT INTO depts ("deptid","dept","div") VALUES('3','DEBTORS','0');
INSERT INTO depts ("deptid","dept","div") VALUES('13','MYBUSINESS','0');
INSERT INTO depts ("deptid","dept","div") VALUES('14','DOCUMENT MANAGEMENT','0');
INSERT INTO depts ("deptid","dept","div") VALUES('15','PROJECT MANAGEMENT','0');
INSERT INTO depts ("deptid","dept","div") VALUES('16','HIRING','0');
CREATE TABLE empc ("id" serial NOT NULL PRIMARY KEY ,"cid" int4 DEFAULT 0,"emp" int4 DEFAULT 0,"amount" numeric(16, 2) DEFAULT 0) WITH OIDS;
SELECT setval('empc_id_seq',1);
CREATE TABLE serial6 ("stkid" numeric DEFAULT 0,"serno" varchar ,"rsvd" varchar ,"warranty" date ) WITH OIDS;
CREATE TABLE pos_trans_items ("id" serial NOT NULL PRIMARY KEY ,"stkid" numeric DEFAULT 0,"qty" numeric DEFAULT 0,"unitcost" numeric(16, 2) DEFAULT 0,"amt" numeric(16, 2) DEFAULT 0,"trans_id" numeric DEFAULT 0,"cost_price" numeric(16, 2) DEFAULT 0) WITH OIDS;
SELECT setval('pos_trans_items_id_seq',1);
CREATE TABLE open_stmnt ("id" serial NOT NULL PRIMARY KEY ,"type" varchar ,"cusnum" numeric DEFAULT 0,"invid" numeric DEFAULT 0,"date" date ,"st" varchar ,"div" numeric DEFAULT 0,"amount" numeric(16, 2) DEFAULT 0,"balance" numeric(16, 2) DEFAULT 0,"timeadded" timestamp ,"docref" varchar ,"branch" varchar ) WITH OIDS;
SELECT setval('open_stmnt_id_seq',1);
CREATE TABLE ss4 ("stock" int4 DEFAULT 0,"code" varchar ,"div" numeric DEFAULT 0,"active" varchar DEFAULT 'yes'::character varying) WITH OIDS;
CREATE TABLE monthcloses ("id" serial NOT NULL PRIMARY KEY ,"type" varchar ,"closedate" date ,"closeby" varchar ,"ext" varchar ) WITH OIDS;
SELECT setval('monthcloses_id_seq',1);
CREATE TABLE ncs ("id" serial NOT NULL PRIMARY KEY ,"temp" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
SELECT setval('ncs_id_seq',1);
CREATE TABLE nons_note_items ("id" serial NOT NULL PRIMARY KEY ,"noteid" numeric DEFAULT 0,"qty" numeric DEFAULT 0,"description" varchar ,"amt" numeric(16, 2) DEFAULT 0,"unitcost" numeric(16, 2) DEFAULT 0,"vatcode" numeric DEFAULT 0) WITH OIDS;
SELECT setval('nons_note_items_id_seq',1);
CREATE TABLE pc ("id" serial NOT NULL PRIMARY KEY ,"date" date ,"by" varchar ,"inv" int4 DEFAULT 0,"amount" numeric(16, 2) DEFAULT 0) WITH OIDS;
SELECT setval('pc_id_seq',1);
CREATE TABLE dnotes ("id" serial NOT NULL PRIMARY KEY ,"purid" int4 DEFAULT 0,"date" date ,"sub" numeric(16, 2) DEFAULT 0,"vat" numeric(16, 2) DEFAULT 0,"tot" numeric(16, 2) DEFAULT 0) WITH OIDS;
SELECT setval('dnotes_id_seq',1);
CREATE TABLE purch_batch_entries ("id" serial NOT NULL PRIMARY KEY ,"supplier" numeric DEFAULT 0,"account" numeric DEFAULT 0,"pdate" date ,"sdate" date ,"vat" varchar ,"vatcode" numeric DEFAULT 0,"supinv" varchar ,"description" varchar ,"qty" numeric DEFAULT 0,"price" numeric(16, 2) DEFAULT 0) WITH OIDS;
SELECT setval('purch_batch_entries_id_seq',1);
CREATE TABLE training ("trainnum" serial NOT NULL PRIMARY KEY ,"empnum" varchar ,"course_name" varchar ,"commence_date" date ,"completed_date" date ,"supid" varchar ,"assessor_name" varchar ,"training_cost" varchar ,"competent_date" date ,"other_details" varchar ,"div" varchar ,"date_date" date ) WITH OIDS;
SELECT setval('training_trainnum_seq',1);
CREATE TABLE debtors_batch ("batchid" serial NOT NULL PRIMARY KEY ,"ordnum" numeric DEFAULT 0,"paidamt" numeric DEFAULT 0,"accpaid" numeric DEFAULT 0,"proc" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
SELECT setval('debtors_batch_batchid_seq',1);
CREATE TABLE hire_stock_items ("id" serial NOT NULL PRIMARY KEY ,"stock_id" numeric DEFAULT 0,"invoice_id" numeric DEFAULT 0,"qty" numeric DEFAULT 0,"unitprice" numeric(16, 2) DEFAULT 0,"total" numeric(16, 2) DEFAULT 0,"type" numeric DEFAULT 1,"cost_price" numeric(16, 2) DEFAULT 0) WITH OIDS;
SELECT setval('hire_stock_items_id_seq',1);
CREATE TABLE ss0 ("stock" int4 DEFAULT 0,"code" varchar ,"div" numeric DEFAULT 0,"active" varchar DEFAULT 'yes'::character varying) WITH OIDS;
CREATE TABLE email_groups ("id" serial NOT NULL PRIMARY KEY ,"email_group" varchar ,"emailaddress" varchar ,"date_added" date ) WITH OIDS;
SELECT setval('email_groups_id_seq',1);
CREATE TABLE hire_trans_items ("id" serial NOT NULL PRIMARY KEY ,"hire_id" numeric DEFAULT 0,"asset_id" numeric DEFAULT 0,"basis" varchar DEFAULT 'per_day'::character varying,"from_date" date ,"to_date" date ,"half_day" bool DEFAULT false,"qty" numeric DEFAULT 0,"weekends" bool DEFAULT false,"total_days" numeric DEFAULT 0,"total" numeric(16, 2) DEFAULT 0,"discount_perc" numeric(16, 2) DEFAULT 0,"returned" bool DEFAULT false,"processed" bool DEFAULT false) WITH OIDS;
SELECT setval('hire_trans_items_id_seq',1);
CREATE TABLE medical_aid ("id" serial NOT NULL PRIMARY KEY ,"medical_aid_name" varchar DEFAULT ''::character varying,"medical_aid_contact_person" varchar DEFAULT ''::character varying,"medical_aid_contact_number" varchar DEFAULT ''::character varying,"medical_aid_bank_name" varchar DEFAULT ''::character varying,"medical_aid_bank_account" varchar DEFAULT ''::character varying,"medical_aid_bank_branch" varchar DEFAULT ''::character varying) WITH OIDS;
SELECT setval('medical_aid_id_seq',1);
CREATE TABLE crednot ("salprsn" varchar ,"cusnum" varchar ,"invnum" int4 DEFAULT 0,"ordnum" int4 DEFAULT 0,"crdnte" varchar ,"cusnme" varchar ,"cuspos1" varchar ,"cuspos2" varchar ,"cuspos3" varchar ,"poscode" varchar ,"custel" varchar ,"cusfax" varchar ,"cuseml" varchar ,"contact" varchar ,"amtdue" numeric DEFAULT 0,"div" numeric DEFAULT 0) WITH OIDS;
CREATE TABLE cord_data ("sordid" numeric DEFAULT 0,"dept" varchar ,"customer" varchar ,"addr1" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
CREATE TABLE delnote ("noteid" serial NOT NULL PRIMARY KEY ,"deptid" numeric DEFAULT 0,"cusnum" numeric DEFAULT 0,"cusname" varchar ,"contname" varchar ,"cellno" varchar ,"jobid" numeric DEFAULT 0,"jobnum" varchar ,"ndate" date ,"cordno" varchar ,"ordno" varchar ,"username" varchar ,"printed" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
SELECT setval('delnote_noteid_seq',1);
CREATE TABLE fringebens ("id" serial NOT NULL PRIMARY KEY ,"fringeben" varchar ,"accid" numeric DEFAULT 0,"type" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
SELECT setval('fringebens_id_seq',1);
CREATE TABLE mail_accounts ("account_id" serial NOT NULL PRIMARY KEY ,"username" varchar ,"user_edit" varchar ,"active" varchar ,"account_name" varchar ,"server_type" varchar ,"server_host" varchar ,"server_user" varchar ,"server_pass" varchar ,"leave_msgs" varchar ,"enable_smtp" varchar ,"smtp_from" varchar ,"smtp_reply" varchar ,"smtp_host" varchar ,"smtp_auth" varchar ,"smtp_user" varchar ,"smtp_pass" varchar ,"signature" text ,"public" varchar ,"crmteam" int4 DEFAULT 0) WITH OIDS;
SELECT setval('mail_accounts_account_id_seq',1);
CREATE TABLE maritalstatus ("status" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
CREATE TABLE ss2 ("stock" int4 DEFAULT 0,"code" varchar ,"div" numeric DEFAULT 0,"active" varchar DEFAULT 'yes'::character varying) WITH OIDS;
CREATE TABLE template_colors ("id" serial NOT NULL PRIMARY KEY ,"setting" varchar ,"value" varchar ,"description" varchar ) WITH OIDS;
SELECT setval('template_colors_id_seq',23);
INSERT INTO template_colors ("id","setting","value","description") VALUES('1','TMPL_bgColor','#4477BB','Background Color');
INSERT INTO template_colors ("id","setting","value","description") VALUES('2','TMPL_fntColor','#FFFFFF','Font Color 1');
INSERT INTO template_colors ("id","setting","value","description") VALUES('3','TMPL_fntColor2','#000000','Font COlor 2');
INSERT INTO template_colors ("id","setting","value","description") VALUES('4','TMPL_lnkColor','#0000DD','Link Color 1');
INSERT INTO template_colors ("id","setting","value","description") VALUES('5','TMPL_lnkHvrColor','#FF0000','Link Hover Color');
INSERT INTO template_colors ("id","setting","value","description") VALUES('6','TMPL_navLnkColor','#CCCCCC','Navigation Link Color');
INSERT INTO template_colors ("id","setting","value","description") VALUES('7','TMPL_navLnkHvrColor','#FFFFFF','Navigation Link Hover Color');
INSERT INTO template_colors ("id","setting","value","description") VALUES('8','TMPL_fntSize','10','Default Font Size');
INSERT INTO template_colors ("id","setting","value","description") VALUES('9','TMPL_fntFamily','arial','Font Type');
INSERT INTO template_colors ("id","setting","value","description") VALUES('10','TMPL_h2FntSize','14','Size 2 Heading Size');
INSERT INTO template_colors ("id","setting","value","description") VALUES('11','TMPL_h2Color','#FFFFFF','Size 2 Heading Color');
INSERT INTO template_colors ("id","setting","value","description") VALUES('12','TMPL_h3FntSize','12','Size 3 Heading Size');
INSERT INTO template_colors ("id","setting","value","description") VALUES('13','TMPL_h3Color','#FFFFFF','Size 3 Heading Color');
INSERT INTO template_colors ("id","setting","value","description") VALUES('14','TMPL_h4FntSize','10','Size 4 Heading Size');
INSERT INTO template_colors ("id","setting","value","description") VALUES('15','TMPL_h4Color','#FFFFFF','Heading Size 4 Color');
INSERT INTO template_colors ("id","setting","value","description") VALUES('16','TMPL_tblBrdrColor','#FFFFFF','Table Border Color');
INSERT INTO template_colors ("id","setting","value","description") VALUES('17','TMPL_tblCellSpacing','1','Table Cell Spacing Size');
INSERT INTO template_colors ("id","setting","value","description") VALUES('18','TMPL_tblCellPadding','1','Table Cell Padding Size');
INSERT INTO template_colors ("id","setting","value","description") VALUES('19','TMPL_tblDataColor1','#88BBFF','Data Cell Background Color 1');
INSERT INTO template_colors ("id","setting","value","description") VALUES('20','TMPL_tblDataColor2','#77AAEE','Data Cell Background Color 2');
INSERT INTO template_colors ("id","setting","value","description") VALUES('21','TMPL_tblDataColorOver','#FFFFFF','Mouse Over Data Cell Background Color');
INSERT INTO template_colors ("id","setting","value","description") VALUES('22','TMPL_tblHdngBg','#114488','Table Cell Heading Background Color');
INSERT INTO template_colors ("id","setting","value","description") VALUES('23','TMPL_tblHdngColor','#FFFFFF','Table Cell Background Color');
CREATE TABLE crec ("id" serial NOT NULL PRIMARY KEY ,"userid" int4 DEFAULT 0,"username" varchar ,"amount" numeric(16, 2) DEFAULT 0,"pdate" date ,"inv" int4 DEFAULT 0) WITH OIDS;
SELECT setval('crec_id_seq',1);
CREATE TABLE login_retries ("tries" numeric DEFAULT 0,"minutes" numeric DEFAULT 0) WITH OIDS;
CREATE TABLE ss1 ("stock" int4 DEFAULT 0,"code" varchar ,"div" numeric DEFAULT 0,"active" varchar DEFAULT 'yes'::character varying) WITH OIDS;
CREATE TABLE workshop ("refnum" serial NOT NULL PRIMARY KEY ,"stkcod" varchar ,"cusnum" numeric DEFAULT 0,"description" varchar ,"notes" varchar ,"status" varchar ,"serno" varchar ,"cdate" date ,"active" varchar ,"assetid" int4 DEFAULT 0,"asset" varchar ,"asset_id" numeric DEFAULT 0,"e_date" date ) WITH OIDS;
SELECT setval('workshop_refnum_seq',1);
CREATE TABLE cancelled_callout ("calloutid" numeric DEFAULT 0,"username" varchar ,"date" date ,"deptid" numeric DEFAULT 0,"deptname" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
CREATE TABLE cancelled_pos_quo ("quoid" numeric DEFAULT 0,"username" varchar ,"date" date ,"deptid" numeric DEFAULT 0,"deptname" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
CREATE TABLE serial ("stkid" numeric DEFAULT 0,"serno" varchar ,"rsvd" varchar ,"description" varchar ,"warranty" varchar ,"status" varchar ) WITH OIDS;
CREATE TABLE currency ("fcid" serial NOT NULL PRIMARY KEY ,"symbol" varchar ,"descrip" varchar ,"rate" numeric(16, 2) DEFAULT 0,"def" varchar ,"curcode" varchar ) WITH OIDS;
SELECT setval('currency_fcid_seq',1);
CREATE TABLE nons_invoices ("invid" serial NOT NULL PRIMARY KEY ,"cusname" varchar ,"cusaddr" varchar ,"cusvatno" varchar ,"chrgvat" varchar ,"sdate" date ,"done" varchar ,"username" varchar ,"prd" numeric DEFAULT 0,"invnum" int4 DEFAULT 0,"div" numeric DEFAULT 0,"remarks" text ,"cusid" int4 DEFAULT 0,"age" numeric DEFAULT 0,"typ" varchar ,"subtot" numeric(16, 2) DEFAULT 0,"balance" numeric(16, 2) DEFAULT 0,"vat" numeric(16, 2) DEFAULT 0,"total" numeric(16, 2) DEFAULT 0,"descrip" varchar ,"ctyp" varchar ,"accid" numeric DEFAULT 0,"tval" varchar ,"docref" varchar ,"jobid" numeric DEFAULT 0,"jobnum" varchar ,"labid" numeric DEFAULT 0,"location" varchar ,"fcid" numeric DEFAULT 0,"currency" varchar ,"xrate" numeric(16, 2) DEFAULT 0,"fbalance" numeric(16, 2) DEFAULT 0,"fsubtot" numeric(16, 2) DEFAULT 0,"accepted" varchar ,"cordno" varchar ,"terms" numeric DEFAULT 0,"odate" date ,"salespn" varchar ,"deldate" date ,"systime" timestamp DEFAULT now(),"bankid" numeric DEFAULT 2,"cusordno" varchar DEFAULT ''::character varying,"lead" varchar DEFAULT ''::character varying,"ncdate" date DEFAULT now(),"cusnum" numeric DEFAULT 0,"discount" numeric DEFAULT 0,"delivery" numeric DEFAULT 0,"hire_invid" numeric DEFAULT 0,"hire_invnum" varchar DEFAULT 0,"cash" numeric DEFAULT 0,"cheque" numeric(16, 2) DEFAULT 0,"credit" numeric(16, 2) DEFAULT 0,"multiline" varchar DEFAULT 'no'::character varying) WITH OIDS;
SELECT setval('invoicesids_seq',1);
CREATE TABLE nc_popup_data ("id" serial NOT NULL PRIMARY KEY ,"type" varchar ,"typename" varchar ,"edate" date ,"descrip" varchar ,"amount" varchar ,"cdescrip" varchar ,"cosamt" varchar ,"sdate" date ) WITH OIDS;
SELECT setval('nc_popup_data_id_seq',1);
CREATE TABLE nons_purint_items ("id" serial NOT NULL PRIMARY KEY ,"purid" numeric DEFAULT 0,"cod" varchar ,"des" varchar ,"qty" numeric DEFAULT 0,"ddate" date ,"div" numeric DEFAULT 0,"rqty" numeric DEFAULT 0,"unitcost" numeric(16, 2) DEFAULT 0,"cunitcost" numeric(16, 2) DEFAULT 0,"duty" numeric(16, 2) DEFAULT 0,"dutyp" numeric(16, 2) DEFAULT 0,"amt" numeric(16, 2) DEFAULT 0,"accid" numeric DEFAULT 0,"svat" numeric(16, 2) DEFAULT 0) WITH OIDS;
SELECT setval('nons_purint_items_id_seq',1);
CREATE TABLE pslip_items ("id" serial NOT NULL PRIMARY KEY ,"slipid" numeric DEFAULT 0,"whid" numeric DEFAULT 0,"stkid" numeric DEFAULT 0,"qty" numeric DEFAULT 0,"pqty" numeric DEFAULT 0,"unitcost" numeric(16, 2) DEFAULT 0,"amt" numeric(16, 2) DEFAULT 0,"div" numeric DEFAULT 0) WITH OIDS;
SELECT setval('pslip_items_id_seq',1);
CREATE TABLE vatreminder ("id" serial NOT NULL PRIMARY KEY ,"username" varchar ,"opt" varchar ,"val" int4 DEFAULT 0,"reminded" timestamp ) WITH OIDS;
SELECT setval('vatreminder_id_seq',1);
CREATE TABLE costcenters ("ccid" serial NOT NULL PRIMARY KEY ,"centercode" varchar ,"centername" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
SELECT setval('costcenters_ccid_seq',2);
INSERT INTO costcenters ("ccid","centercode","centername","div") VALUES('2','111','Cost Center 1','2');
CREATE TABLE cashup_cache ("id" serial NOT NULL PRIMARY KEY ,"date" date ,"username" varchar ,"stock" varchar ,"total_sold" numeric DEFAULT 0,"total_price" numeric(16, 2) DEFAULT 0) WITH OIDS;
SELECT setval('cashup_cache_id_seq',1);
CREATE TABLE forecast_items ("id" serial NOT NULL PRIMARY KEY ,"forecast_id" numeric DEFAULT 0,"stkid" numeric DEFAULT 0,"actual" numeric(16, 2) DEFAULT 0,"projected" numeric(16, 2) DEFAULT 0) WITH OIDS;
SELECT setval('forecast_items_id_seq',1);
CREATE TABLE egroups ("id" serial NOT NULL PRIMARY KEY ,"grouptitle" varchar ,"groupname" varchar ) WITH OIDS;
SELECT setval('egroups_id_seq',1);
CREATE TABLE loan_requests ("id" serial NOT NULL PRIMARY KEY ,"empnum" numeric DEFAULT 0,"loanamt" numeric DEFAULT 0,"loaninstall" numeric DEFAULT 0,"loanint" numeric DEFAULT 0,"loanperiod" numeric DEFAULT 0,"loandate" date ,"loan_type" numeric DEFAULT 0,"div" numeric DEFAULT 0,"loan_account" numeric DEFAULT 0,"bankacc" numeric DEFAULT 0,"date" date ,"totamount" numeric(16, 2) DEFAULT 0,"loanint_amt" numeric(16, 2) DEFAULT 0,"fringebenefit" numeric(16, 2) DEFAULT 0,"ldate" date ,"account" numeric DEFAULT 0,"accid" numeric DEFAULT 0) WITH OIDS;
SELECT setval('loan_requests_id_seq',1);
CREATE TABLE nons_inv_notes ("noteid" serial NOT NULL PRIMARY KEY ,"invid" numeric DEFAULT 0,"invnum" numeric DEFAULT 0,"cusname" varchar ,"cusaddr" varchar ,"cusvatno" varchar ,"chrgvat" varchar ,"date" date ,"subtot" numeric(16, 2) DEFAULT 0,"vat" numeric(16, 2) DEFAULT 0,"total" numeric(16, 2) DEFAULT 0,"username" varchar ,"prd" numeric DEFAULT 0,"notenum" numeric DEFAULT 0,"ctyp" varchar ,"div" numeric DEFAULT 0,"location" varchar ,"currency" varchar ,"fcid" varchar DEFAULT 0,"remarks" varchar ,"bankid" numeric DEFAULT 2) WITH OIDS;
SELECT setval('nons_inv_notes_noteid_seq',1);
CREATE TABLE pinvoices ("invid" serial NOT NULL PRIMARY KEY ,"deptid" numeric DEFAULT 0,"cusnum" numeric DEFAULT 0,"cordno" varchar ,"ordno" varchar ,"chrgvat" varchar ,"terms" numeric DEFAULT 0,"salespn" varchar ,"odate" date ,"printed" varchar ,"comm" varchar ,"done" varchar ,"username" varchar ,"deptname" varchar ,"cusacc" varchar ,"cusname" varchar ,"surname" varchar ,"cusaddr" varchar ,"cusordno" varchar ,"cusvatno" varchar ,"prd" numeric DEFAULT 0,"invnum" int4 DEFAULT 0,"div" numeric DEFAULT 0,"prints" numeric DEFAULT 0,"disc" numeric(16, 2) DEFAULT 0,"discp" numeric(16, 2) DEFAULT 0,"delchrg" numeric(16, 2) DEFAULT 0,"subtot" numeric(16, 2) DEFAULT 0,"traddisc" numeric(16, 2) DEFAULT 0,"balance" numeric(16, 2) DEFAULT 0,"vat" numeric(16, 2) DEFAULT 0,"total" numeric(16, 2) DEFAULT 0,"discount" numeric(16, 2) DEFAULT 0,"delivery" numeric(16, 2) DEFAULT 0,"nbal" numeric(16, 2) DEFAULT 0,"rdelchrg" numeric(16, 2) DEFAULT 0,"serd" varchar ,"pcash" numeric(16, 2) DEFAULT 0,"pcheque" numeric(16, 2) DEFAULT 0,"pcc" numeric(16, 2) DEFAULT 0,"rounding" numeric(16, 2) DEFAULT 0,"pchange" numeric(16, 2) DEFAULT 0,"delvat" int4 DEFAULT 0,"pcredit" numeric(16, 2) DEFAULT 0,"vatnum" varchar ,"telno" varchar ,"systime" timestamp DEFAULT now(),"bankid" numeric DEFAULT 2,"fcid" numeric DEFAULT 0,"pslip_sordid" numeric DEFAULT 0) WITH OIDS;
SELECT setval('invoicesids_seq',1);
CREATE TABLE set ("type" varchar ,"label" varchar ,"value" varchar ,"descript" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
CREATE TABLE asset_svdates ("id" serial NOT NULL PRIMARY KEY ,"asset_id" numeric DEFAULT 0,"svdate" date ,"des" varchar ) WITH OIDS;
SELECT setval('asset_svdates_id_seq',1);
CREATE TABLE exports ("expid" int4 DEFAULT 0,"vessel" varchar ,"date" date ,"harbour" varchar ,"bags" numeric DEFAULT 0,"loads" numeric DEFAULT 0,"div" numeric DEFAULT 0) WITH OIDS;
CREATE TABLE settings ("constant" varchar ,"label" varchar ,"value" varchar ,"type" varchar ,"datatype" varchar ,"minlen" int2 DEFAULT 0,"maxlen" int2 DEFAULT 0,"div" numeric DEFAULT 0,"readonly" bool ) WITH OIDS;
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('COMP_ADDRESS','Company address','Unit 3 - 129 9th Road, Kew 2090<br>
P.O Box 39818, Bramley 2018<br>
South Africa','company','allstring','1','250','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('COMP_NAME','Company name','SABolt Distribution','company','allstring','1','250','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('COMP_SLOGAN','Company slogan','Distributors of mining & industrial fasteners.','company','allstring','1','250','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('MAX_PCHS','Maximum Rand value of purchase before auth','2500','general','num','1','250','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('SESSION_TIMEOUT','Session timeout in minutes (not working)','20','general','num','1','250','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('TMPL_fntSize','Template default font-size','10','layout','num','1','250','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('TMPL_h3FntSize','Template heading size','12','layout','num','1','250','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('TMPL_h4FntSize','Template heading size','10','layout','num','1','250','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('TMPL_tblCellPadding','Template table cellpadding','2','layout','num','1','250','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('TMPL_tblCellSpacing','Template table cellspacing','1','layout','num','1','250','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('TMPL_title','Template page-title','Cubit Accounting','layout','string','1','250','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('COMP_FAX','Company fax no','27 11 882-1670','company','string','1','250','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('COMP_TEL','Company telephone no','27 11 882-1550','company','string','1','250','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('COMP_VATNO','Company VAT no','4820116814','company','num','1','250','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('TMPL_fntFamily','Template default font family','sans-serif','layout','string','1','250','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('TMPL_fntColor','Template - Default font-color','#000000','layout','allstring','7','7','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('TMPL_h3Color','Template large heading color','#FFFFFF','layout','allstring','7','7','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('TMPL_h4Color','Template heading color','#FFFFFF','layout','allstring','7','7','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('TMPL_lnkColor','Template default link-color','#0000DD','layout','allstring','7','7','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('TMPL_lnkHvrColor','Template default link-color (hover)','#FF0000','layout','allstring','7','7','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('TMPL_navLnkColor','Template navigation-link color','#CCCCCC','layout','allstring','7','7','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('TMPL_navLnkHvrColor','Template navigation-link color (hover)','#FFFFFF','layout','allstring','7','7','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('TMPL_tblDataColor1','Template data row color','#88BBFF','layout','allstring','7','7','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('TMPL_tblDataColor2','Template data row color (alternative)','#77AAEE','layout','allstring','7','7','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('TMPL_tblHdngBg','Template table-heading background color','#114488','layout','allstring','7','7','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('TMPL_tblHdngColor','Template table heading font color','#FFFFFF','layout','allstring','7','7','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('TMPL_bgColor','Template background-color','#4477BB','layout','allstring','7','7','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('TMPL_hrColor','Horizontal rule color','#000000','Layout','allstring','1','7','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('LEAVE_VAC','Paid vacation leave (days)','14','accounting','num','1','2','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('LEAVE_SICK','Paid sick leave (days)','14','accounting','num','1','2','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('LEAVE_STUDY','Paid study leave (days)','14','accounting','num','1','2','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('COMMISSION','Commission on sales','10','accounting','num','1','2','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('DEFAULT_COMMENTS','Default Comments','','company','allstring','1','255','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('VAT_REG','VAT Registered','no','vat','allstring','1','3','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('TAX_PRDCAT','TAX Period Category','none','vat','allstring','1','4','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('UIF_IND','UIF (Individual)','1','accounting','float','1','250','0','t');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('UIF_COMP','UIF (Company)','1','accounting','float','1','250','0','t');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('TAX_VAT','VAT','14','accounting','float','1','250','0','t');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('DEFAULT_STMNT_COMMENTS','Default Statement Comments','','company','allstring','1','255','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('PENSION','Pension rate','50','accounting','float','1','250','0','t');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('PIR','Prime interest rate','0','accounting','float','1','250','0','t');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('SDL','Skills Development Levy','1','accounting','num','1','2','0','t');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('DEFAULT_POS_COMMENTS','Default POS Comments','','company','allstring','1','255','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('LOCALE_TIMEZONE','Timezone of Cubit Installation','Africa/Johannesburg','locale','string','1','100','0','t');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('LOCALE_DEFAULT','Default Locale','en_ZA','locale','string','1','100','0','t');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('EMPLOAN_MTHS','Default Loan Payback Period (Months)','12','accounting','num','1','3','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('SDLPAYABLE','SDL Payable','y','static','string','1','1','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('EMP_SALMSG','Salary Messages','mfwd','accounting','string','0','4','0','t');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('EMP_PRINTSLIP','Print Payslip after Processing Salaries','y','accounting','string','1','1','0','t');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('VAT_PERIOD','VAT Period','2','accounting','num','1','2','0','t');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('POSMSG','Point of Sale Slip Message','THANK YOU FOR YOUR PURCHASE','static','string','1','1','0','t');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('CONTRACT_TEXT','Contract Text','VGhpcyBoaXJlIGlzIHN1YmplY3QgdG8gY29tcGFueSBoaXJlIGNvbmRpdGlvbnMu','company','string','0','256','2','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('EMPLOAN_INT','Interest on employee loans','9','static','float','1','5','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('NEWINV_SETTING','Create A New Invoice After Processing The Last One','no','general','string','2','3','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('SET_INV_TRADDISC','Include/Exclude  Delivery Charge In Trade Discount','exclude','general','string','7','7','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('SUPP_PAY_DATE','Last Supplier Payment Date Used','2007-01-01','general','string','10','10','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('DEFAULT_BANK_RECPT_COMMENTS','Comment That Appears On Customer Bank Receipt','','general','string','10','10','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('PRINT_PSLIPS_BATCH','Print Multiple Salary Slips On Single Page','yes','general','allstring','2','3','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('STATEMENT_AGE','Customer Statement','statement','general','allstring','6','9','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('PRINT_DIALOG','Printer Popup','y','general','string','1','1','0','t');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('SUPP_PAY_TYPE','Supplier Payment Type','cheq_man','general','allstring','1','20','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('SUPP_PROCESS_TYPE','Supplier Payment Process Type','now','general','allstring','1','20','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('UIF_MAX','Max amount allowed for UIF','124.78','accounting','float','1','6','0','t');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('SORDER_NEG_STOCK','Show Negative Stock On Sales Order','yes','static','allstring','1','250','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('CUST_PRINT_RECPT','Default Print Customer Receipt','yes','general','allstring','0','50','0','f');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('REBATE_65UNDER','Tax rebate for persons under 65','10260','accounting','num','1','9','0','t');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('REBATE_65PLUS','Tax rebate for persons over 65','5675','accounting','num','1','9','0','t');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('EMP_TAXYEAR','Employee Tax Year','2013','accounting','num','4','4','0','t');
INSERT INTO settings ("constant","label","value","type","datatype","minlen","maxlen","div","readonly") VALUES('EMPLOAN_FRINGEINT','Interest Rate on which Loan Fringe Benifit is calculated','13','static','float','1','5','0','t');
CREATE TABLE statement_data ("id" serial NOT NULL PRIMARY KEY ,"date" date ,"amount" numeric(16, 2) DEFAULT 0,"description" varchar ,"contra" varchar ,"code" varchar ,"ex1" varchar ,"ex2" varchar ,"ex3" varchar ,"by" varchar ,"bank" varchar ,"account" int4 DEFAULT 0) WITH OIDS;
SELECT setval('statement_data_id_seq',1);
CREATE TABLE vatcodes ("id" serial NOT NULL PRIMARY KEY ,"code" varchar ,"description" varchar ,"del" varchar ,"zero" varchar ,"vat_amount" numeric(16, 2) DEFAULT 0) WITH OIDS;
SELECT setval('vatcodes_id_seq',1);
CREATE TABLE stkimgs ("id" serial NOT NULL PRIMARY KEY ,"stkid" numeric DEFAULT 0,"image" text ,"imagetype" varchar ) WITH OIDS;
SELECT setval('stkimgs_id_seq',1);
CREATE TABLE stock_take ("id" serial NOT NULL PRIMARY KEY ,"stkid" numeric DEFAULT 0,"qty" numeric DEFAULT 0,"adjusted" numeric DEFAULT 0,"page" numeric DEFAULT 0,"adjust_val" numeric DEFAULT 0) WITH OIDS;
SELECT setval('stock_take_id_seq',1);
CREATE TABLE empreports ("id" int4 DEFAULT 0,"empnum" int4 DEFAULT 0,"date" date ,"type" varchar ,"submitter" varchar ,"submitter2" varchar ,"submitter3" varchar ,"submitter4" varchar ,"report" text ,"div" numeric DEFAULT 0) WITH OIDS;
CREATE TABLE ss5 ("stock" int4 DEFAULT 0,"code" varchar ,"div" numeric DEFAULT 0,"active" varchar DEFAULT 'yes'::character varying) WITH OIDS;
CREATE TABLE callout_docs ("calloutid" serial NOT NULL PRIMARY KEY ,"deptid" numeric DEFAULT 0,"cusnum" numeric DEFAULT 0,"cordno" varchar ,"ordno" varchar ,"odate" date ,"accepted" varchar ,"comm" varchar ,"done" varchar ,"username" varchar ,"deptname" varchar ,"cusacc" varchar ,"cusname" varchar ,"surname" varchar ,"cusaddr" varchar ,"cusordno" varchar ,"cusvatno" varchar ,"div" numeric DEFAULT 0,"disc" numeric(16, 2) DEFAULT 0,"discp" numeric(16, 2) DEFAULT 0,"subtot" numeric(16, 2) DEFAULT 0,"jobid" numeric DEFAULT 0,"jobnum" varchar ,"location" varchar ,"fcid" numeric DEFAULT 0,"xrate" numeric(16, 2) DEFAULT 0,"fbalance" numeric(16, 2) DEFAULT 0,"fsubtot" numeric(16, 2) DEFAULT 0,"calloutdescrip" varchar ,"calloutp" varchar ,"sign" varchar ,"def_travel" varchar ,"def_labour" varchar ,"invoiced" varchar DEFAULT 'no'::character varying) WITH OIDS;
SELECT setval('callout_docs_calloutid_seq',1);
CREATE TABLE emp_ded ("id" serial NOT NULL PRIMARY KEY ,"emp" int4 DEFAULT 0,"year" int4 DEFAULT 0,"period" int4 DEFAULT 0,"date" date ,"payslip" int4 DEFAULT 0,"type" varchar ,"code" varchar ,"description" varchar ,"qty" int4 DEFAULT 0,"rate" numeric(16, 2) DEFAULT 0,"amount" numeric(16, 2) DEFAULT 0,"ex" varchar ,"amount_employer" numeric(16, 2) DEFAULT 0,"week" varchar ) WITH OIDS;
SELECT setval('emp_ded_id_seq',1);
CREATE TABLE nons_pur_items ("id" serial NOT NULL PRIMARY KEY ,"purid" numeric DEFAULT 0,"cod" varchar ,"des" varchar ,"qty" numeric DEFAULT 0,"ddate" date ,"div" numeric DEFAULT 0,"rqty" numeric DEFAULT 0,"unitcost" numeric(16, 2) DEFAULT 0,"amt" numeric(16, 2) DEFAULT 0,"accid" numeric DEFAULT 0,"svat" numeric(16, 2) DEFAULT 0,"vatcode" int4 DEFAULT 0,"is_asset" varchar DEFAULT 'no'::character varying) WITH OIDS;
SELECT setval('nons_pur_items_id_seq',1);
CREATE TABLE email_queue ("id" serial NOT NULL PRIMARY KEY ,"emailaddress" varchar DEFAULT ''::character varying,"subject" varchar DEFAULT ''::character varying,"message" text DEFAULT ''::text,"status" varchar DEFAULT ''::character varying,"date_added" date ,"date_sent" date ,"completed" varchar DEFAULT ''::character varying,"groupname" varchar DEFAULT ''::character varying,"failed_reason" varchar DEFAULT ''::character varying,"status2" varchar DEFAULT ''::character varying,"attachment" numeric DEFAULT 0,"send_format" varchar DEFAULT 'html'::character varying) WITH OIDS;
SELECT setval('email_queue_id_seq',1);
CREATE TABLE display_images ("id" serial NOT NULL PRIMARY KEY ,"type" varchar ,"ident_id" numeric DEFAULT 0,"image_data" text ,"image_type" text ,"image_name" text ,"image_filename" text ) WITH OIDS;
SELECT setval('display_images_id_seq',1);
CREATE TABLE quote_data ("quoid" numeric DEFAULT 0,"dept" varchar ,"customer" varchar ,"addr1" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
CREATE TABLE deductions ("dedid" int4 DEFAULT 0,"deduction" varchar ,"amount" numeric DEFAULT 0,"percentage" bool ,"add" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
CREATE TABLE non_stock_account_list ("id" serial NOT NULL PRIMARY KEY ,"accid" numeric DEFAULT 0,"accname" varchar DEFAULT ''::character varying) WITH OIDS;
SELECT setval('non_stock_account_list_id_seq',1);
CREATE TABLE stock_take_report ("id" serial NOT NULL PRIMARY KEY ,"timestamp" timestamp ,"stkid" numeric DEFAULT 0,"qty" numeric DEFAULT 0) WITH OIDS;
SELECT setval('stock_take_report_id_seq',1);
CREATE TABLE mail_folders ("folder_id" serial NOT NULL PRIMARY KEY ,"parent_id" int4 DEFAULT 0,"account_id" int4 DEFAULT 0,"icon_open" varchar ,"icon_closed" varchar ,"name" varchar ,"username" varchar ,"public" varchar ) WITH OIDS;
SELECT setval('mail_folders_folder_id_seq',1);
CREATE TABLE mail_attachments ("attach_id" serial NOT NULL PRIMARY KEY ,"type_id" int4 DEFAULT 0,"data" text ,"message_id" int4 DEFAULT 0,"filename" varchar ) WITH OIDS;
SELECT setval('mail_attachments_attach_id_seq',1);
CREATE TABLE pick_slips ("id" serial NOT NULL PRIMARY KEY ,"user_id" numeric DEFAULT 0,"creation_time" timestamp DEFAULT now(),"completed" numeric DEFAULT 0) WITH OIDS;
SELECT setval('pick_slips_id_seq',1);
CREATE TABLE pos_filled ("id" serial NOT NULL PRIMARY KEY ,"method" varchar ) WITH OIDS;
SELECT setval('pos_filled_id_seq',3);
INSERT INTO pos_filled ("id","method") VALUES('1','Internally');
INSERT INTO pos_filled ("id","method") VALUES('2','Externally');
INSERT INTO pos_filled ("id","method") VALUES('3','Other');
CREATE TABLE cancelled_sord ("username" varchar ,"date" date ,"deptid" numeric DEFAULT 0,"deptname" varchar ,"sordid" numeric DEFAULT 0,"div" numeric DEFAULT 0,"cusnum" numeric DEFAULT 0,"cordno" varchar ,"ordno" varchar ,"chrgvat" varchar ,"terms" numeric DEFAULT 0,"salespn" varchar ,"odate" date ,"comm" varchar ,"cusacc" varchar ,"cusname" varchar ,"surname" varchar ,"cusaddr" varchar ,"cusordno" varchar ,"cusvatno" varchar ,"prd" numeric DEFAULT 0,"disc" numeric(16, 2) DEFAULT 0,"discp" numeric(16, 2) DEFAULT 0,"delchrg" numeric(16, 2) DEFAULT 0,"subtot" numeric(16, 2) DEFAULT 0,"traddisc" numeric(16, 2) DEFAULT 0,"balance" numeric(16, 2) DEFAULT 0,"vat" numeric(16, 2) DEFAULT 0,"total" numeric(16, 2) DEFAULT 0,"jobid" numeric DEFAULT 0,"jobnum" varchar ,"dir" varchar ,"location" varchar ,"fcid" numeric DEFAULT 0,"currency" varchar ,"xrate" numeric(16, 2) DEFAULT 0,"fbalance" numeric(16, 2) DEFAULT 0,"fsubtot" numeric(16, 2) DEFAULT 0,"discount" numeric(16, 2) DEFAULT 0,"delivery" numeric(16, 2) DEFAULT 0,"delvat" int4 DEFAULT 0,"display_costs" varchar ,"proforma" varchar ,"pinvnum" varchar ,"purnum" numeric DEFAULT 0,"supid" numeric DEFAULT 0,"supaddr" varchar ,"pdate" date ,"ddate" date ,"remarks" varchar ,"received" varchar ,"refno" varchar ,"varinc" varchar ,"ordernum" varchar ,"part" varchar ,"purid" numeric DEFAULT 0,"edit" numeric DEFAULT 0,"supname" varchar ,"supno" varchar ,"shipchrg" numeric(16, 2) DEFAULT 0,"supinv" varchar ,"apprv" varchar ,"appname" varchar ,"appdate" date ,"rvat" numeric(16, 2) DEFAULT 0,"rsubtot" numeric(16, 2) DEFAULT 0,"rshipchrg" numeric(16, 2) DEFAULT 0,"rtotal" numeric(16, 2) DEFAULT 0,"toggle" varchar ,"cash" varchar ,"shipping" numeric(16, 2) DEFAULT 0,"invcd" varchar ,"rshipping" numeric(16, 2) DEFAULT 0,"noted" varchar ,"returned" varchar ,"iamount" numeric(16, 2) DEFAULT 0,"ivat" numeric(16, 2) DEFAULT 0) WITH OIDS;
CREATE TABLE cashup_paid ("id" serial NOT NULL PRIMARY KEY ,"timestamp" timestamp DEFAULT now(),"user_id" numeric DEFAULT 0,"trans_id" numeric DEFAULT 0,"cash" numeric(16, 2) DEFAULT 0,"cheque" numeric(16, 2) DEFAULT 0,"credit_card" numeric(16, 2) DEFAULT 0) WITH OIDS;
SELECT setval('cashup_paid_id_seq',1);
CREATE TABLE equip_cashup ("id" serial NOT NULL PRIMARY KEY ,"hire_id" numeric DEFAULT 0,"user_id" numeric DEFAULT 0,"stkid" numeric DEFAULT 0,"timestamp" timestamp DEFAULT now(),"qty" numeric DEFAULT 0,"cost_price" numeric(16, 2) DEFAULT 0,"selling_price" numeric(16, 2) DEFAULT 0,"description" varchar ) WITH OIDS;
SELECT setval('equip_cashup_id_seq',1);
CREATE TABLE coms ("rep" varchar ,"date" date ,"amount" numeric DEFAULT 0,"div" numeric DEFAULT 0,"inv" int4 DEFAULT 0,"com" numeric(16, 2) DEFAULT 0) WITH OIDS;
CREATE TABLE statement_settings ("id" serial NOT NULL PRIMARY KEY ,"ad" varchar ) WITH OIDS;
SELECT setval('statement_settings_id_seq',1);
CREATE TABLE pos_quote_items ("quoid" numeric DEFAULT 0,"whid" numeric DEFAULT 0,"stkid" numeric DEFAULT 0,"qty" numeric DEFAULT 0,"div" numeric DEFAULT 0,"amt" numeric(16, 2) DEFAULT 0,"discp" numeric(16, 2) DEFAULT 0,"disc" numeric(16, 2) DEFAULT 0,"unitcost" numeric(16, 2) DEFAULT 0,"vatcode" int4 DEFAULT 0,"description" varchar ,"account" int4 DEFAULT 0) WITH OIDS;
CREATE TABLE rnons_invoices ("invid" serial NOT NULL PRIMARY KEY ,"cusname" varchar ,"cusaddr" varchar ,"cusvatno" varchar ,"chrgvat" varchar ,"sdate" date ,"done" varchar ,"username" varchar ,"prd" numeric DEFAULT 0,"invnum" int4 DEFAULT 0,"div" numeric DEFAULT 0,"remarks" text ,"cusid" int4 DEFAULT 0,"age" numeric DEFAULT 0,"typ" varchar ,"subtot" numeric(16, 2) DEFAULT 0,"balance" numeric(16, 2) DEFAULT 0,"vat" numeric(16, 2) DEFAULT 0,"total" numeric(16, 2) DEFAULT 0,"descrip" varchar ,"ctyp" varchar ,"accid" numeric DEFAULT 0,"tval" varchar ,"docref" varchar ,"jobid" numeric DEFAULT 0,"jobnum" varchar ,"labid" numeric DEFAULT 0,"location" varchar ,"fcid" numeric DEFAULT 0,"currency" varchar ,"xrate" numeric(16, 2) DEFAULT 0,"fbalance" numeric(16, 2) DEFAULT 0,"fsubtot" numeric(16, 2) DEFAULT 0,"accepted" varchar ,"cordno" varchar ,"terms" numeric DEFAULT 0,"odate" date ) WITH OIDS;
SELECT setval('rnons_invoices_invid_seq',1);
CREATE TABLE varrec ("id" serial NOT NULL PRIMARY KEY ,"inv" int4 DEFAULT 0,"date" date ,"amount" numeric(16, 2) DEFAULT 0) WITH OIDS;
SELECT setval('varrec_id_seq',1);
CREATE TABLE quotes ("quoid" serial NOT NULL PRIMARY KEY ,"deptid" numeric DEFAULT 0,"cusnum" numeric DEFAULT 0,"cordno" varchar ,"ordno" varchar ,"chrgvat" varchar ,"terms" numeric DEFAULT 0,"salespn" varchar ,"odate" date ,"accepted" varchar ,"comm" varchar ,"done" varchar ,"username" varchar ,"deptname" varchar ,"cusacc" varchar ,"cusname" varchar ,"surname" varchar ,"cusaddr" varchar ,"cusordno" varchar ,"cusvatno" varchar ,"prd" numeric DEFAULT 0,"div" numeric DEFAULT 0,"disc" numeric(16, 2) DEFAULT 0,"discp" numeric(16, 2) DEFAULT 0,"delchrg" numeric(16, 2) DEFAULT 0,"subtot" numeric(16, 2) DEFAULT 0,"traddisc" numeric(16, 2) DEFAULT 0,"balance" numeric(16, 2) DEFAULT 0,"vat" numeric(16, 2) DEFAULT 0,"total" numeric(16, 2) DEFAULT 0,"jobid" numeric DEFAULT 0,"jobnum" varchar ,"location" varchar ,"fcid" numeric DEFAULT 0,"currency" varchar ,"xrate" numeric(16, 2) DEFAULT 0,"fbalance" numeric(16, 2) DEFAULT 0,"fsubtot" numeric(16, 2) DEFAULT 0,"discount" numeric(16, 2) DEFAULT 0,"delivery" numeric(16, 2) DEFAULT 0,"delvat" int4 DEFAULT 0,"lead" varchar ,"ncdate" date DEFAULT now()) WITH OIDS;
SELECT setval('quotes_quoid_seq',1);
CREATE TABLE picking_slip_setting ("id" serial NOT NULL PRIMARY KEY ,"set" varchar ) WITH OIDS;
SELECT setval('picking_slip_setting_id_seq',1);
INSERT INTO picking_slip_setting ("id","set") VALUES('1','n');
CREATE TABLE grievance_items ("itemnum" serial NOT NULL PRIMARY KEY ,"input" varchar ,"grievnum" varchar ,"div" varchar ,"date_added" date ) WITH OIDS;
SELECT setval('grievance_items_itemnum_seq',1);
CREATE TABLE active_bursaries ("id" serial NOT NULL PRIMARY KEY ,"bursary" numeric DEFAULT 0,"rec_name" varchar ,"rec_add1" varchar ,"rec_add2" varchar ,"rec_add3" varchar ,"rec_add4" varchar ,"rec_idnum" varchar ,"rec_telephone" varchar ,"from_date" date ,"to_date" date ,"notes" varchar ) WITH OIDS;
SELECT setval('active_bursaries_id_seq',1);
CREATE TABLE ncsrec ("oldnum" varchar ,"newnum" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
CREATE TABLE callout_docs_items ("calloutid" numeric DEFAULT 0,"whid" numeric DEFAULT 0,"stkid" numeric DEFAULT 0,"div" numeric DEFAULT 0,"amt" numeric(16, 2) DEFAULT 0,"discp" numeric(16, 2) DEFAULT 0,"disc" numeric(16, 2) DEFAULT 0,"funitcost" numeric(16, 2) DEFAULT 0,"famt" numeric(16, 2) DEFAULT 0,"description" varchar ,"account" int4 DEFAULT 0,"unitcost" varchar ,"qty" varchar ) WITH OIDS;
CREATE TABLE statement_irefs ("id" serial NOT NULL PRIMARY KEY ,"amount" varchar ,"des1" varchar ,"des2" varchar ,"pn" varchar ,"action" varchar ,"account" int4 DEFAULT 0,"by" varchar ) WITH OIDS;
SELECT setval('statement_irefs_id_seq',1);
CREATE TABLE cons ("id" serial NOT NULL PRIMARY KEY ,"name" varchar ,"surname" varchar ,"comp" varchar ,"tell" varchar ,"cell" varchar ,"fax" varchar ,"email" varchar ,"padd" varchar ,"hadd" varchar ,"ref" varchar ,"date" date ,"by" varchar ,"con" varchar ,"div" numeric DEFAULT 0,"supp_id" int4 DEFAULT 0,"cust_id" int4 DEFAULT 0,"accountname" varchar ,"account_id" numeric DEFAULT 0,"account_type" varchar ,"lead_source" varchar ,"title" varchar ,"department" varchar ,"birthdate" date ,"reports_to" varchar ,"reports_to_id" numeric DEFAULT 0,"assigned_to" varchar ,"assigned_to_id" numeric DEFAULT 0,"tell_office" varchar ,"tell_other" varchar ,"email_other" varchar ,"assistant" varchar ,"assistant_phone" varchar ,"padd_city" varchar ,"padd_state" varchar ,"padd_code" varchar ,"padd_country" varchar ,"hadd_city" varchar ,"hadd_state" varchar ,"hadd_code" varchar ,"hadd_country" varchar ,"description" varchar ,"del_addr" varchar ,"team_id" numeric DEFAULT 0) WITH OIDS;
SELECT setval('cons_id_seq',1);
CREATE TABLE deptscripts ("dept" numeric DEFAULT 0,"script" varchar ,"div" numeric DEFAULT 0,"scriptname" varchar ) WITH OIDS;
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','payslip-view.php','0','VIEW PAYSLIP');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','index_cons.php','0','CONTACTS INDEX');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','mod_con.php','0','EDIT CONTACT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','find_con.php','0','FIND CONTACT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','req_gen.php','0','LEAVE MESSAGES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','index_reqs.php','0','MESSAGES INDEX');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','new_con.php','0','NEW CONTACT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','rem_con.php','0','REMOVE CONTACT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','view_con.php','0','VIEW CONTACT DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','list_cons.php','0','VIEW CONTACTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','view_req.php','0','VIEW MESSAGES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('4','yr-prd-trans-new.php','0','ADD PREVIOUS YEAR TRANSACTION');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('4','trail-bal.php','0','GENERATE PREVIOUS YEAR TRAIL BALANCE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('4','trans-view.php','0','VIEW PREVIOUS YEAR TRANSACTIONS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','allowance-add.php','0','ADD ALLOWANCE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','admin-employee-add.php','0','ADD EMPLOYEE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','employee-reports-add.php','0','ADD EMPLOYEE REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','report-type-add.php','0','ADD EMPLOYEE REPORT TYPE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','paye-add.php','0','ADD PAYE BRACKETS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','salded-add.php','0','ADD SALARY DEDUCTION');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','employee-leave-approve.php','0','APPROVE LEAVE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','admin-employee-edit.php','0','EDIT EMPLOYEE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','loan-edit.php','0','EDIT EMPLOYEE LOANS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','admin-paye-edit.php','0','EDIT PAYE BRACKETS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','allowance-edit.php','0','EDIT ALLOWANCE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','salded-edit.php','0','EDIT SALARY DEDUCTIONS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','employee-resources.php','0','EMPLOYEE RESOURCES INDEX');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','loan-add.php','0','GRANT EMPLOYEE LOAN');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','salaries-staff.php','0','PROCESS SALARY');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','admin-employee-rem.php','0','REMOVE EMPLOYEE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','loan-rem.php','0','REMOVE EMPLOYEE LOANS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','admin-paye-rem.php','0','REMOVE PAYE BRACKETS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','allowance-rem.php','0','REMOVE ALLOWANCE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','salded-rem.php','0','REMOVE SALARY DEDUCTIONS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','index-salaries.php','0','SALARIES INDEX');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','allowances-view.php','0','VIEW ALLOWANCES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','payslips.php','0','VIEW ALL PROCESSED SALARIES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','paye-view.php','0','VIEW/EDIT PAYE BRACKETS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','admin-employee-detail.php','0','VIEW EMPLOYEE DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','loans-view.php','0','VIEW EMPLOYEE LOANS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','employee-reports-view.php','0','VIEW EMPLOYEE REPORTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','admin-employee-view.php','0','VIEW EMPLOYEES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','payslip.php','0','VIEW PROCESSED SALARIES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','salded-view.php','0','VIEW SALARY DEDUCTIONS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','purch-int-cancel.php','0','CANCEL INTERNATIONAL PURCHASE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','purch-cancel.php','0','CANCEL PURCHASE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','purchase-edit.php','0','EDIT PURCHASE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','supp-edit.php','0','EDIT SUPPLIER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','purch-int-new.php','0','NEW INTERNATIONAL PURCHASE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','purchase-new.php','0','NEW PURCHASE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','index-pchs.php','0','PURCHASES INDEX');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','pchs-reports.php','0','PURCHASES REPORTS INDEX');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','purch-int-recv.php','0','RECEIVE INTERNATIONAL PURCHASE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','purch-recv.php','0','RECIEVE PURCHASE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','supp-trans.php','0','SUPPLIER/ACCOUNT JOURNAL ENTRY');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','supp-stmnt.php','0','SUPPLIER STATEMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','purch-int-det.php','0','VIEW INTERNATIONAL PURCHASE DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','purch-int-view.php','0','VIEW INTERNATIONAL PURCHASES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','purch-det.php','0','VIEW PURCHASE DETIALS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','purchase-view.php','0','VIEW PURCHASES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','purch-int-view-prd.php','0','VIEW RECEIVED INTERNATIONAL PURCHASES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','purch-int-det-prd.php','0','VIEW RECEIVED INTERNATIONAL PURCHASES DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','purchase-view-prd.php','0','VIEW RECEIVED PURCHASES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','purch-det-prd.php','0','VIEW RECEIVED PURHCASES DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','supp-det.php','0','VIEW SUPPLIER DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','nons-purch-batch-add.php','0','ADD BATCH CREDITOR NON STOCK INVOICES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','stock-add.php','0','ADD STOCK');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','stock-avail.php','0','AVAILABLE STOCK REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','stock-block.php','0','BLOCK STOCK');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','stock-edit.php','0','EDIT STOCK');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','stock-search.php','0','FIND STOCK');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','stock-rem.php','0','REMOVE STOCK');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','stock-alloc.php','0','SHOW STOCK ALLOCATION');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','stock-det.php','0','STOCK DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','index-stock.php','0','STOCK INDEX');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','stock-lvl-rep.php','0','STOCK LEVES REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','stock-amt-det.php','0','STOCK REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','stock-report.php','0','STOCK REPORTS INDEX');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','stock-taking.php','0','STOCK TAKING');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','stock-transfer.php','0','STOCK TRANSFER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','stock-unblock.php','0','UNBLOCK STOCK');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','cost-price-view.php','0','VIEW COST PRICE OF ITEMS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','stock-view.php','0','VIEW STOCK');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','index-accounts.php','0','ACCOUNTING INDEX');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','index-reports.php','0','ACCOUNTING REPORTS INDEX');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','trans-new.php','0','ADD JOURNAL ENTRY');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','multi-trans.php','0','ADD MULTIPLE JOURNAL ENTRIES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','trans-batch.php','0','ADD MULTIPLE TRANSACTIONS TO BATCH');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','ledger-new.php','0','ADD NEW HIGH SPEED INPUT LEDGER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','trans-new-sep.php','0','ADD TRANSACTION (ONE DT/CT, MULTIPLE CT/DT) ');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','trans-batch-new.php','0','ADD TRANSACTION TO BATCH');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','bank-recon.php','0','BANK RECONCILIATION');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','banked.php','0','CASH BOOK ANALYSIS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','set-bal-sheet.php','0','CONFIGURE BALANCE SHEET');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','cred-age-analysis.php','0','CREDITORS AGE ANALYSIS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','debt-age-analysis.php','0','DEBTORS AGE ANALYSIS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','acc-new-dec.php','0','DEFAULT ACCOUNT CREATION');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','batch-edit.php','0','EDIT BATCH TRANSACTION');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','ledger-edit.php','0','EDIT HIGH SPEED INPUT LEDGER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','bal-sheet.php','0','GENERATE BALANCE SHEET');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','trial_bal.php','0','GENERATE TRAIL BALANCE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','alltrans.php','0','JOURNAL ENTRIES REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','alltrans-prd.php','0','JOURNAL ENTRIES REPORT BY PERIOD RANGE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','acc-trans-prd.php','0','JOURNAL ENTRIES REPORT PER ACCOUNT BY PERIOD RANGE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','cat-trans.php','0','JOURNAL ENTRIES REPORT PER CATEGORY');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','accsub-trans.php','0','JOURNAL ENTRIES REPORT PER MAIN ACCOUNT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','allcat.php','0','LIST ALL CATEGORIES AND ACCOUNTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','not-banked.php','0','LIST OUTSTANDING PAYMENTS/RECEIPTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','bank-recon-print.php','0','PRINT SAVED BANK RECONS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','batch-procs.php','0','PROCESS/REMOVE BATCH TRANSACTIONS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','accat-rem.php','0','REMOVE ACCOUNT CATEGORY');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','batch-rem.php','0','REMOVE BATCH TRANSACTION');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','ledger-rem.php','0','REMOVE HIGH SPEED INPUT LEDGER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','ledger-run.php','0','RUN HIGH SPEED INPUT LEDGER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','vat-report.php','0','VAT REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','batch-view.php','0','VIEW BATCH ENTRIES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','period-view.php','0','VIEW CURRENT PERIOD');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','ledger-det.php','0','VIEW HIGH SPEED INPUT LEDGER DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','ledger-view.php','0','VIEW HIGH SPEED INPUT LEDGERS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','bal-sheet-view.php','0','VIEW SAVED BALANCE SHEETS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','bank-recon-saved.php','0','VIEW SAVED BANK RECONS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','income-stmnt-view.php','0','VIEW SAVED INCOME STATEMENTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','trial_bal-view.php','0','VIEW SAVED TRAIL BALANCES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','quote-accept.php','0','ACCEPT QUOTE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','sorder-accept.php','0','ACCEPT SALES ORDER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','pos.php','0','ALLOCATE STOCK BARCODE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','invoice-unf-cancel.php','0','CANCEL INCOMPLETE INVOICE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','quote-cancel.php','0','CANCEL QUOTE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','invoice-print.php','0','COMPLETE/PRINT INVOICE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','invoice-note.php','0','CREDIT NOTE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','cust-trans.php','0','CUSTOMER/ACCOUNT JOURNAL ENTRY');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','invoice-disc-rep.php','0','CUSTOMER DISCOUNT/DELIVERY REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','cust-edit.php','0','EDIT CUSTOMER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','pos-invoice-edit.php','0','EDIT POINT OF SALES CASH INVOICE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','pos-invoice-list.php','0','LIST POINT OF SALES CASH INVOICE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','cust-credit-stockinv.php','0','NEW INVOICE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','pos-invoice-new.php','0','NEW POINT OF SALES CASH INVOICE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','quote-new.php','0','NEW QUOTE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','pos-invoice-print.php','0','PRINT POINT OF SALES CASH INVOICE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','cust-stmnt.php','0','PRODUCE CUSTOMER STATEMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','invoice-reprint.php','0','REPRINT A PRINTED INVOICE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','index-sales.php','0','SALES INDEX');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','coms-report.php','0','SALES REP COMMISION REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','sales-reports.php','0','SALES REPORTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','stock-sales-rep.php','0','STOCK SALES REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','invoice-canc-view.php','0','VIEW CANCELED INVOICES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','quote-canc-view.php','0','VIEW CANCELED QUOTES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','cust-det.php','0','VIEW CUSTOMER DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','invoice-unf-view.php','0','VIEW INCOMPLETE INVOICES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','quote-unf-view.php','0','VIEW INCOMPLETE QUOTES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','invoice-details.php','0','VIEW INVOICE DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','invoice-view.php','0','VIEW INVOICES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','invoice-view-prd.php','0','VIEW PAID INVOICES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','invoice-details-prd.php','0','VIEW PAID INVOICES DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','pos-invoice-details.php','0','VIEW POINT OF SALE CASH INVOICE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','quote-details.php','0','VIEW QUOTE DETIALS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','quote-view.php','0','VIEW QUOTES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','sorder-canc-view.php','0','VIEW CANCELLED SALES ORDERS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','sorder-cancel.php','0','CANCEL SALES ORDER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','sorder-details.php','0','SALES ORDER DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','sorder-new.php','0','ADD NEW SALES ORDER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','sorder-unf-cancel.php','0','CANCEL INCOMPLETE SALES ORDER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','sorder-unf-view.php','0','VIEW INCOMPLETE SALES ORDER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','sorder-view.php','0','VIEW SALES ODERS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','acc-new2.php','0','ADD NEW ACCOUNT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','prd-close.php','0','CLOSE PERIOD');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','pricelist-copy.php','0','COPY PRICE LIST');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','acc-edit.php','0','EDIT ACCOUNT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','accat-edit.php','0','EDIT ACCOUNT CATEGORY');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','class-edit.php','0','EDIT CLIENT CLASS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','cat-edit.php','0','EDIT CUSTOMER CATEGORY');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','dept-edit.php','0','EDIT CUSTOMER/SUPPLIER DEPARTMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','pricelist-edit.php','0','EDIT PRICE LIST');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','salesp-edit.php','0','EDIT SALES REP');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','stockclass-edit.php','0','EDIT STOCK CLASSIFICATIONS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','whouse-edit.php','0','EDIT STORE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','whouse-selamt-inc.php','0','INCREASE ALL SELLING PRICES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','acc-trans.php','0','JOURNAL ENTRIES REPORT PER ACCOUNT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','acc-mov.php','0','MOVE ACCOUNT BETWEEN CATEGORIES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','yr-open.php','0','OPEN FINANCIAL YEAR');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','acc-rem.php','0','REMOVE ACCOUNT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','class-rem.php','0','REMOVE CLIENT CLASS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','cat-rem.php','0','REMOVE CUSTOMER CATEGORY');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','dept-rem.php','0','REMOVE CUSTOMER/SUPPLIER DEPARTMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','pricelist-rem.php','0','REMOVE PRICE LIST');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','salesp-rem.php','0','REMOVE SALES REP');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','stockclass-rem.php','0','REMOVE STOCK CLASSIFICATIONS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','whouse-rem.php','0','REMOVE STORES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','sal-settings.php','0','SALARY SETTINGS INDEX');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','sales-settings.php','0','SALES SETTINGS INDEX');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','stock-settings.php','0','STOCK SETTINGS INDEX');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','accat-view.php','0','VIEW ACCOUNT CATEGORIES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','pricelist-det.php','0','VIEW PRICE LIST DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','stockcat-rem.php','0','REMOVE STOCK CATEGORY');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','stockcat-det.php','0','VIEW STOCK CATEGORY DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','stockcat-edit.php','0','EDIT STOCK CATEGORIES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','index-assets.php','0','ASSET LEDGER INDEX');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','asset-new.php','0','ADD ASSET');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','asset-view.php','0','VIEW ASSETS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','asset-rem.php','0','REMOVE ASSETS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','bal-sheet-print.php','0','PRINT SAVED BALANCE SHEET');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','income-stmnt-print.php','0','PRINT SAVED INCOME STATEMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','trial_bal-print.php','0','PRINT SAVED TRIAL BALANCE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','quote-unf-cancel.php','0','CANCEL UNFINISHED QUOTE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','set-bal-sheet-edit.php','0','EDIT BALANCE SHEET CONFIGURATION');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','acc-trans-prd-xls.php','0','ACCOUNT TRANSACTIONS EXECL EXPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','acc-trans-xls.php','0','SAME YEAR ACCOUNT TRANSACTIONS SPREADSHEET EXPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','accsub-trans-xls.php','0','MAIN ACCOUNTS TRANSACTIONS SPREADSHEET EXPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','allcat-xls.php','0','ALL ACCOUNTS AND CATEGORIES SPREADSHEET EXPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','alltrans-prd-xls.php','0','ALL TRANSACTIONS PER PERIOD RANGE SPREADSHEET EXPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','alltrans-xls.php','0','ALL JOURNAL ENTRIES SPREADSHEET EXPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','bal-sheet-xls.php','0','BALANCE SHEET SPREADSHEET EXPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','bank-recon-xls.php','0','BANK RECONS SPREADSHEET EXPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','banked-xls.php','0','CASHBOOK ANALYSIS SPREADSHEET EXPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','cat-trans-xls.php','0','CATEGORY TRANSACTIONS SPREADSHEET EXPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','cred-age-analysis-xls.php','0','CREDITORS ANALYSIS SPREADSHEET EXPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','debt-age-analysis-xls.php','0','DEBTORS ANALYSIS SPREADSHEET EXPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','income-stmnt-xls.php','0','INCOME STATEMENT SPREADSHEET EXPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','not-banked-xls.php','0','OUTSTANDING ENTRIES SPREADSHEET EXPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','trial-bal-xls.php','0','TRIAL BALANCE SPREADSHEET EXPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','vat-report-xls.php','0','VAT REPORT SPREADSHEET EXPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','bal-sheet-pdf.php','0','BALANCE SHEET PDF EXPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','cust-pdf-stmnt.php','0','CUSTOMER STATEMENT PDF EXPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','income-stmnt-pdf.php','0','INCOME STATEMENT PDF EXPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','invoice-pdf-reprint.php','0','INVOICE REPRINT PDF EXPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','trial-bal-pdf.php','0','TRIAL BALANCE PDF EXPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','purch-return.php','0','PURCHASE RETURN');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','stock-balance.php','0','STOCK TRANSACTION');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','index-maint.php','0','MAINTENANCE INDEX');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','maint.php','0','DO MAINTENANCE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','backup-make.php','0','MAKE BACKUP');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','cust-stmnt-date.php','0','CUSTOMER STATEMENT BY DATE RANGE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','employee-leave-apply.php','0','APPLY FOR LEAVE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','employee-leave-view.php','0','VIEW LEAVE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','employee-leave-cancel.php','0','CANCEL LEAVE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','nons-purchase-new.php','0','ADD NON STOCK PURCHASE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','nons-purchase-view.php','0','VIEW NON STOCK PURCHASES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','nons-purch-det.php','0','VIEW NON STOCK PURCHASE DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','nons-purchase-view-prd.php','0','VIEW RECIEVED NON STOCK PURCHASE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','nons-purch-det-prd.php','0','VIEW RECIEVED NON STOCK PURCHASE DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','backup-download.php','0','DOWNLOAD BACKUP');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','employee-leave-avail.php','0','VIEW AVAILABLE LEAVE FOR EMPLOYEES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','employee-onleave.php','0','VIEW EMPLOYEES ON LEAVE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','invoice-edit.php','0','EDIT INVOICE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','pos-invoice-view-prd.php','0','VIEW PRINTED POINT OF SALE INVOICES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','pos-invoice-details-prd.php','0','VIEW PRINTED POINT OF SALE INVOICE DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','pos-invoice-reprint-prd.php','0','REPRINT POINT OF SALE INVOICE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','find-num.php','0','VIEW TEMP/INVOICE NUMBER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','corder-new.php','0','ADD CONSIGNMENT ORDER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','corder-view.php','0','VIEW CONSIGNMENT ORDERS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','corder-unf-view.php','0','VIEW INCOMPLETE CONSIGNMENT ORDERS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','corder-canc-view.php','0','VIEW CANCELED CONSIGNMENT ORDERS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','corder-details.php','0','VIEW CONSIGNMENT ORDER DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','corder-cancel.php','0','CANCEL CONSIGNMENT ORDER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','corder-accept.php','0','ACCEPT CONSIGNMENT ORDER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','corder-print.php','0','PRINT CONSIGNMENT ORDER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','sorder-print.php','0','PRINT SALES ORDER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','purch-canc-view.php','0','VIEW CANCELLED PURCHASES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('4','income-stmnt.php','0','GENERATE PREVIOUS YEAR INCOME STATEMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','bankacct-new.php','0','ADD BANK ACCOUNT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','bank-pay-add.php','0','ADD BANK PAYMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','bank-pay-supp.php','0','ADD BANK PAYMENT FOR SUPPLIERS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','bank-recpt-add.php','0','ADD BANK RECEIPT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','bank-recpt-inv.php','0','ADD BANK RECEIPT FOR CUSTOMER INVOICES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','bank-stmnt.php','0','ADD BANK TRANSACTIONS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','index-bankaccnt.php','0','BANKING INDEX');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','cheq-cancel.php','0','CANCEL CHEQUE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','index-cashbook.php','0','CASHBOOK INDEX');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','bankacct-edit.php','0','EDIT BANK ACCOUNT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','bankacct-rem.php','0','REMOVE BANK ACCOUNT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','bankacct-view.php','0','VIEW BANK ACCOUNTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','cashbook-view.php','0','VIEW CASH BOOK');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','cash-link.php','0','SET PETTY CASH LINK');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','petty-req-add.php','0','ADD PETTY CASH REQUISITION');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','petty-req-app.php','0','APPROVE PETTY CASH REQUISITION');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','petty-req-can.php','0','CANCEL PETTY CASH REQUISITION');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','petty-req-recpt.php','0','RECORD PETTY CASH RECEIPT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','petty-trans.php','0','TRANSFER FUNDS TO PETTY CASH');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','pettycash-rep.php','0','PETTY CASH REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','pettycashbook-view.php','0','VIEW PETTY CASH BOOK');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','index-pettycashbook.php','0','PETTY CASH BOOK INDEX');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('3','cust-rem.php','0','REMOVE CUSTOMER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('3','customers-new.php','0','ADD CUSTOMER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('1','supp-new.php','0','ADD SUPPLIER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('1','supp-rem.php','0','REMOVE SUPPLIER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('3','index-debtors.php','0','DEBTORS INDEX');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('1','index-creditors.php','0','CREDITORS INDEX');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('1','supp-view.php','0','VIEW SUPPLIERS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('3','customers-view.php','0','VIEW CUSTOMERS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','index-sales-posinvoices.php','0','SALES ORDER POSINVOICES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','index-sales-quotes.php','0','SALES ORDER QUOTES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','index-sales-consignment.php','0','SALES CONSIGNMENT INDEX');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','index-sales-orders.php','0','SALES ORDER INDEX');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','multi-bank-pay-add.php','0','MULTIPLE BANK PAYMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','multi-bank-recpt-add.php','0','MULTIPLE BANK RECEIPT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','nons-invoice-new.php','0','ADD NON STOCK INVOICE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','nons-invoice-view.php','0','VIEW NON STOCK INVOICES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','invoice-note-view-prd.php','0','VIEW CREDIT NOTE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','pos-quote-new.php','0','ADD POS QUOTE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','pos-quote-view.php','0','VIEW POS QUOTES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','pos-quote-canc-view.php','0','VIEW CANCELLED POS QUOTES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','pos-quote-unf-view.php','0','VIEW INCOMPLETE POS QUOTES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','month-end.php','0','MONTH END');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','stock-transfer-bran.php','0','STOCK TRANSFER - BRANCH ');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','stock-transit-view.php','0','VIEW STOCK IN TRANSIT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('3','calc-int.php','0','CALCULATE INTEREST');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','pos-quote-details.php','0','POS QUOTE DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','intbrac-edit.php','0','EDIT INTEREST BRACKETS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','intbrac-rem.php','0','REMOVE INTEREST BRACKETS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','stock-transit-can.php','0','CANCELL STOCK TRANSFER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','stock-transit-del.php','0','DELETE STOCK FROM TRANSIT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','nons-invoice-det.php','0','VIEW NON STOCK INVOICE DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','nons-invoice-print.php','0','PRINT NON STOCK INVOICE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','pos-quote-cancel.php','0','CANCELL POS QUOTE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','corder-unf-cancel.php','0','CANCEL INCOMPLETE CONSIGNMENT ORDER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','nons-purch-cancel.php','0','CANCEL NON STOCK PURCHASE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','nons-purch-recv.php','0','RECIEVE NON STOCK PURCHASE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','supp-tran-rep.php','0','SUPPLIER TRANSACTION REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('3','cust-list.php','0','PRINT CUSTOMER LIST');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('1','supp-list.php','0','PRINT SUPPLIER LIST');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','ledger-prd.php','0','PERIOD RANGE GENERAL LEDGER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('1','supp-block.php','0','BLOCK SUPPLIER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('1','supp-unblock.php','0','UNBLOCK SUPPLIER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','alltrans-refnum.php','0','DETAILED GENERAL LEDGER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','petty-bank.php','0','BANK PETTY CASH');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','petty-recpt-supp.php','0','RECEIVE PETTY CASH FROM SUPPLIER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','petty-recpt-cust.php','0','RECEIVE PETTY CASH FROM CUSTOMER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','petty-pay-supp.php','0','PAY PETTY CASH TO SUPPLIER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','petty-pay-cust.php','0','PAY PETTY CASH TO CUSTOMER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','acctab-new.php','0','ADDING NEW ACCES SCRIPTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','asset-dep.php','0','ASSET DEPRECIATION');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','asset-rep.php','0','ASSET REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','assetgrp-edit.php','0','EDIT ASSET GROUP');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','assetgrp-new.php','0','ADD ASSET GROUPS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','assetgrp-rem.php','0','REMOVE ASSET GROUP');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','assetgrp-view.php','0','VIEW ASSET GROUPS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','bank-recpt-supp.php','0','ADD BANK RECPT FROM SUPPLIERS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','cust-ledger.php','0','DEBTORS LEDGER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('3','cust-pdf-stmnt-all.php','0','PRINT CUSTOMER STATEMENTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('3','invoice-pdf-cust.php','0','PRINT CUSTOMER INVOICES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','invoice-unitcost-edit.php','0','EDIT UNIT COST ON INVOICES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','ledger-ytd.php','0','YEAR REVIEW');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','ledger.php','0','GENERAL LEDGER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','petty-req-edit.php','0','EDIT PETTY REQUISITION');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','quote-pdf-print.php','0','PRINT QUOTES IN PDF');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','quote-print.php','0','PRINT QUOTES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','rectrans-edit.php','0','EDIT RECURING TRANSACTION');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','rectrans-new.php','0','ADD RECURING TRANSACTION');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','rectrans-run.php','0','PROCCESS RECURING TRANSACTIONS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','rectrans-view.php','0','VIEW RECURING TRANSACTIONS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','stock-ledger.php','0','INVENTORY LEDGER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','supp-ledger.php','0','CREDITORS LEDGER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','trans-amt.php','0','TRANSACTION BY REFNO');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','company-new.php','0','NEW COMPANY');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','company-view.php','0','VIEW COMPANY');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','admin-branadd.php','0','ADD BRANCH');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','admin-branview.php','0','VIEW BRANCH');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','company-export.php','0','EXPORT COMPANY');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','company-import.php','0','IMPORT COMPANY');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','doc-add.php','0','ADD DOCUMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','doc-dload.php','0','DOWNLOAD DOCUMENTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','doc-edit.php','0','EDIT DOCUMENTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','doc-rem.php','0','REMOVE DOCUMENTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','doc-view.php','0','VIEW DOCUMENTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','doctype-add.php','0','ADD DOCUMENT TYPES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','doctype-rem.php','0','REMOVE DOCUMENT TYPES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','doctype-view.php','0','VIEW DOCUMENT TYPES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('4','index-multi-reports.php','0','MULTI COMPANY/BRANCH REPORTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','nons-quote-acc.php','0','ACCEPT NON STOCK QUOTES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','nons-quote-det.php','0','NON STOCK QUOTE DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','nons-quote-new.php','0','NEW NON STOCK QUOTE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','nons-quote-print.php','0','PRINT NON STOCK QUOTE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','nons-quote-view.php','0','VIEW NON STOCK QUOTES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','nons-invoice-note.php','0','NON STOCK CREDIT NOTE ');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','nons-invoice-note-view.php','0','VIEW NONS STOCK CREDIT NOTES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','nons-invoice-note-det.php','0','NON STOCK CREDIT NOTE DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','nons-invoice-note-reprint.php','0','REPRINT NON STOCK CREDIT NOTES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','invoice-search.php','0','FIND INVOICES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','admin-lemployee-view.php','0','VIEW LEFT EMPLOYEES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','admin-usrpasswd.php','0','ALLOW USER TO CHANGE OWN PASSWORD');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','company-terms.php','0','VIEW EDIT COMPANY TERMS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','costcenter-add.php','0','ADD COST CENTER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','costcenter-edit.php','0','EDIT COST CENTER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','costcenter-rep-det.php','0','VIEW DETAILED COST CENTER REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','costcenter-rep.php','0','COST CENTER REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','costcenter-tran.php','0','ADD COST CENTER MANUAL TRANSACTION');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','costcenter-view.php','0','VIEW COST CENTERS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','employee-view-image.php','0','VIEW EMPLOYEE IMAGE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','lnons-purch-det.php','0','LINKED PURCHASE DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','lnons-purch-new.php','0','NEW LINKED PURCHASE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','lnons-purch-recv.php','0','RECV LINKED PURCHASE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','nonsa-purchase-new.php','0','NEW ASSET PURCHASE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','nonsa-purch-det.php','0','ASSET PURCHASE DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','nonsa-purch-recv.php','0','RECEIVE ASSET PURCHASE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','pos-report-user.php','0','POS USER REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','pos-slip.php','0','PRINT POS INVOICE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','payslip-print.php','0','PRINT SALARY PAYSLIP');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','purch-apprv.php','0','APPROVE PURCHASE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','purch-complete.php','0','COMPLETE PARLTY RECEIVED PURCHASE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','purch-recv-purnum.php','0','RECEIVE PURCHASE BY NUMBER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','rec-invoice-details.php','0','RECURRING INVOICE DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','rec-invoice-new.php','0','NEW RECURRING INVOICE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','rec-invoice-rem.php','0','REMOVE RECURRING INVOICE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','rec-invoice-rem.php','0','REMOVE RECURRING INVOICE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','rec-invoice-run.php','0',' INVOICE FROM RECURRING INVOICE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','rec-invoice-view.php','0','VIEW RECURRING INVOICES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','stock-serials.php','0','ALLOCATE SERIALS TO STOCK');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','invoice-limit-override.php','0','ALLOW INVOICE TO LIMITED CUSTOMERS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','creditcard-new.php','0','ADD CREDIT CARD');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','petrolcard-new.php','0','ADD PETROL CARD');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','petty-req-multi-add.php','0','ADD MULTIPLE PETTY CASH REQUISITIONS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','creditcard-edit.php','0','EDIT CREDIT CARD');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','petrolcard-edit.php','0','EDIT PETROL CARD');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','purch-recinvcd.php','0','RECORD INVOICE FROM SUPPLIER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('1','supp-stmnt-date.php','0','SUPPLIER STATEMENT BY DATE RANGE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','purch-recnote.php','0','RECORD SUPPLIER CREDIT NOTE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','invoice-note-reprint.php','0','REPRINT CREDIT NOTE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','stock-move-rep.php','0','STOCK MOVEMENT REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','pos-invoice-note.php','0','PRODUCE POS CREDIT NOTE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','pos-note-slip.php','0','PRINT POS CREDIT NOTE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','purch-int-recinvcd.php','0','RECORD INTERNATIONAL INVOICE FROM SUPPLIER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','purch-int-return.php','0','RETURN INTERNATIONAL PURCHASE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','intinvoice-new.php','0','NEW INTERNATIONAL INVOICE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','intinvoice-print.php','0','PRINT INTERNATIONAL INVOICE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','intinvoice-note.php','0','PRODUCE INTERNATIONAL INVOICE CREDIT NOTE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','intinvoice-note-reprint.php','0','REPRINT INTERNATIONAL INVOICE CREDIT NOTE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','intsorder-new.php','0','PRODUCE INTERNATIONAL SALES ORDER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','intsorder-accept.php','0','ACCEPT INTERNATIONAL SALES ORDER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','admin-lemployee-detail.php','0','VIEW PAST EMPLOYEE DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','cheq-return.php','0','RETURN PAYMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','stock-serno-find.php','0','FIND SERIAL NUMBER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','intinvoice-details.php','0','INTERNATIONAL INVOICE DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','intinvoice-pdf-reprint.php','0','PRINT INTERNATIONAL INVOICE PDF');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','intinvoice-reprint.php','0','REPRINT INTERNATIONAL INVOICE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','nons-intinvoice-reprint.php','0','REPRINT INTERNATIONAL NON STOCK INVOICE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','nons-invoice-reprint.php','0','REPRINT NON STOCK INVOICE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','nons-intinvoice-pdf-reprint.php','0','PRINT NON STOCK INTERNATIONAL INVOICE PDF');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','nons-sales-rep.php','0','NON STOCK SALES REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','sn-sales-rep.php','0','TOTAL SALES REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','pos-quote-print.php','0','PRINT POS QUOTE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','pos-quote-accept.php','0','ACCEPT POS QUOTE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','multi-not-banked.php','0','LIST OUTSTANDING PAYMENTS/RECEIPTS - ALL BRANCHES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','multi-trial-bal.php','0','PRODUCE TRIAL BALANCE - ALL BRANCHES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','multi-banked.php','0','VIEW CASH BOOK - ALL BRANCHES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','multi-vat-report.php','0','VAT REPORT - ALL BRANCHES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','multi-income-stmnt.php','0','INCOME STATEMENT - ALL BRANCHES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','config-bal-sheet.php','0','CONFIGURE BALANCE SHEET - ALL BRANCHES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','multi-bal-sheet.php','0','BALANCE SHEET - ALL BRANCHES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','multi-debt-age-analysis.php','0','DEBTORS AGE ANALYSIS - ALL BRANCHES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','multi-cred-age-analysis.php','0','CREDITORS AGE ANALYSIS - ALL BRANCHES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','multi-alltrans.php','0','ALL JOURNAL ENTRIES - ALL BRANCHES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','multi-alltrans-prd.php','0','ALL JOURNAL ENTRIES(PERIOD RANGE) - ALL BRANCHES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','budget-new.php','0','ADD BUDGET');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','budget-view.php','0','VIEW BUDGETS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','budget-details.php','0','BUDGET DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','budget-report.php','0','BUDGET REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','budget-rem.php','0','REMOVE BUDGET');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','budget-edit.php','0','EDIT BUDGET');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','tokens-list-unall.php','0','List Unallocated Queries');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','tokens-new.php','0','Add Query');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','tokens-manage.php','0','Manage Queries');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','index-sms.php','0','SMS Index');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','team-add.php','0','Add Team');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','team-list.php','0','View Teams');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','team-links.php','0','Select Team Links');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','team-edit.php','0','Edit Team');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','team-rem.php','0','Remove Team');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','tcat-add.php','0','Add Query Category');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','tcat-list.php','0','View Query Categories');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','tcat-edit.php','0','Edit Query Categories');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','tcat-rem.php','0','Remove Query Category');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','crms-allocate.php','0','Set default user teams');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','tokens-allocate.php','0','Allocate Unallocated queries to users');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','email-send.php','0','Send CRM Email');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','message-send.php','0','Send Message to other user');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','reports-tokens-closed.php','0','Search closed queries');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','reports-tokens-stats.php','0','Outstanding query Statistics');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','sms-send.php','0','Send CRM SMS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','tokens-action-other.php','0','Record other Action taken');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','tokens-closed-details.php','0','View Closed query Details');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','tokens-close.php','0','Close Query');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','tokens-forward.php','0','Forward Query to future date');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','tokens-list-open.php','0','List All Open Queries');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','tokens-pass.php','0','Pass Query to other User');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','action-add.php','0','Add Action');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','action-list.php','0','List Actions');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','action-rem.php','0','Remove Action');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','crms-list.php','0','List Users to select teams for');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','crms-teams.php','0','Select Multiple teams for a user');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','tokens-action-archive.php','0','Archive query actions');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','tokens-action-archive-view.php','0','View Archived query actions');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','bank-trans.php','0','BANK TRANSFER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','bank-trans-int.php','0','TRANSFER FUNDS TO/FROM FOREIGN ACCOUNTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','sup-pricelist-add','0','ADD SUPLLIER PRICELIST');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','sup-pricelist-edit.php','0','EDIT SUPPLIER PRICELIST');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','sup-pricelist-copy.php','0','COPY SUPPLIER PRICELIST');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','sup-pricelist-rem.php','0','REMOVE SUPPLIER PRICELIST');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','purch-apprv','0','APPROVE PURCHASE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('3','cust-block.php','0','BLOCK CUSTOMER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('3','cust-unblock.php','0','UNBLOCK CUSTOMER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','purch-print.php','0','PRINT PURCHASE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','rec-invoice-proc.php','0','PROCESS RECURRING INVOICES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','stock-sales-rep-stk.php','0','STOCK SALES REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','purchase-view-ret.php','0','VIEW RETURNED PURCHASES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','purch-det-ret.php','0','VIEW RETURNED PURCHASE DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','multi-bank-recon-saved.php','0','VIEW SAVED BANK RECONCILIATIONS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','multi-allcat.php','0','ALL CATEGORIES AND RELATED ACCOUNTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','invoice-proc.php','0','PRINT RECURRING INVOICES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','ledger-xls.php','0','EXPORT GENERAL LEDGER TO SPREADSHEET');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','reports-tokens-closed2.php','0','List closed queries');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','bank-recpt-inv-int.php','0','RECEIPT FOR INTERNATIONAL CUSTOMER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','bank-pay-supp-int.php','0','PAYMENT TO INTERNATIONAL SUPPLIER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','bank-payment-customer.php','0','ADD PAYMENT TO CUSTOMER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','intsorder-print.php','0','PRINT INTERNATIONAL SALES ORDER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','cheq-return-int.php','0','CANCEL INTERNATIONAL PAYMENT/RECEIPT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','intsorder-cancel.php','0','CANCEL INTERNATIONAL SALES ORDER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','intsorder-details.php','0','VIEW INTERNATIONAL SALES ORDER DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','nons-intinvoice-new.php','0','NEW INTERNATIONAL NON STOCK INVOICE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','nons-intinvoice-print.php','0','PRINT INTERNATIONAL NON STOCK INVOICE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','nons-intinvoice-print.php','0','PRINT INTERNATIONAL NON STOCK INVOICE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','nons-intinvoice-note.php','0','NEW INTERNATIONAL NON STOCK INVOICE CREDIT NOTE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','pos-quote-unf-cancel.php','0','CANCEL INCOMPLETE POS QUOTE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','nons-intinvoice-det.php','0','VIEW INTERNATIONAL NON STOCK INVOICE DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','bank-pay-cus.php','0','ADD BANK PAYMENT TO CUSTOMER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','intinvoice-reprint-prd.php','0','REPRINT INTERNATIONAL INVOICE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('3','intcust-trans.php','0','INTERNATIONAL CUSTOMER TRANSACTION');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','intinvoice-details-prd.php','0','VIEW INTERNATIONAL INVOCIE DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('1','intsupp-trans.php','0','INTERNATIONAL SUPPLIER TRANSACTION');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','purch-int-view-ret.php','0','VIEW RETURNED INTERNATIONAL PURCHASE DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','purch-int-recmode.php','0','RECORD INTERNATIONAL PURCHASE RECEIVED');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','budget-yr-export.php','0','EXPORT YEARLY BUDGET');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','budget-export.php','0','EXPORT MONTHLY BUDGET');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','nons-purch-int-view-prd.php','0','VIEW RECEIVED INTERNATIONAL NON STOCK ORDERS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','employee-pay.php','0','PAY EMPLOYEE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('3','customers-find.php','0','FIND DEBTOR');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('1','supp-find.php','0','FIND CREDITOR');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','set-balance-sheet.php','0','SET DETAILED BALANCE SHEET');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','gen-balance-sheet.php','0','GENERATE DETAILED BALANCE SHEET');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','gen-balance-sheet-xls.php','0','EXPORT DETAILED BALANCE SHEET TO SPREADSHEET');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','gen-trial-balance.php','0','GENERATE DETAILED TRIAL BALANCE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','gen-trial-balance-xls.php','0','EXPORT DETAILED TRIAL BALANCE TO SPREADSHEET');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','set-trial-balance.php','0','SET DETAILED TRIAL BALANCE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','gen-income-stmnt.php','0','GENERATE DETAILED INCOME STATEMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','gen-income-stmnt-xls.php','0','EXPORT DETAILED INCOME STATEMENT TO SPREADSHEET');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','set-income-stmnt.php','0','SET DETAILED INCOME STATEMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','nons-purch-int-new.php','0','NEW INTERNATIONAL NON STOCK PURCHASE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','nons-purch-int-view.php','0','VIEW INTERNATIONAL NON STOCK PURCHASES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','nons-purch-int-cancel.php','0','CANCEL INTERNATIONAL NON STOCK PURCHASE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','nons-purch-int-det.php','0','VIEW INTERNATIONAL NON STOCK PURCHASE DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','nons-purch-int-recv.php','0','RECEIVE INTERNATIONAL NON STOCK PURCHASE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','nons-purch-return.php','0','RETURN INTERNATIONAL NON STOCK PURCHASE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','nons-purch-int-det-prd.php','0','VIEW RECEIVED INTERNATIONAL NON STOCK PURCHASE DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','invoice-note-prd.php','0','CREDIT NOTE FOR PAID INVOICE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','cashflow-report.php','0','CASHFLOW REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','invoices-email.php','0','EMAIL INVOICES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','pos-report-sales.php','0','POS SALES REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','nons-purch-return.php','0','RETURN NON STOCK PURCHASE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','nons-note-view.php','0','VIEW RETURNED NON STOCK PURCHASES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','nons-note-det.php','0','VIEW RETURNED NON STOCK PURCHASES DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','cfe-add.php','0','ADD CASH FLOW ENTRY');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','cfe-edit.php','0','EDIT CASH FLOW ENTRY');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','cfe-rem.php','0','REMOVE CASH FLOW ENTRY');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','cfe-view.php','0','VIEW CASH FLOW ENTRIES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('3','statements-email.php','0','EMAIL STATEMENTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','rbs-add.php','0','Add reimbursement');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','rbs-view.php','0','View reimbursements');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','edit-add.php','0','Edit reimbursement');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','rbs-rem.php','0','Delete reimbursement');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','rec-nons-invoice-new.php','0','New Recurring Non-Stock Invoice');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','rec-nons-invoice-view.php','0','View Recurring Non-Stock Invoices');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','rec-nons-invoice-det.php','0','View Recurring Non-Stock Invoice Details');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','nons-rec-invoice-proc.php','0','Process Recurring Non-Stock Invoices');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','nons-invoices-print.php','0','Print Recurring Non-Stock Invoices');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','import-statement.php','0','Import bank statement');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','salaries-batch.php','0','Batch salaries');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','salaries-staffr.php','0','Reverse salary');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','rec-nons-invoice-rem.php','0','Delete Recurring Non-Stock Invoice');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','asset-edit.php','0','Edit asset');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','nons-sorder-new.php','0','New Non-Stock Sales Order');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','nons-sorder-view.php','0','View Non-Stock Sales Orders');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','nons-sorder-det.php','0','View Non-Stock Sales Orders Details');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','nons-sorder-print.php','0','Print Non-Stock Sales Orders');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','nons-sorder-pdf-print.php','0','Print Non-Stock Sales Orders PDF');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','nons-sorder-acc.php','0','Accept Non-Stock Sales Orders');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','cust-ledger-xls.php','0','Export customer ledger to spreadsheet');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','stock-ledger-xls.php','0','Export stock ledger to spreadsheet');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','pos-report-user-xls.php','0','Export POS cash report to spreadsheet');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','pos-report-sales-xls.php','0','Export POS sales report to spreadsheet');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','supp-ledger-xls.php','0','Export supplier ledger to spreadsheet');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','ledger-prd-xls.php','0','Export period range ledger to spreadsheet');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','ledger-ytd-xls.php','0','Export YTD ledger to spreadsheet');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','trans-amt-xls.php','0','Export All journal enteries by ref to spreadsheet');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','sup-pricelist-det.php','0','SUPPLIER PRICELIST DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','nons-purch-print.php','0','PRINT NON STOCK PURCHASE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','invoice-delnote.php','0','DELIVERY NOTE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','index-reports-banking.php','0','BANKING REPORTS INDEX');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','index-reports-stmnt.php','0','STATEMENT REPORTS INDEX');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','index-reports-debtcred.php','0','DEBTORS AND CREDITORS REPORT INDEX');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','index-reports-journal.php','0','JOURNAL REPORTS INDEX');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','index-reports-other.php','0','OTHER REPORTS INDEX');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','reports-vat.php','0','VAT REPORTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','employee-ledger.php','0','VIEW EMPLOYEE LEDGER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','cash-flow.php','0','VIEW CASH FLOW STATEMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','import-stock.php','0','IMPORT STOCK');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('3','import-customers.php','0','IMPORT CUSTOMERS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('1','import-suppliers.php','0','IMPORT SUPPLIERS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','import-tb.php','0','IMPORT TRIAL BALANCE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','pdf-tax-invoice.php','0','VIEW PDF INVOICE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','callout-new.php','0','ADD CALL OUT DOCUMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','callout-view.php','0','VIEW CALL OUT DOCUMENTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','callout-inved-view.php','0','VIEW INVOICED CALL OUT DOCUMENTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','callout-unf-view.php','0','VIEW INCOMPLETE CALL OUT DOCUMENTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','callout-canc-view.php','0','VIEW CANCELED CALL OUT DOCUMENTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','label-stock-print.php','0','PRINT STOCK LABELS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','label-stock-save.php','0','STORE STOCK LABELS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','irp5-pdf.php','0','VIEW IRP5 PDF');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','irp5-export.php','0','EXPORT IRP5');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','irp5-data.php','0','VIEW IRP5');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','employee-tran.php','0','EMPLOYEE TRANSACTION');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','it3-pdf.php','0','VIEW IT3 PDF');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','doc-view-type.php','0','VIEW DOCUMENT TYPES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','empacc-link.php','0','SET EMPLOYEE ACCOUNT LINK');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','payslipsr.php','0','VIEW REVERSED SALARIES SLIPS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','fringeben-add.php','0','ADD FRINGE BENEFIT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','fringebens-view.php','0','VIEW FRINGE BENEFITS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','subsistence-edit.php','0','DEFINE SUBSISTENCE ALLOWANCE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','subsistence-view.php','0','VIEW SUBSISTENCE ALLOWANCE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','loans-archive.php','0','VIEW ARCHIVED LOANS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','grievances-add.php','0','ADD GRIEVANCES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','grievances-view.php','0','VIEW GRIEVANCES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','employee-training-add.php','0','ADD EMPLOYEE TRAINING');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','training-view.php','0','VIEW EMPLOYEE TRAINING');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','training-search.php','0','SEARCH EMPLOYEE TRAINING');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','employee-training-rep.php','0','EMPLOYEE TRAINING REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','training-provider-add.php','0','ADD EMPLOYEE TRAINING PROVIDER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('3','label-cust-save.php','0','STORE CUSTOMER LABELS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('3','label-cust-print.php','0','PRINT CUSTOMER LABELS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','save-age.php','0','SAVE AGE ANALYSIS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','pdf-statement.php','0','VIEW CUSTOMER PDF STATEMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('3','customers-email.php','0','EMAIL CUSTOMERS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('1','supp-group-add.php','0','ADD SUPPLIER GROUP');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('1','supp-group-view.php','0','VIEW SUPPLIER GROUP');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('1','label-supp-print.php','0','PRINT SUPPLIER LABELS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('1','label-supp-save.php','0','STORE SUPPLIER LABELS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('1','supp-payment-run.php','0','CREDITOR PAYMENT RUN');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('3','email_page.php','0','EMAIL CUSTOMER PAGE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','diary-privileges.php','0','DIARY PRIVILEGES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','newmessage-iframe.php','0','NEW MESSAGE WINDOW');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','doc-add-iframe.php','0','NEW DOCUMENT WINDOW');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','doc-view-iframe.php','0','VIEW DOCUMENT WINDOW');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','todo_iframe.php','0','OPEN TODO WINDOW');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','diary-iframe.php','0','OPEN DIARY WINDOW');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','purch-recv-print.php','0','PRINT RECEIVED PURCHASES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','nons-purch-recv-print.php','0','PRINT RECEIVED NON STOCK PURCHASES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','openpopup.php','0','ALLOW CUBIT WINDOW REDIRECT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','workshop-view.php','0','VIEW WORKSHOP');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','workshop-add.php','0','ADD WORKSHOP');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','stock-move-report-xls.php','0','EXPORT STOCK MOVEMENT REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('1','supp-tran-rep-xls.php','0','EXPORT SUPPLIER TRANSACTION REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','nons-purch-int-return.php','0','RETURN INTERNATIONAL NON STOCK PURCHASE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','nons-purch-int-view-ret.php','0','VIEW INTERNATIONAL RETURNED NON STOCK PURCHASES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','leads_new.php','0','ADD LEADS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','leads_list.php','0','VIEW LEADS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','invoice-delnote-prd.php','0','INVOICE DELIVERY NOTE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','stock-sales-rep-xls.php','0','EXPORT STOCK SALES REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','support_search.php','0','CUBIT SUPPORT DATABASE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','register.php','0','REGISTER CUBIT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','batch-cashbook-view.php','0','VIEW BATCH CASHBOOK');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','cashbook-entry.php','0','NEW CASHBOOK ENTRY');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','docman-index.php','0','DOCUMENT MANAGEMENT INDEX');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','diary-index.php','0','DIARY INDEX');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','index-admin.php','0','ADMINISTRATION INDEX');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','index-audit.php','0','AUDIT INDEX');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','index-company.php','0','COMPANY INDEX');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','index-recuring.php','0','RECURING INDEX');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','accounttype-add.php','0','ADD BANK ACCOUNT TYPE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','admin-paye-add.php','0','ADD PAYE BRACKET');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','admin-paye-view.php','0','VIEW PAYE BRACKETS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','emp-ledger-audit.php','0','VIEW/AUDIT EMPLOYEE LEDGER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','stock-ledger-audit.php','0','VIEW/AUDIT STOCK LEDGER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','supp-ledger-audit.php','0','VIEW/AUDIT SUPPLIER LEDGER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','yr-income-stmnt.php','0','VIEW PREVIOUS YEAR INCOME STATEMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','trial-bal.php','0','GENERATE PREVIOUS YEAR TRIAL BALANCE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','ledger-audit-prd.php','0','VIEW GENERAL LEDGER BY PERIOD RANGE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','ledger-audit.php','0','VIEW/AUDIT GENERAL LEDGER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','balance-sheet.php','0','VIEW SAVED BALANCE SHEETS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','prd-trans-new.php','0','VIEW PREVIOUS PERIODS JOURNAL TRANSACTIONS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','cust-ledger-audit.php','0','VIEW/AUDIT DEBTORS LEDGER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','batch-entry-delete.php','0','DELETE BATCH CASHBOOK ENTRY');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','stock-tran.php','0','STOCK BALANCE TRANSACTION');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','multi-bank-recpt-edit.php','0','EDIT BANK RECEIPT (MULTIPLE)');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','cheq-cancel-int.php','0','CANCEL CASH BOOD ENTRY');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','multi-acc-popup.php','0','CASHBOOK MULTIPLE ACCOUNTS LISTING POPUP');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','bank-trans-edit.php','0','EDIT BANK TRANSFER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','multi-bank-pay-edit.php','0','EDIT BATCH CASHBOOK PAYMENT ENTRY');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','bank-pay-edit.php','0','EDIT CASHBOOK PAYMENT ENTRY');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','bank-bankall.php','0','PROCESS MULTIPLE BANKING ENTRIES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','budget-yr-details.php','0','VIEW YEARLY BUDGET DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','budget-yr-report-print.php','0','PRINT YEARLY BUDGET REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','budget-yr-new.php','0','NEW YEARLY BUDGET');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','budget-report-print.php','0','PRINT BUDGET REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','budget-yr-edit.php','0','EDIT YEARLY BUDGET');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','budget-yr-rem.php','0','REMOVE YEARLY BUDGET');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','statement-ref-add.php','0','ADD STATEMENT DESCRIPTION DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','statement-ref-edit.php','0','EDIT STATEMENT DESCRIPTION DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','import-settings.php','0','CHANGE IMPORT SETTINGS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','income-xls.php','0','GENERATE INCOME STATEMENT SPREADSHEET');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','bal-xls.php','0','GENERATE BALANCE SHEET SPREADSHEET');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','emp-ledger-xls.php','0','GENERATE EMPLOYEE LEDGER SPREADSHEET');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','ledger-audit-xls.php','0','GENERATE GENERAL LEDGER AUDIT SPREADSHEET');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','pricelist-xls.php','0','GENERATE PRICELIST SPREADSHEET');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','cust-ledger-audit-xls.php','0','GENERATE CUSTOMER LEDGER AUDIT SPREADSHEET');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','asset-export.php','0','GENERATE ASSET LEDGER SPREADSHEET');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','ledger-audit-prd-xls.php','0','GENERATE GENERAL LEDGER AUDIT PERIOD SPREADSHEET');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','stock-ledger-audit-xls.php','0','GENERATE STOCK LEDGER AUDIT SPREADSHEET');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','reports-vat-xls.php','0','GENERATE VAT REPORT SPREADSHEET');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','tb-xls.php','0','GENERATE TRIAL BALANCE SPREATSHEET');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','supp-ledger-audit-xls.php','0','GENERATE SUPPLIER LEDGER AUDIT SPREADSHEET');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','todo.php','0','MANAGE TO DO LIST');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','tdocdload.php','0','SHOW DOCUMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','grpedit.php','0','EDIT GROUPS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','usradd.php','0','ADD NEW USER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','tdocview.php','0','MANAGE DOCUMENTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','archive.php','0','VIEW ARCHIVED DOCUMENTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','tdocedit.php','0','EDIT DOCUMENTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','foladd.php','0','ADD FOLDER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','grprem.php','0','REMOVE GROUP');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','docdload.php','0','DOWNLOAD DOCUMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','tdocrem.php','0','REMOVE DOCUMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','doctyperem.php','0','REMOVE DOCUMENT TYPE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','link.php','0','LINKING DOCUMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','view.php','0','VIEW DOCUMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','usredit.php','0','EDIT USER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','view.php','0','VIEW DOCUMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','grpadd.php','0','ADD GROUP');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','foledit.php','0','EDIT FOLDER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','doctypeadd.php','0','ADD DOCUMENT TYPE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','usrem.php','0','REMOVE USER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','delete.php','0','DELETE CONTACT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','doctypeview.php','0','VIEW DOCUMENT TYPE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','tlistedit.php','0','EDIT DOCUMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','tlist-docview.php','0','LIST DOCUMENTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','docview.php','0','VIEW DOCUMENTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','docrem.php','0','REMOVE DOCUMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','folrem.php','0','REMOVE FOLDER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','docedit.php','0','EDIT DOCUMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','tdocadd.php','0','ADD DOCUMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','editdel.php','0','MODIFY CONTACT INFORMATION');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','tlistrem.php','0','REMOVE DOCUMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','history.php','0','SHOW HISTORY');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','docadd.php','0','ADD DOCUMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','email.php','0','SEND EMAIL');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','publish.php','0','PUBLISH DOCUMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','tlistres.php','0','SHOW DOCUMENTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','checkout.php','0','CHECK IN DOCUMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','discussion.php','0','DESCUSSION');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','viewmessage.php','0','VIEW MESSAGE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','diary-smallmonth.php','0','SHOW DIARY MONTH');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','diary-appointment.php','0','MAKE APPOINTMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','diary-appointment.php','0','MAKE DIARY APPOINTMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','newmessage.php','0','NEW MESSAGE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','messages.php','0','VIEW MESSAGES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','msglist.php','0','VIEW MESSAGES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','newaccount.php','0','CREATE NEW ACCOUNT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','newaccount.php','0','CREATE NEW ACCOUNT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','viewmessage.php','0','VIEW MESSAGE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','getattachment.php','0','GENERATE ATTACHMENTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','getmessages.php','0','SHOW MESSAGES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','accounts.php','0','VIEW ACCOUNTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('4','report_asa401.php','0','VIEW ASA401 REPORTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('4','auditor_record.php','0','AUDITOR RECORDING SECTION');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('4','cash-flow-view.php','0','VIEW SAVED CASH FLOW STATEMENTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('4','cash-flow-print.php','0','GENERATE CASH FLOW STATEMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('4','multi-acc-trans.php','0','VIEW ACCOUNT JOURNAL ENTRIES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','purchase-new-cash.php','0','NEW ORDER (CASH)');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','purch-canc-print.php','0','VIEW CANCELED PURCHASES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','purch-int-det-ret.php','0','VIEW RETURNED INTERNATIONAL PURCHASES DETAIL');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','purch-int-recnote.php','0','RETURN STOCK TO SUPPLIER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','purch-recv-cash.php','0','RECEIVE PURCHASE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','credit-purch-pay.php','0','PAY CREDIT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','lnons-purch-ret.php','0','VIEW RETURNED LINKED NON STOCK PURCHASES ');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','nons-purch-int-det-ret.php','0','VIEW RETURNED NON STOCK PURCHASES DETAIL');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','nons-purch-int-print.php','0','PRINT NON STOCK INTERNATIONAL PURCHASES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('5','supp-pdf-stmnt-date.php','0','GENERATE SUPPLIER PDF STATEMENT (DATE)');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','delnote-out.php','0','VIEW OUTSTANDING STOCK');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','delnote-report.php','0','VIEW OUTSTANDING STOCK REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','rfid_batch.php','0','RECORD RFID BARCODES BATCH');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','rfid_write.php','0','RECORD RFID BARCODES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','stock-price-inc.php','0','INCREASE STOCK SELLING PRICE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','stock-view-image.php','0','VIEW STOCK IMAGE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','order-cancel.php','0','CANCEL STOCK ORDER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','order-det.php','0','STOCK ORDER DETAIL');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','order-new.php','0','NEW STOCK ORDER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','order-recv.php','0','RECEIVE STOCK ORDER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','orders-view.php','0','VIEW STOCK ORDERS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','pos-rem.php','0','REMOVE BARCODE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','callout-cancel.php','0','CANCEL CALL OUT DOCUMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','callout-invoiced.php','0','VIEW INVOICED CALL OUT DOCUMENTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','callout-print.php','0','PRINT CALL OUT DOCUMENTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','callout-unf-cancel.php','0','VIEW INCOMPLETE CALL OUT DOCUMENTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','callout-uploaddoc.php','0','UPLOAD CALL OUT DOCUMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','invoice-new.php','0','NEW INVOICE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','cust-credit-stockinv-deladdr.php','0','EDIT INVOICE DELIVERY ADDRESS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','invoice-reprint-prd.php','0','INVOICE REPRINT (DATE)');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','nons-invoice-unf-view.php','0','VIEW INCOMPLETE NON STOCK INVOICES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','cust-pdf-print-invoices.php','0','GENERATE CUSTOMER PDF INVOICE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','nons-processed-view.php','0','VIEW PRCESSED NON STOCK INVOICES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','invoice-pdf-reprint-prd.php','0','GENERATE PDF INVOICE REPRINT (DATE)');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','pos-invoice-reprint.php','0','REPRING POS INVOICE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','pos-invoice-speed.php','0','NEW SPEED POS INVOICE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','pos-quote-pdf-print.php','0','GENERATE POS QUOTE PDF');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','nons-invoice-pdf-reprint.php','0','GENERATE NON STOCK INVOICE PDF REPRINT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','nons-quote-pdf-print.php','0','GENERATE NON STOCK QUOTE PDF');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','cust-pdf-stmnt-date.php','0','GENERATE CUSTOMER PDF STATEMENT (DATE)');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','supp-pdf-stmnt.php','0','GENERATE SUPPLIER PDF STATEMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','admin-branedit.php','0','EDIT COMPANY BRANCH');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','admin-branrem.php','0','REMOVE COMPANY BRANCH');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','admin-deptadd.php','0','ADD NEW USER DEPARTMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','admin-deptedit.php','0','MOVE SCRIPT PERMISSIONS BETWEEN DEPARTMENTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','admin-deptrem.php','0','REMOVE SCRIPT PERMISSION DEPARTMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','admin-deptview.php','0','VIEW USER DEPARTMENTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','admin-usradd.php','0','ADD NEW USER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','admin-usredit.php','0','EDIT USER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','admin-usrrem.php','0','REMOVE USER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','admin-usrview.php','0','VIEW USERS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','asset-app.php','0','EDIT ASSET APPRECIATION');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','bank-recpt-edit.php','0','EDIT BANK RECEIPTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','ccpopup.php','0','SHOW ALLOCATE TO COST CENTERS POPUP');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','ch.php','0','VIEW CUBIT VERSION INFORMATION');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','company-rem.php','0','REMOVE COMPANY');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','getimg.php','0','SHOW COMPANY BANNER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','getimg2.php','0','SHOW COMPANY LOGO');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','compinfo-view.php','0','VIEW COMPANY INFORMATION');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','coms-edit.php','0','SET SALES REP COMMISSION');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','conper-add.php','0','ADD CONTACT PERSON');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','conper-edit.php','0','EDIT CONTACT PERSON');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','conper-rem.php','0','REMOVE CONTACT PERSON');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','yr-close.php','0','CLOSE YEAR');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','cubitnet_settings.php','0','CUBIT INTERNET SETTINGS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','currency-add.php','0','ADD NEW CURRENCY');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','currency-edit.php','0','EDIT CURRENCY');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','currency-rem.php','0','REMOVE CURRENCY');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','currency-view.php','0','VIEW CURRENCIES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','cust-branch-add.php','0','ADD CUSTOMER BRANCH');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','cust-branch-del.php','0','REMOVE CUSTOMER BRANCH');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','cust-branch-edit.php','0','EDIT CUSTOMER BRANCH');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','cust-branch-view.php','0','VIEW CUSTOMER BRANCHES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','email-settings.php','0','CHANGE EMAIL SETTINGS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','employee-training-edit.php','0','EDIT EMPLOYEE TRAINING');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','grievance-det.php','0','VIEW GRIEVANCE DETAIL');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','grievance-edit.php','0','EDIT GRIEVANCE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','salesp-add.php','0','ADD NEW SALES PERSON');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','dept-view.php','0','VIEW DEPARTMENTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','od-add.php','0','ADD OVERDUE TERM');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','od-edit.php','0','EDIT OVERDUE TERM');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','ct-view.php','0','VIEW CREDIT TERMS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','dept-add.php','0','ADD DEPARTMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','ct-add.php','0','ADD CREDIT TERM');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','ct-edit.php','0','EDIT CREDIT TERM');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','default-comments.php','0','SET DEFAULT INVOICE COMMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','default-pos-comments.php','0','SET DEFAULT POS INVOICE COMMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','default-stmnt-comments.php','0','SET DEFAULT STATEMENT COMMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','defdep-slct.php','0','CREATE DEFALT ACCOUNTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','acc-new.php','0','ADD ACCOUNT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','accat-new.php','0','ADD NEW CATEGORY');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','finyear-range.php','0','SET FINANCIAL YEAR PERIOD RANGE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','finyearnames-view.php','0','VIEW FINANCIAL YEAR NAMES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','acc-view.php','0','VIEW ACCOUNTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','finyearnames-new.php','0','ADD FINANCIAL YEAR NAMES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','pchs-link.php','0','CREATE ACCOUNT LINK');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','sales-link.php','0','SET ACCOUNT LINK');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','xrate-change.php','0','CHANGE EXCHANGE RATE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','whouse-add.php','0','ADD STORE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','class-add.php','0','ADD CLASSIFICATION');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','pricelist-view.php','0','VIEW PRICELISTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','sup-pricelist-view.php','0','VIEW SUPPLIER PRICELISTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','calloutp-rem.php','0','REMOVE CALL OUT PERSON');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','calloutp-view.php','0','VIEW CALL OUT PEOPLE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','settings-acc-edit.php','0','EDIT ACCOUNTING SETTINGS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','intbrac-add.php','0','ADD INTEREST BRACKET');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','scpopup.php','0','OPEN COST CENTER POPUP');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','set-block-mainaccount.php','0','CHANGE BLOCK MAIN ACCOUNTS SETTING');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','set-costcenter-use.php','0','CHANGE USE COST CENTER SETTING');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','set-currency-symbol.php','0','SET CURRENCY SYMBOL');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','set-cust-inv-warn.php','0','CHANGE CUSTOMER CREDIT LIMIT RESPONSE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','set-debt-age.php','0','CHANGE DEBTORS AGE ANALYSIS PERIOD SETTING');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','set-int-type.php','0','INTEREST CLACULATION');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','set-inv-bankdetails.php','0','CHANGE BANKING DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','set-login-retries.php','0','CHANGE LOGIN RETRIES SETTING');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','set-period-use.php','0','CHANGE YEAR/PERIOD CONTROL SETTING');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','set.php','0','CHANGE CUBIT ACCOUNT SETTING');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','set-purch-apprv.php','0','CHANGE PURCHASE REQUIRE APPROVAL SETTING');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','set-state.php','0','CHANGE STATEMENT TYPE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','setup.php','0','RUN QUICK SETUP');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','set-vat-type.php','0','CHANGE VAT INCLUSIVE/EXCLUSIVE SETTING');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','set-view.php','0','VIEW CURRENT SETTINGS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','sorder-cancel-details.php','0','VIEW CANCELED SALES ORDER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','sorder-cancel-print.php','0','PRINT CANCELED SALES ORDER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','splash.php','0','CHANGE SPLASH SCREEN');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','stockcat-add.php','0','ADD STOCK CATEGORY');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','stockcat-view.php','0','VIEW STOCK CATEGORIES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','stockclass-add.php','0','ADD STOCK CLASSIFICATION');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','stockclass-view.php','0','VIEW STOCK CLASSIFICATIONS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','od-rem.php','0','REMOVE OVERDUE TERM');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','calloutp-edit.php','0','EDIT CALL OUT PERSON');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','cat-view.php','0','VIEW CATEGORIES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','salesp-view.php','0','VIEW SALES PEOPLE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','whouse-view.php','0','VIEW STORES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','ct-rem.php','0','REMOVE CREDIT TERM');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','class-view.php','0','VIEW CLASSIFICATIONS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','defwh-set.php','0','SET DEFAULT STORE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','user-add.php','0','ADD NEW USER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','user-view.php','0','VIEW USERS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','vatcodes-add.php','0','ADD VATCODE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','vatcodes-edit.php','0','EDIT VATCODES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','vatcodes-rem.php','0','REMOVE VATCODES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','vatcodes-view.php','0','VIEW VATCODES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','workshop-settings.php','0','WORKSHOP SETTINGS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','pricelist-add.php','0','ADD PRICELIST');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','sup-pricelist-add.php','0','ADD SUPPLIER PRICELIST');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','cat-add.php','0','ADD CATEGORY');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','invid-set.php','0','CHANGE INVOICING NUMBER SETTINGS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','pricelist-print.php','0','PRINT PRICELISTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','od-view.php','0','VIEW OVERDUE TERMS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','calloutp-add.php','0','ADD CALLOUT PERSON');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','toms-settings.php','0','CHANGE SETTINGS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','intbrac-add.php','0','ADD INTEREST BRACKET');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','intbrac-view.php','0','VIEW INTEREST BRACKETS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','cust-view.php','0','VIEW CUSTOMERS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','cust-add.php','0','ADD CUSTOMER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','leads_view.php','0','VIEW LEADS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','leads_edit.php','0','EDIT LEAD');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','pos-set.php','0','CHANGE POS ITEM BARCODE SETTING');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','pos-setting.php','0','CHANGE POS ROUNDING SETTING');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','pos-user-add.php','0','ADD NEW POS USER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','psql_path.php','0','SET POSTGRES PSQL BINARY PATH');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','sal-link.php','0','SET ACCOUNT LINK');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','paye-rem.php','0','REMOVE PAYE BRACKET');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','rbs-edit.php','0','EDIT REIMBURSEMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','employee-detail.php','0','VIEW EMPLOYEE DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','template-settings.php','0','CHANGE OUTPUT TEMPLATE SETTINGS(HTML/PDF)');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','customers-email-msg.php','0','EMAIL CUSTOMERS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','entries.php','0','SHOW CASHBOOK ENTRIES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','statement-ref-rem.php','0','REMOVE STATEMENT DESCRIPTION DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','ncpopup.php','0','GENERATE COST CENTRE POPUP');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('3','invoice-email.php','0','EMAIL INVOICE TO CUSTOMERS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','leave_report.php','0','LEAVE REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','loan_approval.php','0','APPROVE LOAN SETTING');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','loan_apply_approve.php','0','APPROVE LOANS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','loans-view.php','0','VIEW ARCHIVED LOANS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','loan_apply.php','0','APPLY FOR LOANS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','loan_apply_view.php','0','VIEW LOAN APPLICATIONS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','bursary_give.php','0','GIVE BURSARY');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','bursary_type_rem.php','0','REMOVE BURSARY DATA');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','bursary_type_add.php','0','ADD BURSARY DATA');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','bursary_type_view.php','0','VIEW BURSARY DATA');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','bursary_view.php','0','VIEW GIVEN BURSARIES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','bursary_add.php','0','ADD BURSARY DATA');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','loan_type_rem.php','0','REMOVE LOAN TYPE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','loan_type_view.php','0','VIEW LOAN TYPES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','loan_type_add.php','0','ADD LOAN TYPE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','public_holiday_add.php','0','ADD PUBLIC HOLIDAY');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','public_holiday_rem.php','0','REMOVE PUBLIC HOLIDAY');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','public_holiday_list.php','0','LIST PUBLIC HOLIDAYS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('14','action_report.php','0','ACTION REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('14','action_save.php','0','ADD/EDIT ACTION');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('14','doc_dep_remove.php','0','REMOVE DOCUMENT DEPARTMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('14','doc_dep_save.php','0','ADD/EDIT DOCUMENT DEPARTMENTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('14','doc_dep_view.php','0','VIEW DOCUMENT DEPARTMENTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('14','doc_type_remove.php','0','REMOVE DOCUMENT TYPE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('14','doc_type_save.php','0','ADD/EDIT DOCUMENT TYPE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('14','doc_type_view.php','0','VIEW DOCUMENT TYPE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('14','document_det.php','0','VIEW DOCUMENT DETAILS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('14','document_movement.php','0','DOCUMENT MOVEMENT REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('14','document_save.php','0','ADD/EDIT DOCUMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('14','document_view.php','0','VIEW DOCUMENTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('15','gantt_display.php','0','GANTT CHART');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('15','people_save.php','0','ADD/EDIT PEOPLE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('15','people_view.php','0','VIEW PEOPLE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('15','position_save.php','0','ADD/EDIT POSITION');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('15','position_view.php','0','VIEW POSITIONS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('15','project_save.php','0','ADD/EDIT PROJECTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('15','project_view.php','0','VIEW PROJECTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('15','task_save.php','0','ADD/EDIT PROJECT TASKS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('15','task_type_save.php','0','ADD/EDIT PROJECT TASK TYPES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('15','task_type_view.php','0','VIEW PROJECT TASK TYPES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('15','task_view.php','0','VIEW PROJECT TASKS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('15','team_allocate.php','0','PROJECT TEAM ALLOCATE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('15','team_save.php','0','ADD/EDIT PROJECT TEAMS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('15','team_view.php','0','VIEW PROJECT TEAMS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('15','project_charter.php','0','PROJECT CHARTER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('15','project_event_save.php','0','PROJECT EVENT ADD/EDIT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','todo_sub_save.php','0','TODO VIEW');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','todo_main_save.php','0','TODO ADD/EDIT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','iframe.php','0','GROUPWARE MENUS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','bank-recpt-multi-debtor.php','0','CASHBOOK ONE RECEIPT FOR MULTIPLE CUSTOMERS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('10','emailsave_page.php','0','EMAIL OPEN PAGE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','ledger_export.php','0','EXPORT ACCOUNT MOVEMENT REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','asset-autodep.php','0','APPLY AUTOMATIC ASSET DEPRECIATION');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','location_save.php','0','ADD DIARY LOCATION');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','today.php','0','TODAY ACTION DISPLAY');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','newaccount.php','0','NEW EMAIL ACCOUNT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','word_proc.php','0','WORD PROCESSOR');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('11','cubit_docs.php','0','VIEW CUBIT DOCUMENTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','pos-pricelist-edit.php','0','POS PRICELIST EDIT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','email-groups.php','0','SEND EMAIL TO EMAIL GROUPS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','email-group-new.php','0','ADD EMAIL GROUP');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','email-group-view.php','0','VIEW EMAIL GROUPS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('13','email-group-remove.php','0','REMOVE EMAIL GROUP');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','sales_report.php','0','SALES REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','multi-bank-recpt-inv.php','0','CASHBOOK MULTIPLE RECEIPTS FOR MULTIPLE CUSTOMERS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('1','recon_balance_ct.php','0','ADD BALANCE ACCORDING TO CRE
DITOR');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('1','recon_reason_view.php','0','ADD/REMOVE/VIEW RECON REASONS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('1','recon_report_ct.php','0','CREDITORS RECON REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('1','recon_statement_ct.php','0','CREDITORS RECON STATEMENT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','stock_take_pre.php','0','PRE STOCK TAKE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','stock_take_post.php','0','POST STOCK TAKE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','stock_take_adjust.php','0','STOCK TAKE ADJUST');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','stock_take_report.php','0','STOCK TAKE REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','vat-report-view.php','0','VIEW SAVED VAT 201');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','vat_return_report.php','0','GENERATE VAT 201');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','employee_timelog.php','0','Time and Attendance');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','employee_timelog_report.php','0','Time and Attendance Reports');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','employee-card.php','0','Employee Cards');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','picking_slip.php','0','Picking Slips');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','Print Picking Slip','0','picking_slip_print.php');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','Invoice Picking Slip','0','picking_slip_invoice.php');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','Mark Picking Slip Done','0','picking_slip_done.php');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','Picking Slip Settings','0','picking_slip_settings.php');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('1','Picking Slip Settings','0','picking_slip_settings.php');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('1','Add Balance According to Creditor','0','recon_balance_ct.php');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('1','RECON CREDITOR REPORT - VARIANCE','0','recon_report_variance_ct.php');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('1','RECON CREDITOR REPORT - REASON VARIANCE','0','recon_report_reason_variance_ct.php');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('3','customers-zero-new.php','0','ADD ZERO CREDIT CUSTOMER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','whathappened_stock.php','0','STOCK - WHAT HAPPENED');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('1','whathappened_stock.php','0','CREDITORS - WHAT HAPPENED');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('3','whathappened_stock.php','0','DEBTORS - WHAT HAPPENED');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','stock-sales-gp.php','0','STOCK SALES GP REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','sales_forecast.php','0','SALES FORECAST');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','sales_forecast_pit.php','0','SALES FORECAST POINT IN TIME');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','sales_forecast_view.php','0','SALES FORECAST VIEW SAVED');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','sorder-due.php','0','SALES ORDERS PAST DUE DATE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','dispatch.php','0','DISPATCH');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','signed_invoices.php','0','SIGNED INVOICES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','unsigned_invoices_report.php','0','OUTSTANDING INVOICES REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','lead_time_report.php','0','LEAD TIME REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','purchase_dateqty_report.php','0','RECOMMENDED ORDER DATE AND QTY REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','stock_minmax.php','0','MINNIMUM AND MAXIMUM STOCK LEVELS REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('1','recon_report_variance_ct.php','0','CREDITOR RECON VARIANCE REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('1','recon_report_reason_variance_ct.php','0','CREDITOR RECON VARIANCE REPORT - REASONS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','asset-sale.php','0','SALE OF ASSETS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','manu_recipe.php','0','BASIC MANUFACTURING RECIPE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','manu_stock.php','0','BASIC MANUFACTURING MANUFACTURE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('7','pricelist_adjust.php','0','ADJUST PRICELIST');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','asset_report.php','0','ASSET REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','asset_to_workshop.php','0','BOOK ASSET TO WORKSHOP');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','availibility_report.php','0','AVAILABILITY REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','booking_remove.php','0','REMOVE BOOKING');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','booking_save.php','0','ADD/EDIT BOOKING');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','booking_view.php','0','VIEW BOOKINGS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','collect_deliver.php','0','COLLECT & DELIVER REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','comment_settings.php','0','DEFAULT HIRE NOTE COMMENTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','contract_text.php','0','CONTRACT TEXT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','cust_basis.php','0','CUSTOMER BASIS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','default_basis.php','0','DEFAULT BASIS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','driver_report.php','0','DRIVER COLLECT/DELIVER');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','hire-invoice-print.php','0','PRINT HIRE NOTE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','hire-invoice-view.php','0','HIRE INVOICE REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','hire-nons-invoice-print.php','0','PRINT HIRE INVOICE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','hire-settings.php','0','HIRE SETTINGS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','hire-slip.php','0','HIRE NOTE PRINT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','hire_detail_report.php','0','HIRE DETAIL REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','hire_history_report.php','0','HIRE HISTORY REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','hire_nons_invoices_view.php','0','VIEW HIRE INVOICES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','hire_note_report.php','0','HIRE NOTE REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','hire_note_reprint.php','0','HIRE NOTE REPRINT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','hire_report.php','0','HIRE REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','hire_utilisation.php','0','HIRE UTILISATION REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','hire_view_reprint.php','0','VIEW HIRE NOTE REPRINTS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','inter_hire_report.php','0','INTER HIRE REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','monthly_processing.php','0','MONTHLY PROCESSING');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','nons-invoice-new.php','0','NEW HIRE INVOICE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','nons-invoice-reprint.php','0','REPRINT HIRE INVOICES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','overdue_report.php','0','OVERDUE REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','royalty_report.php','0','ROYALTY REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','royalty_settings.php','0','ROYALTY REPORT SETTINGS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','service_dates.php','0','SERVICE DATES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','service_history.php','0','SERVICE HISTORY');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','service_report.php','0','SERVICING REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','service_settings.php','0','SERVICE SETTINGS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','signed_hirenote_save.php','0','SAVE SIGNED HIRE NOTES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','signed_hirenotes.php','0','VIEW SIGNED HIRE NOTES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','temp_asset_view.php','0','VIEW TEMPORARY ASSETS');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','thanks_text_save.php','0','ADD/EDIT THANK YOU TEXT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','unsigned_hirenotes.php','0','VIEW UNSIGNED HIRE NOTES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','hire-invoice-new.php','0','MAIN HIRE SCREEN');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','hire_view.php','0','VIEW HIRE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','workshop-add-asset.php','0','BOOK ASSET TO WORKSHOP');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','customers-refund.php','0','DEPOSIT REFUND');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','hire-invoices-report.php','0','HIRE INVOICE REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','hire_cashup.php','0','HIRE CASH REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','contract_text','0','CONTRACT TEXT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','half_day_setting.php','0','HALF DAY RATE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','quote-email.php','0','EMAIL QUOTES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','hire-invoice-note.php','0','HIRE INVOICE CREDIT NOTE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('16','hire_cashup_full.php','0','DAILY HIRE CASH UP REPORT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','bank-recpt-inv-quick.php','0','ADD BANK RECEIPT FOR CUSTOMER INVOICES (QUICK PAYMENT)');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','cust-credit-stockinv-newsetting.php','0','NEW INVOICE COMPLETE ACTION SETTING');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','calc-cust-credit-stockinv.php','2','NEW VOLUME INVOICE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('8','export-sarsefiling.php','2','EXPORT PAYROLL DATA FOR EFILING');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','nons-multiline-invoice-new.php','2','NEW NON STOCK MULTILINE INVOICE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','general-creditnote.php','0','GENERAL CREDIT NOTE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('2','bank-recpt-inv-print.php','0','PRINT CASHBOOK RECEIPT');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','multiple-invoices-pdf.php','0','PRINT MULTIPLE PDF INVOICES');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','pdf-invoice.php','0','PRINT PDF INVOICE');
INSERT INTO deptscripts ("dept","script","div","scriptname") VALUES('6','pdf-quote.php','0','PRINT PDF QUOTE');
CREATE TABLE purint_items ("purid" numeric DEFAULT 0,"whid" numeric DEFAULT 0,"stkid" numeric DEFAULT 0,"qty" numeric DEFAULT 0,"ddate" date ,"recved" varchar ,"div" numeric DEFAULT 0,"unitcost" numeric(16, 2) DEFAULT 0,"amt" numeric(16, 2) DEFAULT 0,"cunitcost" numeric(16, 2) DEFAULT 0,"duty" numeric(16, 2) DEFAULT 0,"dutyp" numeric(16, 2) DEFAULT 0,"rqty" numeric DEFAULT 0,"tqty" numeric DEFAULT 0) WITH OIDS;
CREATE TABLE ss6 ("stock" int4 DEFAULT 0,"code" varchar ,"div" numeric DEFAULT 0,"active" varchar DEFAULT 'yes'::character varying) WITH OIDS;
CREATE TABLE supp_payment_cheques ("id" serial NOT NULL PRIMARY KEY ,"key" varchar DEFAULT ''::character varying,"bankid" numeric DEFAULT 0,"date" date ,"supid" numeric DEFAULT 0,"descript" varchar DEFAULT ''::character varying,"reference" varchar DEFAULT ''::character varying,"cheqnum" varchar DEFAULT ''::character varying,"all_val" numeric DEFAULT 2,"out" numeric(16, 2) DEFAULT 0,"amt" numeric(16, 2) DEFAULT 0,"overpay" numeric(16, 2) DEFAULT 0,"setamt" numeric(16, 2) DEFAULT 0,"setvat" varchar DEFAULT ''::character varying,"setvatcode" numeric DEFAULT 0,"invids" varchar DEFAULT ''::character varying,"paidamt" varchar DEFAULT ''::character varying,"stock_setamt" varchar DEFAULT ''::character varying,"out1" numeric(16, 2) DEFAULT 0,"out2" numeric(16, 2) DEFAULT 0,"out3" numeric(16, 2) DEFAULT 0,"out4" numeric(16, 2) DEFAULT 0,"out5" numeric(16, 2) DEFAULT 0,"out1_val" numeric(16, 2) DEFAULT 0,"out2_val" numeric(16, 2) DEFAULT 0,"out3_val" numeric(16, 2) DEFAULT 0,"out4_val" numeric(16, 2) DEFAULT 0,"out5_val" numeric(16, 2) DEFAULT 0,"date_day" varchar DEFAULT ''::character varying,"date_month" varchar DEFAULT ''::character varying,"date_year" varchar DEFAULT ''::character varying,"printed" varchar DEFAULT 'no'::character varying,"done" varchar DEFAULT 'no'::character varying,"supname" varchar ) WITH OIDS;
SELECT setval('supp_payment_cheques_id_seq',1);
CREATE TABLE movpurch ("id" serial NOT NULL PRIMARY KEY ,"purtype" varchar ,"purnum" numeric DEFAULT 0,"prd" numeric DEFAULT 0,"div" numeric DEFAULT 0) WITH OIDS;
SELECT setval('movpurch_id_seq',1);
CREATE TABLE removed_list ("id" serial NOT NULL PRIMARY KEY ,"emailaddress" varchar ,"date_removed" date ) WITH OIDS;
SELECT setval('removed_list_id_seq',1);
CREATE TABLE hire_trans_contracts ("id" serial NOT NULL PRIMARY KEY ,"stock_id" numeric DEFAULT 0,"hire_id" numeric DEFAULT 0,"qty" numeric DEFAULT 0,"unitprice" numeric(16, 2) DEFAULT 0,"total" numeric(16, 2) DEFAULT 0,"cost_price" numeric(16, 2) DEFAULT 0,"units" numeric DEFAULT 0) WITH OIDS;
SELECT setval('hire_trans_contracts_id_seq',1);
CREATE TABLE supp_groups ("id" serial NOT NULL PRIMARY KEY ,"groupname" varchar ) WITH OIDS;
SELECT setval('supp_groups_id_seq',4);
INSERT INTO supp_groups ("id","groupname") VALUES('2','Normal');
INSERT INTO supp_groups ("id","groupname") VALUES('3','Sub Contractor: Normal');
INSERT INTO supp_groups ("id","groupname") VALUES('4','Sub Contractor: PAYE');
CREATE TABLE suppurch ("id" serial NOT NULL PRIMARY KEY ,"supid" numeric DEFAULT 0,"purid" numeric DEFAULT 0,"intpurid" numeric DEFAULT 0,"pdate" date ,"div" numeric DEFAULT 0,"npurid" numeric DEFAULT 0,"balance" numeric(16, 2) DEFAULT 0,"fcid" numeric DEFAULT 0,"fbalance" numeric(16, 2) DEFAULT 0,"actual_date" varchar ) WITH OIDS;
SELECT setval('suppurch_id_seq',1);
CREATE TABLE mail_priv_accounts ("ap_id" serial NOT NULL PRIMARY KEY ,"account_id" int4 DEFAULT 0,"priv_owner" varchar ) WITH OIDS;
SELECT setval('mail_priv_accounts_ap_id_seq',1);
CREATE TABLE cc_popup_data ("id" serial NOT NULL PRIMARY KEY ,"type" varchar ,"typename" varchar ,"edate" date ,"descrip" varchar ,"amount" varchar ,"cdescrip" varchar ,"sdate" date ) WITH OIDS;
SELECT setval('cc_popup_data_id_seq',1);
CREATE TABLE stockcat ("catid" serial NOT NULL PRIMARY KEY ,"catcod" varchar ,"cat" varchar ,"descript" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
SELECT setval('stockcat_catid_seq',1);
CREATE TABLE callout_docs_data ("calloutid" numeric DEFAULT 0,"dept" varchar ,"customer" varchar ,"addr1" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
CREATE TABLE rbs ("id" serial NOT NULL PRIMARY KEY ,"name" varchar ,"account" int4 DEFAULT 0,"div" int4 DEFAULT 0) WITH OIDS;
SELECT setval('rbs_id_seq',1);
CREATE TABLE equip_rented ("id" serial NOT NULL PRIMARY KEY ,"hire_id" numeric DEFAULT 0,"user_id" numeric DEFAULT 0,"asset_id" numeric DEFAULT 0,"timestamp" timestamp DEFAULT now(),"qty" numeric DEFAULT 0,"cost_price" numeric(16, 2) DEFAULT 0,"selling_price" numeric(16, 2) DEFAULT 0,"description" varchar ) WITH OIDS;
SELECT setval('equip_rented_id_seq',1);
CREATE TABLE pinv_items ("invid" numeric DEFAULT 0,"whid" numeric DEFAULT 0,"stkid" numeric DEFAULT 0,"qty" numeric DEFAULT 0,"ss" varchar ,"div" numeric DEFAULT 0,"amt" numeric(16, 2) DEFAULT 0,"discp" numeric(16, 2) DEFAULT 0,"disc" numeric(16, 2) DEFAULT 0,"unitcost" numeric(16, 2) DEFAULT 0,"noted" numeric DEFAULT 0,"serno" varchar ,"vatcode" int4 DEFAULT 0,"description" varchar ,"account" int4 DEFAULT 0) WITH OIDS;
CREATE TABLE customers ("cusnum" serial NOT NULL PRIMARY KEY ,"accno" varchar ,"surname" varchar ,"title" varchar ,"init" varchar ,"cusname" varchar ,"category" numeric DEFAULT 0,"class" numeric DEFAULT 0,"paddr1" varchar ,"paddr2" varchar ,"paddr3" varchar ,"addr1" varchar ,"addr2" varchar ,"addr3" varchar ,"contname" varchar ,"bustel" varchar ,"tel" varchar ,"cellno" varchar ,"fax" varchar ,"email" varchar ,"saleterm" numeric DEFAULT 0,"traddisc" numeric DEFAULT 0,"setdisc" numeric DEFAULT 0,"pricelist" numeric DEFAULT 0,"chrgint" varchar ,"overdue" numeric DEFAULT 0,"chrgvat" varchar ,"vatinc" varchar ,"credterm" numeric DEFAULT 0,"odate" date ,"credlimit" numeric DEFAULT 0,"blocked" varchar ,"deptid" numeric DEFAULT 0,"vatnum" varchar ,"div" numeric DEFAULT 0,"url" varchar ,"ddiv" numeric DEFAULT 0,"intrate" numeric DEFAULT 0,"balance" numeric(16, 2) DEFAULT 0,"day30" numeric(16, 2) DEFAULT 0,"day60" numeric(16, 2) DEFAULT 0,"day90" numeric(16, 2) DEFAULT 0,"day120" numeric(16, 2) DEFAULT 0,"classname" varchar ,"catname" varchar ,"deptname" varchar ,"fbalance" numeric(16, 2) DEFAULT 0,"fcid" numeric DEFAULT 0,"location" varchar ,"currency" varchar ,"lead_source" varchar ,"comments" varchar ,"add1" varchar ,"add2" varchar ,"del_addr1" varchar ,"sales_rep" varchar DEFAULT 0,"bankname" varchar ,"branname" varchar ,"brancode" varchar ,"bankaccno" varchar ,"bankaccname" varchar ,"team_id" numeric DEFAULT 0,"registration" varchar DEFAULT 0,"bankacctype" varchar ,"bankacct" numeric DEFAULT 0,"bankid" numeric DEFAULT 0,"setdays" numeric DEFAULT 0,"units" numeric DEFAULT 0) WITH OIDS;
SELECT setval('customers_cusnum_seq',1);
CREATE TABLE eimgs ("id" serial NOT NULL PRIMARY KEY ,"emp" int4 DEFAULT 0,"image" text ,"imagetype" varchar ) WITH OIDS;
SELECT setval('eimgs_id_seq',1);
CREATE TABLE sc_popup_data ("id" serial NOT NULL PRIMARY KEY ,"type" varchar ,"typename" varchar ,"edate" date ,"descrip" varchar ,"amount" varchar ,"cdescrip" varchar ,"cosamt" varchar ,"sdate" date ) WITH OIDS;
SELECT setval('sc_popup_data_id_seq',1);
CREATE TABLE dispatch_scans ("id" serial NOT NULL PRIMARY KEY ,"sordid" numeric DEFAULT 0,"timestamp" timestamp ,"userid" numeric DEFAULT 0,"duplicate" numeric DEFAULT 0,"dispatch_type" varchar ) WITH OIDS;
SELECT setval('dispatch_scans_id_seq',1);
CREATE TABLE pcnc ("id" serial NOT NULL PRIMARY KEY ,"note" int4 DEFAULT 0,"amount" numeric(16, 2) DEFAULT 0) WITH OIDS;
SELECT setval('pcnc_id_seq',1);
CREATE TABLE creditors ("invdate" date ,"supcod" varchar ,"supnme" varchar ,"suptel" varchar ,"supfax" varchar ,"supeml" varchar ,"contact" varchar ,"supinv" varchar ,"terms" varchar ,"disc" numeric DEFAULT 0,"amtdue" numeric DEFAULT 0,"div" numeric DEFAULT 0) WITH OIDS;
CREATE TABLE users ("userid" serial NOT NULL PRIMARY KEY ,"username" varchar ,"password" varchar ,"div" numeric DEFAULT 0,"admin" int4 DEFAULT 0,"services_menu" varchar ,"abo" int4 DEFAULT 0,"help" varchar ,"cur_prd_db" varchar ,"state" varchar ,"prddb" varchar ,"prdname" varchar ,"blocktime" varchar ,"loginseq" int8 DEFAULT 0,"sales_rep" numeric DEFAULT 0,"locale" varchar ,"locale_enable" varchar DEFAULT 'disabled'::character varying,"usertype" varchar ,"barcode" varchar ,"whid" numeric DEFAULT 0,"empnum" numeric DEFAULT 0,"payroll_groups" varchar DEFAULT ''::character varying) WITH OIDS;
SELECT setval('users_userid_seq',2);
INSERT INTO users ("userid","username","password","div","admin","services_menu","abo","help","cur_prd_db","state","prddb","prdname","blocktime","loginseq","sales_rep","locale","locale_enable","usertype","barcode","whid","empnum","payroll_groups") VALUES('2','admin','202cb962ac59075b964b07152d234b70','2','1','L','0','Yes','','','','','','0','0','en_ZA','disabled','','','0','0','');
CREATE TABLE dispatch_how ("id" serial NOT NULL PRIMARY KEY ,"name" varchar ) WITH OIDS;
SELECT setval('dispatch_how_id_seq',1);
CREATE TABLE supp_payment_print ("id" serial NOT NULL PRIMARY KEY ,"supid" numeric DEFAULT 0,"account" numeric DEFAULT 0,"pay_date" date ,"sdate" date ,"refno" varchar DEFAULT ''::character varying,"cheqno" varchar DEFAULT 0,"total_amt" numeric(16, 2) DEFAULT 0,"set_amt" numeric(16, 2) DEFAULT 0,"overpay_amt" numeric(16, 2) DEFAULT 0,"descript" varchar DEFAULT ''::character varying) WITH OIDS;
SELECT setval('supp_payment_print_id_seq',1);
CREATE TABLE suppstock ("id" serial NOT NULL PRIMARY KEY ,"suppid" int4 DEFAULT 0,"stkid" int4 DEFAULT 0,"stkcod" varchar ,"onhand" numeric DEFAULT 0) WITH OIDS;
SELECT setval('suppstock_id_seq',1);
CREATE TABLE ms ("id" serial NOT NULL PRIMARY KEY ,"set" varchar ,"val" varchar ) WITH OIDS;
SELECT setval('ms_id_seq',1);
CREATE TABLE serial7 ("stkid" numeric DEFAULT 0,"serno" varchar ,"rsvd" varchar ,"warranty" date ) WITH OIDS;
CREATE TABLE statement_refs ("id" serial NOT NULL PRIMARY KEY ,"ref" varchar ,"dets" varchar ,"pn" varchar ,"action" varchar ,"account" int4 DEFAULT 0,"by" varchar ) WITH OIDS;
SELECT setval('statement_refs_id_seq',1);
CREATE TABLE blah ("blah" int4 DEFAULT 0) WITH OIDS;
CREATE TABLE public_holidays ("id" serial NOT NULL PRIMARY KEY ,"holiday_name" varchar ,"holiday_type" varchar ,"holiday_date" date ) WITH OIDS;
SELECT setval('public_holidays_id_seq',1);
CREATE TABLE suppliers ("supid" serial NOT NULL PRIMARY KEY ,"supno" varchar ,"supname" varchar ,"supaddr" varchar ,"contname" varchar ,"tel" varchar ,"fax" varchar ,"email" varchar ,"bankname" varchar ,"branname" varchar ,"brancode" varchar ,"bankaccno" varchar ,"deptid" numeric DEFAULT 0,"vatnum" varchar ,"div" numeric DEFAULT 0,"url" varchar ,"ddiv" numeric DEFAULT 0,"blocked" varchar ,"balance" numeric(16, 2) DEFAULT 0,"listid" numeric DEFAULT 0,"fbalance" numeric(16, 2) DEFAULT 0,"fcid" numeric DEFAULT 0,"location" varchar ,"currency" varchar ,"lead_source" varchar ,"comments" varchar ,"branch" varchar ,"groupid" numeric DEFAULT 0,"reference" varchar ,"bee_status" varchar ,"bee_training" varchar ,"team_id" numeric DEFAULT 0,"registration" varchar DEFAULT 0,"bankaccname" varchar ,"bankacctype" varchar ,"setdisc" numeric(16, 2) DEFAULT 0,"setdays" numeric DEFAULT 0,"pay_type" varchar DEFAULT ''::character varying,"suppostaddr" varchar DEFAULT ''::character varying,"cell" varchar DEFAULT ''::character varying) WITH OIDS;
SELECT setval('suppliers_supid_seq',1);
CREATE TABLE bursaries ("id" serial NOT NULL PRIMARY KEY ,"bursary_name" varchar ,"bursary_details" varchar ,"date_added" date ,"used" varchar ) WITH OIDS;
SELECT setval('bursaries_id_seq',1);
CREATE TABLE cons_img ("id" serial NOT NULL PRIMARY KEY ,"con_id" numeric DEFAULT 0,"type" varchar ,"file" varchar ,"size" varchar ) WITH OIDS;
SELECT setval('cons_img_id_seq',1);
CREATE TABLE pos_trans_airtime ("id" serial NOT NULL PRIMARY KEY ,"trans_id" numeric DEFAULT 0,"hire_trans_id" numeric DEFAULT 0,"prod_code" varchar ,"cost_price" numeric(16, 2) DEFAULT 0,"serial" varchar ,"pin" varchar ,"batch" varchar ,"expiry" varchar ,"printed" numeric DEFAULT 0) WITH OIDS;
SELECT setval('pos_trans_airtime_id_seq',1);
CREATE TABLE diary_entries_details ("entry_id" serial NOT NULL PRIMARY KEY ,"username" varchar ,"department_name" varchar ,"setting" varchar ) WITH OIDS;
SELECT setval('diary_entries_details_entry_id_seq',1);
CREATE TABLE supp_db_questions ("id" serial NOT NULL PRIMARY KEY ,"heading" text ,"content" text ) WITH OIDS;
SELECT setval('supp_db_questions_id_seq',377);
INSERT INTO supp_db_questions ("id","heading","content") VALUES('1','','');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('2','','');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('3','','');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('4','','');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('5','','');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('6','','');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('7','','');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('8','','');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('9','','');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('10','','');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('11','','');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('12','','');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('13','','');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('14','','');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('15','','');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('16','','');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('17','','');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('18','','');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('19','','');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('20','','');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('21','','');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('22','','');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('23','Welcome To Cubit Accounting','We are proud to release this 2.7. version of Cubit Accounting and business management software.
Cubit can be downloaded from www.cubit.co.za. As you know with Cubit there are no watered down
functionality, additional modules or per user fees for use by your business on your network. You may
also provide access for your company over the Internet and Cubit is a complete network multi user
system.
Please study this guide carefully as there are very useful tips, guidelines and information contained
herein that could be of considerable use for you and your company.
Also remember that all Cubit users have complete access to e-mail support, complete updates to all
future versions and regular accounting and regulatory updates. telephonic support is also available so
our clients do not have to pay for services they do not require or ever use.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('24','RESEARCH AND DEVELOPMENT','Since 2001 we have been developing Cubit by consulting with leading experts on an ongoing basis,
stringent research and development and an open door client policy.
Our research and development team constantly develops new processes, perform implementations at
test sites and undertake rigorous software and human interface testing, giving Cubit a level of software
maturity beyond its developmental years. This tireless and continuing research and developmental
process underpins our dedication to being the network accounting and business system of choice');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('25','REGISTERING AND USING CUBIT ACCOUNTING SOFTWARE','Cubit software has no purchase price and only a small monthly usage cost, this cost already includes e-
mail support, updates and even VAT. There are also packages including telephonic support and
unlimited telephonic support.
Please register your usage of the software by calling us on 086 100 4674 during office hours or sending
an e-mail to support@cubit.co.za
If you are not providing Cubit as a service to others there are no per user costs and your company ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('26','REGISTERING AND USING CUBIT ACCOUNTING SOFTWARE','Cubit software has no purchase price and only a small monthly usage cost, this cost already includes e-
mail support, updates and even VAT. There are also packages including telephonic support and
unlimited telephonic support.
Please register your usage of the software by calling us on 086 100 4674 during office hours or sending
an e-mail to support@cubit.co.za
If you are not providing Cubit as a service to others there are no per user costs and your company
could have one or 50 users without paying a single cent additionally.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('27','IMPORTANT USAGE AND PRODUCT INFORMATION','Because it is legal to copy and distribute Cubit you have to be very aware of where you receive the
software.
Please make sure that you receive the software from a source that you trust.
Please make sure that you have consulted us or our dealer before making the software available over
the Internet.
Please do not install updates not obtained from us.
Please backup your data regularly.
Cubit uses very strong encryption which may not be legal in certain countries. If you lose your
generated system code or keys we (or anyone else) CANNOT recover your data.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('28','DATA AND SECURITY','Please backup regularly.
Please record your key (available under the admin section) for any communication with Cubit.
Please configure a security certificate and HTTPS access for remote (Internet) usage and use the built
in .htaccess functionality as well. You should have your webserver and operating system hardened by
an IT professional if your cubit system is connected to a network.
Please consult a qualified Information Technology security practitioner.
CUBIT USES STRONG ENCRYPTION (ELECTRONIC COMMUNICATIONS AND
TRANSACTIONS (ECT) ACT 25 OF 2002)
ELECTRONIC COMMUNICATIONS AND TRANSACTIONS (ECT) ACT 25 OF 2002 regulates the
usage of encryption products. Please consult with a legal practitioner if relevant.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('29','CONTACTING DEALERS','Please contact our dealer geographically located closest to you. Our dealer will be able to assist with
virus, hardware and other functions. The dealer will also be able to advise you of the latest versions,
updates and provide you with documentation, the import of data from other systems and consult on
your Cubit questions.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('30','SERVICE SUPPORT AND MAINTENANCE','As a Cubit user you will routinely receive business information from our mailing lists, we will inform
you when updates become available and advise regarding modules and special versions of the software.
Please consult our dealers regarding regular off-site backups of your data and the other services they
provide');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('31','SYSTEM REQUIRMENTS','Tested operating systems: ANY Linux, BSD, Windows 2000XP (Copyright Microsoft Corporation)
Tested Hardware:
Single User:
Minimum: AMDIntel P3, 128MB RAM, 10 GB HDD
Advised: AMD AthlonIntel P4, 512MB RAM, 40GB HDD');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('32','CUBIT CONTACT INFORMATION','National Call Center : 086 100-4674
Randburg Head Office : (011) 781-8324
Fax Number : 086 683-0711
e-mail: support@cubit.co.za
Website : http:www.cubit.co.za');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('33',' HOW TO GET THE MOST OUT OF YOUR RELATIONSHIP WITH
CUBIT:','1.When calling us have your client number (In the Register Cubit screen) ready in case the operator
requires it to enable specific support.
2.Remember that there is a difference between general accountancy support and supporting the Cubit
Accounting Software Application and should you require training. Training is available in your
geographic area, please contact sales@cubit.co.za
3.Please call between the hours of 09H00 and 17H00 Monday to Friday. We do not have an answering
service. If you are outside South Africa please check our website for your telephonic support numbers.
4.You are our client and we work for you. Without you we will have no business.
5.You are calling an 0861 number. This means you only pay for a local call. We do not phone clients
back for support so if your preferred consultant is not available please call again at the time supplied by
the consultant you speak to. If we have to call you back and the agent that we speak to is in Cape
Town, Johannesburg or Durban we will end up paying for a trunk call. It is much cheaper for you to
call us back. If you try to leave a message it may be very long time before someone calls you back.
6.What generally happens in practice is that 1000 people decide to call at exactly the same time. If our
lines are busy or the operator you wish to speak to is not available, please be patient with us and
remember that your business is very important to us. We really want to give you excellent service,
please call us back in 30 minutes after the rush has passed.
7.Remember that most common issues are already published on the website and it may save you time
and effort (and money!) to check the website and see if your question has an simple answer.
8.Any consultant that answers the phone will be able to assist you immediately. We do not have
receptionists or various departments that you need to be transferred to. Our consultants have a
minimum of a B.COM Accounting degree and are able to immediately assess your situation and
provide you with an answer. Should you not like the answer you receive you can escalate your question
to andre@cubit.co.za
9.Try to register your software over e-mail or from within Cubit itself because there are many
difficulties when reading long numbers back and forth on the telephone.
');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('34',' HOW TO GET THE MOST OUT OF YOUR RELATIONSHIP WITH
CUBIT:','1.When calling us have your client number (In the Register Cubit screen) ready in case the operator
requires it to enable specific support.
2.Remember that there is a difference between general accountancy support and supporting the Cubit
Accounting Software Application and should you require training. Training is available in your
geographic area, please contact sales@cubit.co.za
3.Please call between the hours of 09H00 and 17H00 Monday to Friday. We do not have an answering
service. If you are outside South Africa please check our website for your telephonic support numbers.
4.You are our client and we work for you. Without you we will have no business.
5.You are calling an 0861 number. This means you only pay for a local call. We do not phone clients
back for support so if your preferred consultant is not available please call again at the time supplied by
the consultant you speak to. If we have to call you back and the agent that we speak to is in Cape
Town, Johannesburg or Durban we will end up paying for a trunk call. It is much cheaper for you to
call us back. If you try to leave a message it may be very long time before someone calls you back.
6.What generally happens in practice is that 1000 people decide to call at exactly the same time. If our
lines are busy or the operator you wish to speak to is not available, please be patient with us and
remember that your business is very important to us. We really want to give you excellent service,
please call us back in 30 minutes after the rush has passed.
7.Remember that most common issues are already published on the website and it may save you time
and effort (and money!) to check the website and see if your question has an simple answer.
8.Any consultant that answers the phone will be able to assist you immediately. We do not have
receptionists or various departments that you need to be transferred to. Our consultants have a
minimum of a B.COM Accounting degree and are able to immediately assess your situation and
provide you with an answer. Should you not like the answer you receive you can escalate your question
to andre@cubit.co.za
9.Try to register your software over e-mail or from within Cubit itself because there are many
difficulties when reading long numbers back and forth on the telephone.
');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('35','WHAT DOES CUBIT ACCOUNTING SOFTWARE OFFER APART FROM
THE SOFTWARE?','1. Call out to client premises is generally billed at R385 (excluding VAT) per hour and travel time is included in the calculation of billable time but not distance. Complete reference manual is available at no additional cost and can be downloaded from the www.cubit.co.za website or found on the Cubit disks. There are also 5 minute and 10 minute videos available on the disks at no additional costs. Installation is available from our IT dealer network and on average costs around R1000 but does vary from dealer to dealer. Remote Internet based support and services (including the installation of updates and regular Internet backups for non ASP vendors) are billed at R185 per hour or portion thereof. We will need you to order such support via e-mail from sales@cubit.co.za VIII Platinum personalized telephonic support is available. This entitles you to receive up to three cellular telephone numbers for three consultants that you usually speak to. (You may elect to change one of your personal consultants without specifying any reasons at any time.) Effectively this means you will be able to speak to a qualified and professional consultant immediately between the hours of 08H00 and 20H00 Monday to Friday and on Saturdays from 08H30 to 13H00. Costs for this service varies depending on qualification levels required and starts from R980 per month (Excl. VAT). On this service you may also leave messages (on for example a Sunday) and one of your consultants will call you back. Minimum contract for this service is six months and the full contract amount is payable in advance. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('36','WHAT DOES CUBIT ACCOUNTING SOFTWARE OFFER APART FROM
THE SOFTWARE?','1. Call out to client premises is generally billed at R385 (excluding VAT) per hour and travel time is included in the calculation of billable time but not distance. Complete reference manual is available at no additional cost and can be downloaded from the www.cubit.co.za website or found on the Cubit disks. There are also 5 minute and 10 minute videos available on the disks at no additional costs. Installation is available from our IT dealer network and on average costs around R1000 but does vary from dealer to dealer. Remote Internet based support and services (including the installation of updates and regular Internet backups for non ASP vendors) are billed at R185 per hour or portion thereof. We will need you to order such support via e-mail from sales@cubit.co.za VIII Platinum personalized telephonic support is available. This entitles you to receive up to three cellular telephone numbers for three consultants that you usually speak to. (You may elect to change one of your personal consultants without specifying any reasons at any time.) Effectively this means you will be able to speak to a qualified and professional consultant immediately between the hours of 08H00 and 20H00 Monday to Friday and on Saturdays from 08H30 to 13H00. Costs for this service varies depending on qualification levels required and starts from R980 per month (Excl. VAT). On this service you may also leave messages (on for example a Sunday) and one of your consultants will call you back. Minimum contract for this service is six months and the full contract amount is payable in advance. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('37','WHAT DOES CUBIT ACCOUNTING SOFTWARE OFFER APART FROM
THE SOFTWARE?','1. Call out to client premises is generally billed at R385 (excluding VAT) per hour and travel time is included in the calculation of billable time but not distance. Complete reference manual is available at no additional cost and can be downloaded from the www.cubit.co.za website or found on the Cubit disks. There are also 5 minute and 10 minute videos available on the disks at no additional costs. Installation is available from our IT dealer network and on average costs around R1000 but does vary from dealer to dealer. Remote Internet based support and services (including the installation of updates and regular Internet backups for non ASP vendors) are billed at R185 per hour or portion thereof. We will need you to order such support via e-mail from sales@cubit.co.za VIII Platinum personalized telephonic support is available. This entitles you to receive up to three cellular telephone numbers for three consultants that you usually speak to. (You may elect to change one of your personal consultants without specifying any reasons at any time.) Effectively this means you will be able to speak to a qualified and professional consultant immediately between the hours of 08H00 and 20H00 Monday to Friday and on Saturdays from 08H30 to 13H00. Costs for this service varies depending on qualification levels required and starts from R980 per month (Excl. VAT). On this service you may also leave messages (on for example a Sunday) and one of your consultants will call you back. Minimum contract for this service is six months and the full contract amount is payable in advance. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('38','WHAT DOES CUBIT ACCOUNTING SOFTWARE OFFER APART FROM
THE SOFTWARE?','1. Call out to client premises is generally billed at R385 (excluding VAT) per hour and travel time is included in the calculation of billable time but not distance. Complete reference manual is available at no additional cost and can be downloaded from the www.cubit.co.za website or found on the Cubit disks. There are also 5 minute and 10 minute videos available on the disks at no additional costs. Installation is available from our IT dealer network and on average costs around R1000 but does vary from dealer to dealer. Remote Internet based support and services (including the installation of updates and regular Internet backups for non ASP vendors) are billed at R185 per hour or portion thereof. We will need you to order such support via e-mail from sales@cubit.co.za VIII Platinum personalized telephonic support is available. This entitles you to receive up to three cellular telephone numbers for three consultants that you usually speak to. (You may elect to change one of your personal consultants without specifying any reasons at any time.) Effectively this means you will be able to speak to a qualified and professional consultant immediately between the hours of 08H00 and 20H00 Monday to Friday and on Saturdays from 08H30 to 13H00. Costs for this service varies depending on qualification levels required and starts from R980 per month (Excl. VAT). On this service you may also leave messages (on for example a Sunday) and one of your consultants will call you back. Minimum contract for this service is six months and the full contract amount is payable in advance. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('39','GETTING STARTED
Microsoft Windows specific :','Install for Windows 2000XP (Copyright Microsoft Corporation)
1. Turn off all anti-virus and firewalls.
2. Insert the CD into the CD ROM or DVD drive
3. If it doesnt start itself (within a few seconds), go to My Computer  CDDVD and double
click Setup
4. The Cubit License appears. Read the license then tick the acceptance box and click next
5. Select the designation folder where Cubit will be installed and click install
6. Wait for the installation to complete (could be up to 45 minutes)
7. Once Installation is complete Restart the computer.
8. If it is not possible to restart the computer: Click on START  Programs  Cubit  Start
Server
9. A black window will open after a few seconds it will prompt you to Press any key to
continue, press enter and the window will close.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('40','Linux specific :','Install for Linux
Mount the cubit CDROM (most Linux distributions should do this automatically)
Copy the cubit install file (in the Linux folder of the CDROM) to your hard drive.
Extract the installation file using tar xzpf xxxxxxxx
A Cubit folder will then be made in the present directory
Open that folder by typing cd Cubit
Run the installation by typing .install.sh
The following screens will be displayed:
First screen:
Cubit installation start ... we will attempt to automatically install cubit for you...
Click Ok
Second screen:
Select your type of installation (with regards to security)
https: secure version of apache using certificates
http:
standard version (does not use ssl certificates)
Third screen:
Installing PHPApache, Cubit engine support <------------------ Waiting screen (installing PHPApache)
Fourth screen
Unpacking Postgres <---------------------Another waiting screen
Fifth screen
Preparing to install Postgres <---------- Another waiting screen
Sixth screen
Compiling postgresql <------------------ Postgres is compiled during this waiting screen, this may take
some time (5 - 10 min)
Seventh screen
Installing postgres <-----------------------Waiting screen while Postgres is installed
Eighth screen
Installing cubit <----------------------------Installing scripts etc
If a running copy of Postgres is detected at this time, the installation will ask you to shut it down
before continuing, press Ok to continue installing.
After installation is complete, please remove the Postgres entry from your Linux runlevel (a runlevel
option can be found in most Linux distribution setup tools, eg. Yast in SUSE Linux).
Cubit will automatically add an entry for its own copy of Postgres.
Ninth screen
Cubit is now successfully installed. Click Ok.
User can then either reboot to start using cubit or execute .usrlocalcubitstart_cubit.sh for
immediate access.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('41','Linux specific :','Install for Linux
Mount the cubit CDROM (most Linux distributions should do this automatically)
Copy the cubit install file (in the Linux folder of the CDROM) to your hard drive.
Extract the installation file using tar xzpf xxxxxxxx
A Cubit folder will then be made in the present directory
Open that folder by typing cd Cubit
Run the installation by typing .install.sh
The following screens will be displayed:
First screen:
Cubit installation start ... we will attempt to automatically install cubit for you...
Click Ok
Second screen:
Select your type of installation (with regards to security)
https: secure version of apache using certificates
http:
standard version (does not use ssl certificates)
Third screen:
Installing PHPApache, Cubit engine support <------------------ Waiting screen (installing PHPApache)
Fourth screen
Unpacking Postgres <---------------------Another waiting screen
Fifth screen
Preparing to install Postgres <---------- Another waiting screen
Sixth screen
Compiling postgresql <------------------ Postgres is compiled during this waiting screen, this may take
some time (5 - 10 min)
Seventh screen
Installing postgres <-----------------------Waiting screen while Postgres is installed
Eighth screen
Installing cubit <----------------------------Installing scripts etc
If a running copy of Postgres is detected at this time, the installation will ask you to shut it down
before continuing, press Ok to continue installing.
After installation is complete, please remove the Postgres entry from your Linux runlevel (a runlevel
option can be found in most Linux distribution setup tools, eg. Yast in SUSE Linux).
Cubit will automatically add an entry for its own copy of Postgres.
Ninth screen
Cubit is now successfully installed. Click Ok.
User can then either reboot to start using cubit or execute .usrlocalcubitstart_cubit.sh for
immediate access.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('42','Linux specific :','Install for Linux
Mount the cubit CDROM (most Linux distributions should do this automatically)
Copy the cubit install file (in the Linux folder of the CDROM) to your hard drive.
Extract the installation file using tar xzpf xxxxxxxx
A Cubit folder will then be made in the present directory
Open that folder by typing cd Cubit
Run the installation by typing .install.sh
The following screens will be displayed:
First screen:
Cubit installation start ... we will attempt to automatically install cubit for you...
Click Ok
Second screen:
Select your type of installation (with regards to security)
https: secure version of apache using certificates
http:
standard version (does not use ssl certificates)
Third screen:
Installing PHPApache, Cubit engine support <------------------ Waiting screen (installing PHPApache)
Fourth screen
Unpacking Postgres <---------------------Another waiting screen
Fifth screen
Preparing to install Postgres <---------- Another waiting screen
Sixth screen
Compiling postgresql <------------------ Postgres is compiled during this waiting screen, this may take
some time (5 - 10 min)
Seventh screen
Installing postgres <-----------------------Waiting screen while Postgres is installed
Eighth screen
Installing cubit <----------------------------Installing scripts etc
If a running copy of Postgres is detected at this time, the installation will ask you to shut it down
before continuing, press Ok to continue installing.
After installation is complete, please remove the Postgres entry from your Linux runlevel (a runlevel
option can be found in most Linux distribution setup tools, eg. Yast in SUSE Linux).
Cubit will automatically add an entry for its own copy of Postgres.
Ninth screen
Cubit is now successfully installed. Click Ok.
User can then either reboot to start using cubit or execute .usrlocalcubitstart_cubit.sh for
immediate access.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('43','Linux specific :','Install for Linux
Mount the cubit CDROM (most Linux distributions should do this automatically)
Copy the cubit install file (in the Linux folder of the CDROM) to your hard drive.
Extract the installation file using tar xzpf xxxxxxxx
A Cubit folder will then be made in the present directory
Open that folder by typing cd Cubit
Run the installation by typing .install.sh
The following screens will be displayed:
First screen:
Cubit installation start ... we will attempt to automatically install cubit for you...
Click Ok
Second screen:
Select your type of installation (with regards to security)
https: secure version of apache using certificates
http:
standard version (does not use ssl certificates)
Third screen:
Installing PHPApache, Cubit engine support <------------------ Waiting screen (installing PHPApache)
Fourth screen
Unpacking Postgres <---------------------Another waiting screen
Fifth screen
Preparing to install Postgres <---------- Another waiting screen
Sixth screen
Compiling postgresql <------------------ Postgres is compiled during this waiting screen, this may take
some time (5 - 10 min)
Seventh screen
Installing postgres <-----------------------Waiting screen while Postgres is installed
Eighth screen
Installing cubit <----------------------------Installing scripts etc
If a running copy of Postgres is detected at this time, the installation will ask you to shut it down
before continuing, press Ok to continue installing.
After installation is complete, please remove the Postgres entry from your Linux runlevel (a runlevel
option can be found in most Linux distribution setup tools, eg. Yast in SUSE Linux).
Cubit will automatically add an entry for its own copy of Postgres.
Ninth screen
Cubit is now successfully installed. Click Ok.
User can then either reboot to start using cubit or execute .usrlocalcubitstart_cubit.sh for
immediate access.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('44','GETTING STARTED','1. Double click on the Cubit Icon on the desktop
2. You can select NO if it asks whether to make Mozilla your default browser and tick Dont ask
again
3. Click on Open Cubit
4. Enter your company name, confirm and wait for setup.
5. When you LOG IN the screen on the right contains more information please read it. The
default username and password is case sensitive so username is Root and password is
0123456789
6. There is an option to install FirefoxMozilla browser Cubit functions best with Mozilla
browser
7. You should now be logged into your company as the user Root. Click on Admin Settings Quick Setup
8. Select the month in which your financial year starts (usually March), the month that you are
going to start capturing data for (current month), and the financial year you are capturing the
data for.
9. Cubit will automatically create default ledger accounts for you when doing the Quick Setup. If
you need to add any additional ledger accounts in the future, follow these steps: Go to Settings
Account Settings Add new account and select the type of account you want to add. At the
Quick Links is an option to List All Accounts which will display a list of all your current
ledger accounts so that you can check for the availability of account numbers. Close the
window with the list of accounts to continue entering the information for you new account.
10.Click on Admin Company Details enter you company details
11.Click on Accounting ViewEdit bank account and Select the Edit option and change the
details to your company banking details. If you want to add any additional bank accounts go to
Accounting Add Bank Account');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('45','GETTING STARTED','1. Double click on the Cubit Icon on the desktop
2. You can select NO if it asks whether to make Mozilla your default browser and tick Dont ask
again
3. Click on Open Cubit
4. Enter your company name, confirm and wait for setup.
5. When you LOG IN the screen on the right contains more information please read it. The
default username and password is case sensitive so username is Root and password is
0123456789
6. There is an option to install FirefoxMozilla browser Cubit functions best with Mozilla
browser
7. You should now be logged into your company as the user Root. Click on Admin Settings Quick Setup
8. Select the month in which your financial year starts (usually March), the month that you are
going to start capturing data for (current month), and the financial year you are capturing the
data for.
9. Cubit will automatically create default ledger accounts for you when doing the Quick Setup. If
you need to add any additional ledger accounts in the future, follow these steps: Go to Settings
Account Settings Add new account and select the type of account you want to add. At the
Quick Links is an option to List All Accounts which will display a list of all your current
ledger accounts so that you can check for the availability of account numbers. Close the
window with the list of accounts to continue entering the information for you new account.
10.Click on Admin Company Details enter you company details
11.Click on Accounting ViewEdit bank account and Select the Edit option and change the
details to your company banking details. If you want to add any additional bank accounts go to
Accounting Add Bank Account');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('46','GETTING STARTED','1. Double click on the Cubit Icon on the desktop
2. You can select NO if it asks whether to make Mozilla your default browser and tick Dont ask
again
3. Click on Open Cubit
4. Enter your company name, confirm and wait for setup.
5. When you LOG IN the screen on the right contains more information please read it. The
default username and password is case sensitive so username is Root and password is
0123456789
6. There is an option to install FirefoxMozilla browser Cubit functions best with Mozilla
browser
7. You should now be logged into your company as the user Root. Click on Admin Settings Quick Setup
8. Select the month in which your financial year starts (usually March), the month that you are
going to start capturing data for (current month), and the financial year you are capturing the
data for.
9. Cubit will automatically create default ledger accounts for you when doing the Quick Setup. If
you need to add any additional ledger accounts in the future, follow these steps: Go to Settings
Account Settings Add new account and select the type of account you want to add. At the
Quick Links is an option to List All Accounts which will display a list of all your current
ledger accounts so that you can check for the availability of account numbers. Close the
window with the list of accounts to continue entering the information for you new account.
10.Click on Admin Company Details enter you company details
11.Click on Accounting ViewEdit bank account and Select the Edit option and change the
details to your company banking details. If you want to add any additional bank accounts go to
Accounting Add Bank Account');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('47','Getting Started 2','1. The system is a NETWORK enabled system so other users can connect to the system as
follows:
 Firefox must be installed on each client computer. Firefox can be downloaded from
http:www.getfirefox.com.
 In windows(Copyright Microsoft Corporation), determine the server IP address (Ask your
computer consultant to assist you) then all the systems connected to the server must type in the
this IP address in their web browser eg. http:192.168.1.10 and they will automatically be able
to log into the system. For ease of use set the web browsers homepage as the servers IP
address. After entering the servers IP address you will be asked whether you want to import,
select the option do not import anything.
 You should create user accounts at Admin  User Accounts and select the functionality the
user must have access to.
 Once user accounts have been created, these users can log in using their own usernames and
passwords instead of using the default user Root and its password 0123456789
2. You can enter all the details of your customers, suppliers, stock items and employees by
selecting the applicable section and the Add option. If you need to enter. If you need to enter
the details of a customer for example, go to Debtors  Add Customer and enter the details
for that customer.
You are now ready to use Cubit.
2.2. Migrating to Cubit from other software:
Cubit does not function similarly to other accounting systems
Cubit is a business management system which is fully integrated with accounting, payroll, groupware,
mail exchange software, customer relationship management and much more. To this end Cubit
sometimes requires more input datainformation (mostly for CAAT).
Migration to an integrated electronic network, like Cubit, with accounting and business information
management functionality is reasonably easy.
There are data import tools enabling the user to import data into the system.
Data such as a detailed trial balance, stock, customers and supplier information can be exported to the
required comma separated volume format and imported into Cubit (Please study the relevant sections
below for more detail on this process).
');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('48','Getting Started 2','1. The system is a NETWORK enabled system so other users can connect to the system as
follows:
 Firefox must be installed on each client computer. Firefox can be downloaded from
http:www.getfirefox.com.
 In windows(Copyright Microsoft Corporation), determine the server IP address (Ask your
computer consultant to assist you) then all the systems connected to the server must type in the
this IP address in their web browser eg. http:192.168.1.10 and they will automatically be able
to log into the system. For ease of use set the web browsers homepage as the servers IP
address. After entering the servers IP address you will be asked whether you want to import,
select the option do not import anything.
 You should create user accounts at Admin  User Accounts and select the functionality the
user must have access to.
 Once user accounts have been created, these users can log in using their own usernames and
passwords instead of using the default user Root and its password 0123456789
2. You can enter all the details of your customers, suppliers, stock items and employees by
selecting the applicable section and the Add option. If you need to enter. If you need to enter
the details of a customer for example, go to Debtors  Add Customer and enter the details
for that customer.
You are now ready to use Cubit.
2.2. Migrating to Cubit from other software:
Cubit does not function similarly to other accounting systems
Cubit is a business management system which is fully integrated with accounting, payroll, groupware,
mail exchange software, customer relationship management and much more. To this end Cubit
sometimes requires more input datainformation (mostly for CAAT).
Migration to an integrated electronic network, like Cubit, with accounting and business information
management functionality is reasonably easy.
There are data import tools enabling the user to import data into the system.
Data such as a detailed trial balance, stock, customers and supplier information can be exported to the
required comma separated volume format and imported into Cubit (Please study the relevant sections
below for more detail on this process).
');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('49','FREQUENTLY ASKED QUESTIONS','Running Cubit for the first time
Cubit will ask you to enter your company name, then a default user and password.
The default username is  Root
THIS IS CASE SENSITIVE TYPE CAPITAL R
The default password is  0123456789');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('50','FREQUENTLY ASKED QUESTIONS 2','Cubit then configures your company and after the setup is complete Cubit is ready to use.
Quick Setup option:
The quick setup option is used to setup Cubits basic settings so that Cubit can be used immediately. A
listing of all the settings Cubit will change will be shown, make sure this is correct as you will not be
able to change them thereafter.
If you do not wish to use the basic settings, you can go through the process manually.
There are certain standard things that any accounting package needs, the quick setup does this
automatically.
It is advised that first time users make use of the quick setup feature and manual set is NOT
RECOMMENDED.
Should you wish to adjust the chart of accounts etc. please select the edit or delete options after using
quick setup
After the quick setup has been completed Cubit will display a message that cubit is ready to be used.
Cubit is then ready to be used for Journals, Assets etc. The only other essential steps to complete are:
Adding Customers, Stock (if applicable to your business), Suppliers and Employees. Ask you dealer
about importing from other systems.
This is enough for the standard business, there are however a few settings under the Admin menu that
can be set for additional functionality or preferences if the user wishes to have them.
Editing users
The password can be changed by viewing user and then editing information for that user. If you want to
change the Users name or branch, editselect the new value in the field provided.
To change the password select the Change Password option and type the same password in both fields.
Q: What is the add new user field used for?
A: Your default user is Root , this user has Admin privileges, first off you need to change the password
for security reasons, then you need to add new users for each person that is going to use the system. If a
user with Admin privileges wanted to create a user for a sales rep, heshe would add a new user with
only sales privileges and this user would only be able to see the sales menu when logged in
When creating a user you can allowdeny access to specific area when they login with their username.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('51','Q: What is the add new user field used for?','A: Your default user is Root , this user has Admin privileges, first off you need to change the password
for security reasons, then you need to add new users for each person that is going to use the system. If a
user with Admin privileges wanted to create a user for a sales rep, heshe would add a new user with
only sales privileges and this user would only be able to see the sales menu when logged in
When creating a user you can allowdeny access to specific area when they login with their username.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('52','Q: In the edit user menu, which permissions need to be set for only basic functions?','A: That depends on what the specific user will be doing for the company.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('53','Q: Do these permissions cancel out the user permissions if used?','A: No');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('54','FAQ: Do these permissions cancel out the user permissions if used?','A: No
Select privileges you want to disableenable for this user, and click the Commit Changes button. On
this page you can select what the user may have access to.
The administrator option determines whether the user has administrator privileges or not.
Administrator privileges allows the user to do anything in Cubit, including add other Administrator
users. You still need to add all permissions and tick Administrator User for it to work.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('55','Q: Can I add one user to multiple departments?','A: The departments are just the different menus in Cubit, thus the administrator user belongs to all
departments.
User Entry Page
Select the branch for which for which you want to add the user. enter the user name for that user, enter
the password in both of the password fields. Click the Confirm button.
More about Journals');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('56','Q: What is a journal entry?','A: To know what journals are you need to know what the General Ledger is (the old T accounts you
learned of in school). A journal illustrates which ledger accounts were affected in any transaction.
Journals show which account was Debited and which was Credited.
If you do a Journal transaction you specify what account is Debited and what is Credited and with what
amount (and why). This information is then taken to the General Ledger (T accounts). Eg. You sell a
pen for R5 cash (forget VAT for the amount and ignore inventory), your journal will look like this.
Dr
Cr
Cash
5
Sales
5
pen sold for cash
This means that you now Debit the Cash account with R5, Credit the Sales account with R5, and the
transaction description is pen sold for cash.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('57','Error 1069 ',' (This error will be displayed in a blue pop up screen in the start the server screen)
Control Panel
- -> Administrative Tools
- -> Computer Management
- -> Click + next to Local UsersGroups (node on list at the left)
- -> Click on Users (node revealed by selecting Local UsersGroups)
- -> Double Click the postgres user on the list to the right
- -> Untick the User must change password at next logon option.
- -> Tick the password never expires option.
- -> Click OK button.
- -> Close everything opened during the course of this explanation.
- -> Start Cubit Services again and go accounting!!
Access to company');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('58','Q: When registering more than one company, is the access controlled by the companys password?','A: All companies are separate, the access to each company is controlled by the user for that specific
company.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('59','Q: Is the company info field necessary for any specific reason?','A: Yes, certain things like the VAT number etc. are necessary when producing invoices etc. The PAYE
number in turn is necessary for a valid payslip. The info you put in here is necessary if you use
Cubit to generate documents, but if you only do journal transactions you dont produce documents
so company info is not required.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('60','Q: If this is a new company, should i do anything in the Audit menu?','A: The Audit menu is purely for previous year transaction viewing. If its a new company you do not
have any previous year transactions.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('61','Q: If this is a new company, should i do anything in the Audit menu?','A: The Audit menu is purely for previous year transaction viewing. If its a new company you do not
have any previous year transactions.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('62','Q: When using high speed ledgers for transactions, can the details of the ledger be changed, or should a new one be created?','A: The information can be changed. View High Speed Input Ledgers and Edit.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('63','Q: When adding asset groups, how is the cost, depreciation amount calculated?','A: The cost is the amount paid for it excl. VAT (it can get a lot more complicated than this but thats the general idea). Depreciation amounts are determined by a number of factors: what is is, its
expected life span, how SARS allows you to depreciate (the accountant should know this)');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('64','Q: If there is an item in the todo list will the customer be notified?','A: No. The todo list will need to be opened in order to view previously entered tasks.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('65','Q: For queries the user must be assigned to a team, can any member view the entire list?','A: There are different departments in Cubit, under each department there are functions, also called
pages or scripts.
When you select a department option, all the functions under that department will be selected.
You can also scroll down and selectdeselect the actions to functions individually.
When you have selected a department option, you can also deselect some of the functions after this.
This helps when you wish to only disable one function in a department for a specific user. You can
simply select the department option, scroll down and deselect the function you want to disable.
User Listing
A list of all users will be displayed as well as the department they are in.
Next to each user you will see two options, remove and edit. The remove option will remove a user
from Cubit.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('66','Q: If the backup is restored, will the user be added again?','A: Yes, along with all the user data.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('67','Q: When is the end of my financial year?','A: When you register the company with the Registrar of Companies the end of the Financial Year is
specified. Most companies Financial Year End is 28 Feb.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('68','Q: How do periods work?','A: You have 12 periods in a year, they are basically the months of the year, but starting and ending
with your Financial Year. If the Financial year starts in March, then March is period one. When all
data has been entered for the March period, you need to close the period in the Admin menu so that
you can start capturing data for April.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('69','Q: Can more periods be added?','A: No. The periods cannot be divided into smaller periods, however future versions will be more
flexible.
');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('70','Q: When adding stock to a warehouse, does all the stock need to be assigned to a warehouse before a
price list can be added?','A: You cannot have stock without it belonging to a warehouse, so yes.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('71','Q: How do you transfer stock to a warehouse?','A: Under the Stock menu: Transfer Stock and follow the on-screen instructions.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('72','Q: When viewing the Trail Balance is it possible to disregard some information? Like the accounts
with zero balances.','A: Yes. There is an option just before it actually displays the Trial Balance to ignore zero values.
There is also a custom trial balance where you can do whatever you want, and show whatever you
want.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('73','Q: How do I add users?','A: Go to Admin, add user in the drop down menu. Fill in the users name and password and then select
which departments the user may have asses to.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('74','Q: How do I set it up so that I can sell items at different prices to different customers?','A: You do this by setting up different price lists and allocating them to your debtors.
To set up different price lists go to Admin, Settings, Stock, Add price list, in the drop down menu.
Then once you have added different price lists, when you add customers, you can add different price
lists for each one. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('75','Q: How do I transfer stock from one store to the another?','A: Go to Stock, transfer stock (store), in the drop down menu.
Before you can transfer stock between stores, you need to set up other stores. You do this under
(settings, stock, add store).
You will only be able to transfer stock that you have on hand. You cannot transfer negative stock. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('76','Q: How do I create an Invoice?','A: Go to Sales, invoice, new invoice in the drop down menu. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('77','Q: How do I create a Purchase?','A: Go to Purchases, Order, New Order from the drop down menu. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('78','Q: How can I change my password?','A: Go to Services, password. There you will be asked to fill in a new password and then to confirm it.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('79','Q: Does my Company nameLogo appear on the statementInvoices etc?','A: For the company Name and details to appear go to: Admin, company details. Fill in all the company
details. These will then appear on all your statementsinvoices etc.
A Logo can only be put on a Html: Browser set. Make sure logo is correct size.
Pdf and html.
Html- Cant determine exactly what it will look like(dont have control over) i.e- R on top of figure,
but can put company logo on.
pdf- Layout is better than Html, but as yet we cant put a company logo on it. Just the company
name and details will appear on the pdf.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('80','Q: How do I set Sales Rep, Commission?','A: Go to Settings, Sales settings, Add Sales Rep.
Once you have added a Sales Rep, you can then set their commission by going to Admin, Settings,
Sales, Set sales rep commission.
Commission can be set in 2 ways:: 1) % of each stock item.
2) % of everything he makes.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('81','Q: How do I add stock items excluding VAT?','A: Go to Admin, Settings, Stock, set selling price VAT type. Here you can select if you want ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('82','Q: How do I Find a dealer?','A: Go to our website www.cubit.co.za Click on find a dealer. You can select the area in which you
are looking for a dealer. You will then be presented with a list of all our dealers in that area, along
with their contact details.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('83','Q: Can we have a remote user accessing info as it is updated','A: Yes');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('84','Q: How do I get PHP recognized for this application. PHP works for everything else. ','A: Cubit conflicts with a standard installation of PHP as you are required to install additional modules.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('85','Q: I would like to know if I may include copies of the Cubit CD ROM in a magazine
and is it legal to copy Cubit for my friends?','A: Yes, anyone may distribute and copy Cubit. Usage is governed by the
OpenCubit License (Version 1.7 or later)');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('86','Q: After installation on windows and restarting, it appears that a new user has been created, postgres?','A: Yes this is correct. This is a Cubit System User and should not be used except by the system.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('87','Q: Does Cubit work on Windows98?','A: No.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('88','Q: How do I register Cubit?','A: Please click on the Register Cubit button (Admin-Register Cubit). Copy and paste the Client Side
Key and email it to us. You will then receive a reply to your email containing a number. 
Copy and paste this number into the blank space under the Client Side key. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('89','Q: Does Cubit support importing of other Accounting software data?','A: Yes, please read the import tools section in this manual.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('90','Q: I need help in setting up the mail services on cubit.
I have created an office and added users, but cannot connect from a client PC to the mail server. ','A: Make sure that the machine where Cubit is installed is connected to the Internet. Then enter the
SMTP server details in settings. Cubit does not yet support authenticated SMTP. Please ask your ISP
for another SMTP server to use.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('91','Q: I just need to know which command I have to use to start Cubit.','A: Cubit is a browser based system and is tightly integrated into the Firefox web browser. You can use
any other browser as per usual for your normal Internet or intranet and only use Firefox for Cubit. We
recommend installing the latest version of Firefox.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('92','Q:I Have downloaded the program. How do I get started?','A:You need install it. Please read the section in this manual on installing Cubit. If you have any
difficulties please e-mail us or call us on support@cubit.co.za');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('93','Q:Page file error in windows, system getting slower and slower','A:You have limited the swap file maximum size or have too little memory (RAM) or hard drive space.
Cubit requires about 1Gig of Hard drive space and at least 256MB ram. You can also set your page file
to automatically adjust to your system requirements or re install Cubit on a server platform. Please call us for any assistance on support@cubit.co.za');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('94','Q: I have just installed Cubit. The password and username, as supplied on the right side of the
password screen, dont seem to work. How can I reset them?','A: The username and password are case sensitive. Initially the username was Root and password
0123456789 If you change this and forget what it is you will require a consultant to assist you. Please call us on support@cubit.co.za or email support@cubit.co.za');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('95','Q: I have just registered. Do I get a reference or customer number? and what reference do I quote for
telephonic support?','A: In the register Cubit window (Under the admin menu) is a number. Your number is the Client key
and changes every few months, do not be concerned about this change, we still know who you are
when you quote the new number.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('96','Q: Does Cubit have a complete payroll system? Is it being updated regularly with SARS to comply to
the latest legislation? ','A: Yes. It also produces IRP5s. and other reports that SARS requires, including the electronic output
file.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('97','Q:My operating system crashed, but luckily there is a cubit backup.
Now my problem is that I am unable to restore the backup. If i try to restore, it says Invalid Company. ','A: Please check if company was created correctly.
You have to restore the company on the same version of Cubit that you backed it up on or exported it
from.Reinstall Cubit and run the same updates that were present at the time of the backup,');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('98','Q: Does Cubit support serialbarcode scanners?','A: All scanners that connect to the computer via a PS2 port will function with Cubit.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('99','Q: If I have a list of transactions, is it possible to enter all the debits first and then all the credits afterwards?','A: Yes, this is possible. Please refer to 8.1.2.(Entering straight debitscredits) in this manual. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('100','Common Errors','1. Call to undefined function: cubit_run()
This error means that you are not using the Cubit webserver modules.
Cubit has added modules to Apache version 1 which is required for you to install.
Please re install and use the Cubit installation program. The Cubit software conflicts with Microsoft
Windows (Trademark Microsoft Corporation) IIS and with Apache or other webservers.
If you require another webserver installation please configure your webserver to use another port
number (or reconfigure Cubit to use another port number)
2. Syntax error
This error is either the result of a faulty or incomplete installation or because of a bug.
Please contact our support center on 0861 00 4674 or email support@cubit.co.za
3. Identification failed for user postgres, happens after running php f create_db.php on Fedora Core
This error appears because you have not used the installation program. You have to add a user as per
the install file example and run the dump from the actual directory itself.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('101','Setting Up Your Company
3.1. Using Quick Setup','To start using Cubit you first need to set it up.
To set up, there are two options, Quick Setup or Manual Setup. Quick Setup will setup default values,
so you can start using Cubit immediately. When using quick set up it will ask you to set your financial
year period. It will also ask you to set your current period. The current period is the period you are
going to start capturing data in. For example, if you are in September, then you should set the current
period as September.
The Manual setup is done by going to each of the options under the Settings menu, and configuring
them. After that you will be able to use Cubit.
Please note that it is strongly advised that you set up using the quick setup and not manually.
To use the quick set up go to Admin, settings, quick setup. This you will find under the drop down
menus. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('102','Adding Your Company Details ','Go to Admin, Company Details. This you will find under the drop down menus. Fill in all your
company details. These are the details that will appear on all your invoicesstatements etc.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('103','Taking On Balances','Migrating to Cubit from other software:
Cubit does not function similarly to other accounting systems
Cubit is a business management system which is fully integrated with accounting, payroll, groupware,
mail exchange software, customer relationship management and much more.
To this end Cubit sometimes requires more input datainformation (mostly for CAAT).
Migration to an integrated electronic network, like Cubit, with accounting and business information
management functionality is reasonably easy.
There are data import tools enabling the user to import data into the system.
Data such as a detailed trial balance, stock, customers and supplier information can be exported to the required comma separated volume format and imported into Cubit (Please study the manual).');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('104','Taking On Balances','Migrating to Cubit from other software:
Cubit does not function similarly to other accounting systems
Cubit is a business management system which is fully integrated with accounting, payroll, groupware,
mail exchange software, customer relationship management and much more.
To this end Cubit sometimes requires more input datainformation (mostly for CAAT).
Migration to an integrated electronic network, like Cubit, with accounting and business information
management functionality is reasonably easy.
There are data import tools enabling the user to import data into the system.
Data such as a detailed trial balance, stock, customers and supplier information can be exported to the required comma separated volume format and imported into Cubit (Please study the manual).');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('105','Import Tools','Balance from another Accounting package. You will find this import tool under Accounting, Import
in the drop down menus.
Before you can use this function however, you will need to export the relevant information (Creditors,
Debtors, Stock and Trial Balance) from the other Accounting package you were using. You can do this
by exporting the information to a spreadsheet, most Accounting packages have this option. You may
have to edit the data to a specific Cubit layout, you will find this layout under (Accounting-Import).
Once the data is in the correct layout save it as a comma separated volume(CSV) file, any type of CSV
file may be used.
You will then be able to import this CSV file with the import function.
For example: Go to Accounting, Import, Import Stock.
Above the import function it tells you in red writing that the data must be saved as a CSV file. It also
tells you in which order the data must be saved (code, description, price). Make sure that your data is in
the correct order before you import it.
Click on the browse button and select the CSV file you have saved. Then click the import button.
Cubit will now import your stock.
Follow this same process for Customers and Suppliers.
When importing your Trial Balance:
Make sure that your Trial Balance in the CSV file balances. If it does not balance Cubit will not allow
you to continue.
After opening your CSV file in Cubit, you will have to select ledger accounts for each item on your
Trial Balance, or select to create a new account. If you have selected to create a new account, you will
prompted to select further details (current or non current assetsliabilities, incomeexpense, etc).
On the following page you will have to allocate the total amounts from your control
accounts(customers, suppliers, inventory) to the individual customers, suppliers and stock items.
When allocating the Inventory Control amount to the stock items, do not enter the cost price per unit,
enter the total balance for that stock item and the quantity on hand. Cubit will then work out the cost
per unit for each stock item.
Important Notes:

Ensure that there are no commas in your text as each comma will indicate a separation. Cubit
will then record the information under the wrong headings.

When creating your CSV file, if not all information is available for the relevant cells (eg:
Customers email address), the cell should still exist, however it must be left blank. If the cell is
not left blank for each empty value, Cubit will record the information under the wrong
headings.

The Trial Balance should be imported last as the details for Customers, Suppliers and Stock are
required in order to allocate the total values in the relevant Control Accounts.
');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('106','Taking On Balances Through Journal Entries ','When taking on balances for the first time in Cubit, you must use the multiple journal entry function.
The contra account for all entries, which are income accounts, expense accounts and balance sheet
accounts, must be Opening BalanceSuspense Account. In this manner the retailed income account
will balance while all the other balances will appear in the accounts selected when doing the multiple
journal entry.
Assets
When adding Take on Balances for assets do not process it through journal entries.
Go to (Accounting-Asset Ledger- Add Asset). If you want the history of the assets depreciation you
will have to add the asset at its original cost price (you will then have to depreciate it to the current net
value). Select Opening BalanceSuspense Account as the Contra Account.
CreditorsDebtorsEmployeesStock
When adding take on balances for creditorsdebtorsemployees or stock you will first have to add
creditorsdebtorsemployees and stock to your database. Once you have done this, view the
creditordebtoremployees or stock. Click on the transaction option next to the relevant
creditordebtoremployees or stock item and proceed with journal entries. Remember to use Opening
BalanceSuspense Account as your contra account.
All Other Take On Balances
You can enter Take on Balances by doing journal transactions for each balance.
Go to Accounting, Journal Transactions, Add Multiple Journal Transaction. Use account 9000000
Opening BalanceSuspense Account as your contra account. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('107','Before You Get Going','Before you can start making invoices, purchases, statements etc, there are a few things you need to
add. These will now all be discussed briefly. A more detailed explanation of these steps are given
under relevant sections in the manual.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('108','Adding Stock Items','Before you can purchase or sell any stock items you will need to add the stock items into your
company.
This is done under stock, add stock. Fill in all the relevant information and click the confirm button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('109','Adding Suppliers','You will need to add suppliers in order to make purchases.
This is done under Creditors, Add Suppliers. Fill in the relevant information and click the confirm
button. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('110','Adding Customers','Before you can make any credit salesInvoices you will have to add customers.
This is done under Debtors, Add Customers. Fill in the relevant information and click the confirm
button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('111','Registering Cubit','After every few days Cubit will ask you to register.
This screen shows you your registration status, when it will expire and gives you a client side key.
When Cubit prompts you to register copy and paste this key into an email and send it to
andre@cubit.co.za. If your payments are up up to date, you will then be sent a key that you will have to fill in, in the space below your client side key. Once you have done this click the register button and you will then have registered Cubit.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('112','Registering Cubit','After every few days Cubit will ask you to register.
This screen shows you your registration status, when it will expire and gives you a client side key.
When Cubit prompts you to register copy and paste this key into an email and send it to
andre@cubit.co.za. If your payments are up up to date, you will then be sent a key that you will have to fill in, in the space below your client side key. Once you have done this click the register button and you will then have registered Cubit.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('113','Frequently Asked Questions on Setting Up Your Company','Running Cubit for the first time
Cubit will ask you to enter your company name, then a default user and password.
The default username is : Root
THIS IS CASE SENSITIVE TYPE CAPITAL R
The default password is : 0123456789
Cubit then configures your company and after the setup is complete Cubit is ready to use.
Q: When is the end of my financial year?
A: When you register the company with the Registrar of Companies the end of the Financial Year is
specified. Most companies Financial Year End is 28 Feb.
Quick Setup option:
The quick setup option is used to setup Cubits basic settings so that Cubit can be used immediately. A
listing of all the settings Cubit will change will be shown, make sure this is correct as you will not be
able to change them thereafter.
If you do not wish to use the basic settings, you can go through the process manually.
There are certain standard things that any accounting package needs, the quick setup does this
automatically.
It is advised that first time users make use of the quick setup feature and manual set is NOT
RECOMMENDED.
Should you wish to adjust the chart of accounts etc. please select the edit or delete options after using
quick setup
After the quick setup has been completed Cubit will display a message that cubit is ready to be used.
Cubit is then ready to be used for Journals, Assets etc. The only other essential steps to complete are:
Adding Customers, Stock (if applicable to your business), Suppliers and Employees. Ask you dealer
about importing from other systems.
This is enough for the standard business, there are however a few settings under the Admin menu that
can be set for additional functionality or preferences if the user wishes to have them.
Q: Can more periods be added?
A: No. The periods cannot be divided into smaller periods, however future versions will be more
flexible.
');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('114','New Invoices
The First Page','When you first click on this you will have two fields to fill in, the department and the first letters of the
customer. The first letters of the customer is not a necessary field, fill it in only to filter out other
options on the next page.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('115','The Main Invoice Page','The first thing you should do is select the customer from the drop down list at the top left of the page.
After doing this all the customer fields will be filled in for you. The Customer Order Number field is
optional but is useful when searching for a customer order. The customer supplies this value from his
books. If a customer does not exist you can add the customer by going to the Debtors menu and
clicking Add Customer.
To add a product you select the item number from the drop down list. This list only contains items of
the specified store. You can change the store by clicking the Different Store button. Enter the
description of the item order. This makes reading the invoice in the future easier. Enter the quantity of
the items being ordered in the quantity box. There are two discount fields on this page, the first one for
specifying a discount in currency format, and the second for percentage discount. If you wish to add
another product, press the add product button.
When a product has been selected you can remove it by clicking on the Remove checkbox to the right
of the product. Clicking the Update button will remove this item from the list.
If you wish to continue this invoice only in the future, click the Save button. You can then safely go
on doing something else and go back to this invoice by going to Sales menu, Invoices and clicking
View Incomplete Invoices.
To select a different store click the Different Store button. A drop down list will appear allowing you
to select a different store. If you were busy with a product, this product will automatically be added to
the list, and the new store will be for the next product.
When you are done with the invoice, you can press the Done button to create the invoice. The invoice
will appear on your screen ready to be printed.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('116','View Invoice
The First Page','On the first page you are given the option to enter a date for invoices to view. You could use the default
values, which is from the first of the current month up to todays date. Click the search button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('117','The Invoice View Page','Presents a list of all the invoices and their details which have been completed. Totals for their Totals
and Balances are given at the bottom of the list.
To the right of each invoice there is a list of options. They are as follows:
Details: Shows you the complete invoice as it is.
Credit Note: Same as details, except here you can edit the comments field of the invoice.
Reprint: Print the invoice again.
Reprint in PDF: Saves and prints the invoice in PDF format.
Delivery Note: This gives you a printable delivery note. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('118',' Invoice Search','Here you can search for an invoice.
On the first page select the invoice type, or leave it as all types. Then fill in the invoice number. Then
click the search button.
The invoice will then appear on the screen, with the option details next to it. If you click on the
details option. The detailed invoice will appear on the screen. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('119',' Incomplete Invoices','When you first enter this page you are presented with a list of the invoices which have not yet been
completed. To continue an invoice click the continue link. You can also cancel one of these by
clicking the cancel link. When canceling an incomplete invoice you will be asked to confirm the
cancellation. Make sure you are canceling the correct invoice then click the Cancel button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('120',' New Non Stock Invoice','Non stock invoices are useful if you want to sell items which arent really part of the products you
offer, but still wish to let it go through the books, for example if you are selling your equipment to buy
new ones.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('121','Main Invoice Page:','Fill in the details of the person you are selling it to in the Customer Details box on the top
left of the page.
In the body, fill in the description of an item, the quantity of units and the price per unit. Prices for
these items are VAT inclusive, meaning that the total price will not be added vat, but vat will be
subtracted from it to calculate the Sub total.
After you have entered the product details click the Update button.
If you wish to add another product click the Add Item button.
To remove a product click the Remove in th check box to the right of the product and click the
Update button.
You can enter remarks and notes on the sale in the remarks box below the product list.
After you have added at least one product and entered at least the customer name, you can click on the
Done button. You can now print the invoice.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('122','View Non Stock Invoices (detailseditremoveprocess)
First Page','Select date range for which you wish to see Non Stock Invoices.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('123','View Non Stock Invoices (detailseditremoveprocess)','Main Page
On this page you will see a list of all the Non Stock Invoices which you have made. Next to each
invoice listing you will see a couple of options.
The Details option will show you the invoice.
Credit Note: Here you can record a credit note for this invoice, if the supplier is not satisfied with the
non-stock item.
The Edit option will allow you to edit unprinted invoices (Those for which you have not pressed the
Done button)
The Reprint option will print the invoice again.
Print in PDF: This allows you to print the invoice in a PDF formate.
You can email these invoices, by selecting the invoice you want to email and clicking on the email
selected button. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('124','New International Non Stock Invoice','Here you can add Non Stock invoices for International customers.
You do this in the same way as normal non stock invoices.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('125','New International Non Stock Invoice','First Page
Select the customer and click the continue button.
Main page
Fill in all the information and click the update button. Then click the done button.
Your Non stock International Invoice has now been processed. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('126','New Recurring Invoice','These are invoices that are reoccurring. This menu allows you to enter the transaction once and then
process it each time the transaction comes up again.
This is done in exactly the same way as a normal once off invoice.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('127','New Recurring Non Stock Invoice','Here you can make a recurring invoice for a Non Stock item. This is done in the same way you would
create a once off invoice for a stock item.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('128','New POS Invoice','Details
A Point of Sale (POS) invoice is an invoice made out to a customer for whom isnt on your list of
customers. This is useful for companies dealing only in cash sales.
First Page
On the first page, select the department for which you need to make a POS invoice.
Main POS Invoice Page
Fill in the customers name and order number. These fields can be left blank for a cash sale. An order
number is an optional value which is useful for referring back to this invoice in the future. The
customer supplies this value from his books. You can also enter the barcode if necessary.
The rest is the same as with a normal invoice. See New Invoice for more details.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('129','View processed POS invoices (processreprintslip)','First Page
Select the date range and the period for which you wish to list processed POS invoices.
Main Page
Here you will see a list of all the previously processed invoices. You can view the details or Reprint
them or Credit Note them or print a slip by clicking the appropriate option next to the invoice.
4.1.1.12. View unprocessed POS Invoices (detailseditremove process)
First page
Select the date range for the listing of the Unprinted POS invoices. The default is this month up to the
current day. Click the Search button.
Main page
After you have clicked search, it will display a list of all the invoices which satisfy the date selection.
From here you can view the invoice by clicking the Details option, edit it by clicking the Edit option,
or print it by clicking the Print option. Note, that once you have clicked the Print option you will not
be able to edit it again. Make sure the information is 100% correct before doing this.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('130','New Sales Order','You can place a new order for a customer before you make an invoice. A finished order can be
converted into an invoice at any time. When you place an order for a customer, stock is allocated for
this order. This stock will only become available if this order is canceled.
First page
Select the department and optionally fill in the first letters of the customer. Click Continue button.
Main Order Page
Select the customer from the Customer drop down menus. The Customer Order Number field is
optional. This field helps when referring back to this order in the future. The customer supplies this
from his books.
On the Sales Order Details box on the right side of the page there is another Order Number field. This
is the same as the previous one except it comes from your books. This field is also optional.
In the body of the page, please select a product to place an order for from the Products drop down list.
Enter the quantity of the product and then the discount if applicable. Discounts can be entered in one of
two forms, in currency or percentage. The first box is the discount in Rands and the second in
percentage. You need only fill in one of them.
If the product you wish to add is not in the store shown to the left of the product list, click the Different
Store button at the bottom of the order form. This will allow you to select another store.
To remove a product from the order items list, click the Remove checkbox to the right of the product.
When you have selected all the products you wish to have removed, click the Update button.
When you have completed your order, click the Update button. A new button labeled Done will
appear which will record the order.
If you wish to continue your order at a later stage, click the Save button, you can now safely continue
to do something else, and return to this order later.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('131','New Non Stock Sales Order','This is similar to new sales order, except here you are adding a non stock. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('132','Payments and Receipts
Bank: Receipt From Customer','When a customer pays you, it is recorded here.
Customer Selection
From the drop down menu, select the customer that paid you and click the Enter Details button.
Transaction Details
Select the bank account into which he is paying the funds. Enter the date and description of the
transaction. If the customer paid by Cheque, enter the cheque number. Enter the amount heshe paid.
Now decide how you going to allocate the funds. The different allocations and their descriptions
follow:
Auto: Cubit will decide how it will allocate the funds of the payment. It starts by subtracting available
amounts from the oldest transactions, up to the newest.
Allocate to Age Analysis: Allocate the money manually by selecting how much funds should be used
for which transactions, according to the age of the transaction. This is for transactions that made more
than 30 days ago, and which has not yet been fully paid.
Allocate to Each Invoice: Allocate the money manually by selecting how much funds should be used
for which invoices.
When you are done click the Allocate button.
Allocation
Automatic Allocation
Cubit decides where it will put the money, and displays where the money was allocated to. If you are
satisfied with it, click the Confirm button, or click the Back button for a correction.
Allocation by Age Analysis
Below the general details for the transaction you will see 5 input boxes labeled, Current, 30 Days, 60
Days, 90 Days, 120 Days. Above each of these input boxes the amount owed for that period will be
displayed. Enter in each input box the amount which you wish to allocate for that period.
When you are done click the Confirm button.
Allocation by Invoice
Below the general details for the transaction you will see an entry for each invoice, with an input box to
its right. Enter the amount you wish to allocate for each invoice in the area provided.
When you are done click the Confirm button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('133','Petty Cash: Receipt From Customer','Select this option when receiving petty cash from the suppliers account into the petty cash account.
Enter the date and select the Supplier from the drop down menu. Fill in the description and the amount
received. Then click the add button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('134','Bank: Pay Customer ','Here you can add a bank payment to a customer, similar to the way you can add a receipt from a
customer. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('135','Petty Cash: Pay Customer ','Select this option when paying a customer with petty cash.
Enter the date and select the Customer you wish to pay from the drop down menu. Fill in the
description and the amount paid. Then click the add button. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('136','View Credit Notes (viewdetails)','First Page
Select the date range and the period for which you wish to view the credit notes.
Main Page
Here you will see a list of all previously processed credit notes and view there details by selecting the
details option next to the credit note.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('137','View Non Stock Credit Notes (detailsreprint)','Select the date range of the Non Stock Credit Notes you want to view.
Here you will see a list of all the Non Stock Credit notes. You will be able to view the Credit notes
details, by clicking on the detail option next to a specific Credit Note.
You can reprint the Credit note by clicking the reprint option.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('138','Quotes
New Quote','The First Page
Fill in, the department and the first letters of the customer.
The field labeled First Letters of Customer is optional. It only helps selecting the customer when you
have a huge list of customers.
The Main Quote Page
The first thing you should do is select the customer from the drop down list at the top left of the page.
After doing this all the customer fields will be filled in for you. The Customer Order Number field is
optional but is useful when searching for a customer order. The customer supplies this value from his
books. If a customer does not exist you can add the customer by going to the Debtors menu and
clicking Add Customer.
To add a product, you simply select the item number from the drop down list. This list only contains
items of the specified store. You can change the store by clicking the Different Store button. Enter the
description of the item order. This makes it easier to read the quote in the future easier. Enter the
quantity of the items being ordered in the quantity box. There are two discount fields on this page, the
first one for specifying a discount in currency format, and the second for percentage discount. If you
wish to add another product, press the add product button.
When a product has been selected you can remove it by clicking on the Remove checkbox to the right
of the product. Clicking the Update button will remove this item from the list.
If you wish to continue this quote only in the future, click the Save button. You can then safely go on
doing something else and go back to this quote by going to Sales menu, Quotes and clicking View
Incomplete Quotes.
To select a different store click the Different Store button. A drop down list will appear allowing you
to select a different store. If you were busy with a product, this product will automatically be added to
the list, and the new store will be for the next product.
When you have completed the quotes, click the Update button. All data will be validated and you can
press the Done button to create the quotes. The quotes will appear on your screen, ready to be printed.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('139','View Quotes (detailseditcancelacceptprintprint PDF)','The First Page
On the first page you are given the option to enter a date for quotes to view. You can use the default
values, which is from the first of the current month up to todays date. Click the search button.
The View Page
You will be presented with a list of all the quotes and their details which has been completed. Totals for
their Totals and Balances are given at the bottom of the list.
To the right of each quote there is a list of options. They are as follows:
Details Shows you the complete quote as it is.
Edit: You can edit the details of the Quote.
Accept: Converts the Quote into an invoice. You can edit or finish this newly created invoice from the
Incomplete Invoices option on the menu.
Print: You can print the Quote to send to a customer.
Print in Pdf: This is a different format you can choose to print the invoice in.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('140','New POS Quote','A POS quote is a quote u make out to a customer which is not listed in your database. This is very
helpful for cash sales you need to make a quote for.
First Page
Select the department for which this quote should be made.
The Main POS QUOTE Page
Fill in the name and address of the customer. These fields are not compulsory and can be left blank.
To add a product, you simply select the item number from the drop down list. This list only contains
items of the specified store. You can change the store by clicking the Different Store button. Enter
the description of the item order. This makes reading the POS QUOTE in the future easier. Enter the
quantity of the items being ordered in the quantity box. There are two discount fields on this page,
the first one for specifying a discount in currency format, and the second for percentage discount. If
you wish to add another product, press the add product button.
When a product has been selected you can remove it by clicking on the Remove checkbox to the
right of the product. Clicking the Update button will remove this item from the list.
If you wish to continue this POS QUOTE in the future, click the Save button. You can then safely
go on doing something else and go back to this POS QUOTE by going to Sales menu, POS
QUOTES and clicking View Incomplete POS QUOTES.
To select a different store click the Different Store button. A drop down list will appear allowing
you to select a different store. If you were busy with a product, this product will automatically be
added to the list, and the new store will be ready for the next product.
When you are finished with the POS QUOTES, click the Update button. All data will be validated
and you can press the Done button to to create the POS QUOTES. The POS QUOTES will appear
on you screen ready to be printed.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('141','View POS Quotes (detailseditcancelacceptprintprint PDF)','First Page
Select the date range and the period for which you wish to list POS quotes.
Main Page
Here you will see a list of all the finished POS Quotes. You can view the details, cancel it, Accept it,
Print it or Print it in Pdf by clicking the appropriate option next to the quote listing.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('142','New Non Stock Quote','This is similar to new quotes, except here you are making a quote for a non-stock item.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('143','View Non Stock Quotes (detailseditcancelacceptprintprint PDF)','This is exactly the same as view quotes, except here you are working with non-Stock items. Next to
each Non Stock quote, you will see options to view details, cancel it, Accept it, Print or Print it in Pdf.
You can do any of these by clicking on the appropriate option next to the non stock quote listing.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('144','Stock
New Consignment order','Consignment Orders
A consignment order occurs when you send your inventory to another party to sell it for you. This other
party holds your inventory while they are trying to sell it. The inventory still belongs to you and has to
be included in your inventory count. They do not pay you anything for the inventory until they actually
sell it. If they do not sell the stock then they just return it to you and do not pay you anything.
When you create a new consignment order the amount of stock that you choose to send on consignment
will be shown as allocated under the view stock option (stock-view stock-view allocation). This is
how you can determine how much of a stock item is on consignment.
When the other party sells this inventory you will record the sale via (sales-consignment orders-view
consignment orders-invoice) you can then select how much of that consignment order has been sold by
the other party.
Once you have made the sale and processed the invoice the system will assume that you restocked the
other party with the initial amount of stock given to them on consignment. So the total number of units
on consignment will NOT be reduced it will always remain at the same level. If you wish to adjust the
level then edit the consignment order and change the number of units.
If the other party does not sell the stock you gave them and returns it to you, simply view consignment
orders and click the cancel option to cancel the necessary consignment order, this will take the
inventory out of allocated stock.
The company can return the stock at any time, and the consignment order is canceled. When creating
an invoice from the order, the Consignment Order does not get canceled, the same consignment order is
used to create more invoices in the future.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('145','Consignment Orders','First page
Select the department and optionally fill in the first letters of the customer. Click Continue button.
Main Order Page
Select the customer from the Customer drop down menus. The Customer Order No field is optional.
This field helps when referring back to this order in the future. The customer usually supplies this
information.
On the Sales Order Details box on the right side of the page there is another Order No field. This is
the same as the previous option except it comes from your books. This field is also optional.
In the body of the page, please select a product to place an order for from the Products drop down list.
Enter the quantity of product and then the discount if applicable. Discounts can be entered in one of
two forms, currency or percentage. The first box is the discount in Rands and the second in percentage.
You need only fill in one of them. If the product you wish to add is not in the store shown to the left of
the product list, click the Different Store button at the bottom of the order form. This will allow you to
select another store.
To remove a product from the order items list, click the Remove checkbox to the right of the product.
When you have selected all the products you wish to have removed, click the Update button.
When you are done with your order, click the Update button. A new button labeled Done will appear
which will record the order.
If you wish to continue your order in the future, click the Save button, you can now safely continue to
do something else, and return to this order later.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('146','View Consignment Orders (detailseditcancelprintinvoice)','You will get a list of all orders that are currently active. Next to each order you will see a list of
options.
They are as follows:
Details: Shows you the complete order as it is at that moment.
Edit: Changeadd products or info to this order.
Cancel: Cancel the order and return the allocated stock as available.
Print: You can print the order from here.
Invoice: With this option you can convert the order into an invoice. When you have clicked this, it
will display how the Invoice will look. Click the Invoice button which takes you to the Invoice editing
page. You can edit the invoice from here the same way as editing a normal invoice.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('147',' Journals
Customer Journal','Listing of all your customers. The last line will show the total amount of money due by all customers.
Next to each customer line there are options that affect the customers account.
Add Receipt allows you to accept a payment made by the customer. When accepting this payment you
will have the option to allocate the receipt in three different ways. Auto: This allocates the receipt from
the oldest invoice to the newest one. By age analysis: This allocates the receipt according to the age
analysis. By Invoice: This will give you a list of all the invoices outstanding. You can then allocate the
receipt of payment to each invoice as you choose.
Outstanding Stock: This will show you any outstanding stock that has not been delivered to the
customer yet.
Details gives you more details about the customer.
Statement will list every transaction you made concerning that customer, eg. payments and purchases,
in a printable form.
Transaction allows you to record a transaction with this specific Customer.
Edit and Remove allows you to edit and remove customers. You may only remove customer for which
there are no transactions.
Print Invoices: Here you will be given a printable screen of the statement.
Block: You can block this customer by clicking on this block option. The customer will then be block
and you will not be able to enter into any transactions with them.
Remove: Here you will be able to remove the customer. Note: You cannot remove the customer if there
is still money outstanding from them.
Add Contact: Here you can add a contact.
Transactions
When clicking on the transaction option, you will be able to record customer payments and purchases.
Reference numbers are automatically generated, this could be changed but is usually not required.
Enter the transaction date.
Select whether this was a Debit or Credit transaction.
In the Contra Account field you select the account from. If you selected to Debit the customer account,
you will Credit the the Contra Account, and visa versa. Under Contra Account you can either select
from the list OR enter a custom description into the text box below the selection. Thereafter click the
Enter Details button.
On the enter details page you enter the amount, the descriptiondetails, and click the Record
Transaction button. You will be asked to confirm this transaction.
You can select to email statements to customers. Select which customer you wish to email and then
click the email statements button. If you have entered an email address when adding this customer,
then that email address will appear. If you did not add an email address, then you will be given the
option to add it here.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('148',' PROCESSING
Find Customer','This helps you to find a customer.
Select the filter you wish to use to find the customer and enter the relevant detail. Then click the apply
filter button.
Or add no filter and click the view all button. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('149','View Temp Invoice Number','When creating an invoice, it is given a temporary invoice number. Having this number you can cancel
an invoice safely. Once you have finished an invoice, this temporary number is replaced by a real
Invoice number. You cannot editcancel an invoice which has a permanent number because your books
have to show a sequential list of invoice numbers. With this feature you can match a previously
temporary invoice number with the real one.
First Page
If you have the Temporary invoice number, and wish to find the Real one, fill in the first text box and
click the Find button. If you have the Real invoice number, and wish to find the Temporary one, fill in
the second text box and click the Find button.
Results
On this page you will be shown the Temporary and Real invoice number of the results you searched
for.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('150','View Recurring Invoices (detailseditinvoiceremove)','Select the Customer and the date range and click the search button.
A list of all the selected Customers reoccurring invoices will be displayed.
Select the invoice that has just reoccurred by ticking the check box next to the invoice. Then click the
process button.
This invoice will then be processed again. You can do this each time a reoccurring invoice occurs.
For example a client pays to R200 every month for services you render. You will only have to enter
this invoice once, from there onwards you just need to process the invoice.
Next to each reoccurring invoice will be the following options: detail, edit, invoice, remove.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('151','View Sales Orders (cancelprintinvoice)','Here you can view the Sales order and convert it into an invoice. You do this by clicking the invoice
option that appears next to the Sales Order. The details will then appear on the screen. Click the
invoice button. An invoice will then be displayed with your Sales Order on it. Enter all the relevant
information you require and click the update and then the process button. A printable Tax Invoice
will then be displayed.
The other options that appear next to the Sales Order are details, edit, cancel, print, invoice.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('152','View Incomplete Sales Orders (continuecancel)','Shows you a list of the sales orders which you have saved. Clicking on the cancel option next to an
order will cancel it and return the stock back as available. Continue will allow you to complete the
invoice.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('153','View Non Stock Sales Orders (detailseditprintaccept)','This is similar to new sales order, except here you are adding a non stock.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('154','View Paid Invoices (detailsreprintcredit notedelivery note)','When invoices are paid using the banking module, they are moved. To view paid invoices, choose the
correct period and date range.
Next to each paid invoice you will have the following options:
Details: Here you can view all the details of this paid invoice.
Reprint: Here you will be able to reprint the invoice.
Reprint in PDF: Here you can reprint the invoice in a PDF format.
Credit Note: If the goods are returned, you can create a Credit note for this invoice here.
Delivery Note: Here you are given the option to generate a printable delivery note. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('155','View canceled invoices','It is not possible to cancel a finished invoice. It is possible to cancel an unfinished invoice. On this page you will see a list of all these invoices which have been canceled.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('156','View Canceled Sales Orders','This simply shows you a list of all the canc');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('157','View Canceled Sales Orders','This simply shows you a list of all the canceled sales orders. You cannot un-cancel an order. Once it is canceled, it remains canceled.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('158','View Incomplete Quotes (continuecancel)','Shows you a list of all the quotes which you have not completed yet. From the option next to the quotes
on the list, you can click Cancel to cancel this Quote, and Continue to finish it.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('159','View Canceled Quotes','To view canceled quotes, select the date range, and click the button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('160','View Incomplete POS Quotes (continuecancel)','Shows you a list of all the POS quotes which you have not completed yet. From the
option next to the quotes on the list, you can click Cancel to cancel this Quote, and
Continue to finish it.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('161','View Canceled POS Quotes','Shows you a list of all the Quotes you have canceled. The information on this page is simply general
information and is for you reference only.
');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('162','View Incomplete Consignment Orders (continuecancel)','Displays a list of saved consignment orders. Clicking on the cancel option next to an order will cancel
it and return the allocated stock as available. The Continue option will allow you to finish the invoice.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('163',' View Canceled Consignment Orders','The First Page
Select the date range for which you wish to view canceled consignment orders. You could use the
default values, which is from the first of the current month up to todays date. Click the search button.
View canceled Consignment Orders
You will see a list of canceled consignment Orders. With the date, the user who canceled it, the
department and the order number.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('164',' View Canceled Consignment Orders','The First Page
Select the date range for which you wish to view canceled consignment orders. You could use the
default values, which is from the first of the current month up to todays date. Click the search button.
View canceled Consignment Orders
You will see a list of canceled consignment Orders. With the date, the user who canceled it, the
department and the order number.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('165',' Calculate Interest (Monthly Task)','This allows you to calculate interest on debtors. Before you calculate the interest you will need to Set
interest calculation method and Set Debtors age analysis period type,
which you will find under the Admin settings. Once this is done you can go ahead and calculate the
interest on all the overdue accounts.
When you have confirmed interest calculation, interest will be calculated and added to the customers
balance. A new non-stock invoice will be created for the interest which will enable you to select
which interest and invoices are being paid when you receive payment from a Debtor.
When receiving payment from a Debtor and you are recording the transaction in the Cash Book,
select the Allocate to each invoice option in order to allocate which invoice and interest is being
paid.
');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('166','Add Customer','You can add new customers with this. You need to add someone as a customer before you can do a
transaction. This rule counts for all transactions except POS transactions.
Entry Fields
The following are descriptions for all non-standard fields like Name, Surname etc...
Category:
U can add users to different categories for example: area they live in, etc.
Classification: Example: reseller, walk in, etc.
Price List: You can create multiple price lists, and assign each customer to their own price list.
Settlement Discount: If a person bought on credit and settles their account before the credit term, they
will receive a discount.
Charge Interest Option: If a person doesnt settle the account before the overdue date, they are charged
interest.
Overdue: Select the amount of days it takes for an account to become overdue.
Credit Term: Under what terms the customer may buy on credit.
Credit Limit: Maximum credit a customer may have. When a customer has reached his maximum
credit, you will be notified when creating a new invoice for this customer.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('167','View Customers (receiptoutstanding stockeditstatementtransaction
print invoicesblockadd contact)','Listing of all your customers. The last line will show the total amount of money due by all customers.
Next to each customer line there are options that affect the customers account.
Add Receipt allows you to accept a payment made by the customer. When accepting this payment you
will have the option to allocate the receipt in three different ways. Auto: This allocates the receipt from
the oldest invoice to the newest one. By age analysis: This allocates the receipt according to the age
analysis. By Invoice: This will give you a list of all the invoices outstanding. You can then allocate the
receipt of payment to each invoice as you choose.
Outstanding Stock: This will show you any outstanding stock that has not been delivered to the
customer yet.
Details gives you more details about the customer.
Statement will list every transaction you made concerning that customer, eg. payments and purchases,
in a printable form.
Transaction allows you to record a transaction with this specific Customer.
Edit and Remove allows you to edit and remove customers. You may only remove customer for which
there are no transactions.
Print Invoices: Here you will be able to print all the invoices made to this customer. You will be able to
select to print allpaid or outstanding invoices from a drop down menu. Once selecting which invoices
you wish to print, you will be presented with the printable invoices, which you can then print by
pressing ctrl P on your keyboard.
Block: You can block this customer by clicking on this block option. The customer will then be block
and you will not be able to enter into any transactions with them.
Remove: Here you will be able to remove the customer. Note: You cannot remove the customer if there
is still money outstanding from them.
Add Contact: Here you can add a contact.
Transactions
When clicking on the transaction option, you will be able to record customer payments and purchases.
Reference numbers are automatically generated, this could be changed but is usually not required.
Enter the transaction date.
Select whether this was a Debit or Credit transaction.
In the Contra Account field you select the account from. If you selected to Debit the customer account,
you will Credit the the Contra Account, and visa versa. Under Contra Account you can either select
from the list OR enter a custom description into the text box below the selection. Thereafter click the
Enter Details button.
On the enter details page you enter the amount, the descriptiondetails, and click the Record
Transaction button. You will be asked to confirm this transaction.
You can select to email statements to customers. Select which customer you wish to email and then
click the email statements button. If you have entered an email address when adding this customer,
then that email address will appear. If you did not add an email address, then you will be given the
option to add it here.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('168','View Recurring Non Stock Invoice','Select the customer and date range that you wish to wish.
A list of all (for the date and customer you selected) the recurring non stock invoices will be displayed
on the screen.
If you want to process one of these invoices again, select the invoice and click the process button. This
invoice will then be processed again.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('169','Print Customer List','This displays a printable list of all your customers with their balances.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('170','CustomerDebtors Statements','This displays printable PDF statements for each customer with a list of all transactions and their
balance outstanding.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('171','Invoice Discount Report','You select the department from the department drop down list, then type the first letters
of the customer. The First Letters of Customer field is optional and can be left blank to
make a listing of all the customers. Click Continue.
A list of all the discounts you have given customers at the specified department will
appear. The customers that get listed are chosen by the First Letters of Customer field
on the previous page. If this field has been left blank you will see a listing for ALL the
customers in you database.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('172',' Stock Sales Report','This generates reports regarding the sales of your stock.
First page:
Displayed is a view option to create the format of the report.
Select the date range for the report to be generated.
To create a report by Category of products, select a category from the drop down list under the
Category heading. Click the View button.
To create a report by Classification of products, select a classification from the drop down list under
the Classification heading. Click the View button.
To create a report for both, click the View All button under the All Categories and Classifications
heading.
A Report of the Stock Sales will be displayed. You can export this report to a spreadsheet by clicking
the export to spreadsheet button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('173','Non Stock Sales Report','This generates reports regarding the sales of your non stock.
Select the date range and view your Non Stock Report.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('174','Total Sales Report','Total Sales Report
This generates a report of your Total Sales.
Select the date range and click the view button.
A report of all your Stock and Non-Stock sales is displayed. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('175','Sales Rep Commission Report','If you are a company with representatives for commission, you can create a report of the commissions
you gave them. On the first page, select the Sales person from the drop down list. You can also select
All from the drop down list to generate a general report. Enter the date range for which you wish to
generate the report. When youre done click the Search button. At the bottom of the report is the total
commission.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('176','Debtors Age Analysis','Here you will see a list of your customers along with the age analysis for each individual customer.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('177','Save Age Analysis','This allows you to save the age analysis.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('178',' Record Month End For Age Analysis','If you have selected the close month manually option under admin, settings, Admin, Set Debtors age analysis period type. then this is where you will have to close the month manually.
');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('179',' Debtors Ledger','You can either view individual customers or all customers with the Debtors Ledger. This is done by
choosing either selected accounts or all accounts. Then select the period which you would like to
view debtors for and whether you would like to see the transaction date (The date of the transaction
required by GAAP) otherwise the system date (the date the transaction was entered into the Cubit
system).');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('180','View Previous Years Debtors Ledger','Select the year you wish to view the Debtors ledger and click the next button. Manually select which Debtors you wish to view or select all debtors at the top of the screen. Then select the month you wish to view them in and click the continue button.
You can export this to a spreadsheet if you so wish. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('181','Add POS User','Here you will be able to add a POS user. There are three different types of POS users that you can
select.
POS User: This person can add a POS transaction for different store and they can add a non-stock item.
A normal POS user can enter POS sales for a Debtor.

Speed POS user: This is for someone who does not need to think. All they do is scan the barcode in,
take money and give change. (Tellers at Spar) They do not have any addition functionality besides
POS. They cannot make transactions for different stores and they cannot make non-stock transactions.
A speed POS user will not be able to add a sale for a debtor.
Speed POS Supervisor: This gives someone more functionality than the Speed POS users. You need
someone to be able to add non-stock items, etc when you only have Speed POS users. You will usually
only make one person a speed POS supervisor user, who will then be supervisor over the speed POS
users.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('182','Add Department','This function allows you to add a sales department, They differ from the user departments which
determine which programs each user is allowed to access. Enter the department number, a unique
number with which to identify the department in the future. Enter a name for the department.
The income account is the account into which all sales under this department should be paid into.
Debtors Control Account is the ledger account through which all debtors transactions are handled.
Creditors Control Account is the ledger account through which all creditors transactions are handled.
Point of Sale Cash on Hand Account is the account that all POS sales are related to. Point of Sale
Income Account is the ledger account that handles the POS sales.
When you are done click the Confirm button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('183','View Departments (editremove)','Displays a list of all sales departments with two options next to each one, Edit and Remove.
Edit allows you to edit the sales department
Remove deletes it.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('184','Add Sales Person','When creating an invoice or quotation you can choose which sales person you are. This way you can
log who sold what to whom, and when they sold it, and you can give commission to that sales person.
There are two ways to pay commission:
1)One percentage commission for everything the Sales person sells- Next to commission add a
percentage. This will then be the percentage commission that the Sales Person will earn on all sales that
they make.
2) The Sales person earns different percentages on each stock item- Next to commission enter 0. The
percentages will be allocated to the stock under 2.1.5.13. Set Sales Rep Commission
Enter the number and name of the sales person and click the confirm button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('185','View Sales People (editremove)','Displays a list of all Sales people in the current company with two options next to each.
The Edit option allows you to Edit the Sales persons name or number.
The Remove option will delete the Sales person.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('186','Add Customer Category','Customer categories allows you to divide your customers for easier management. Enter the name of the
category you are adding and click the Add button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('187','View Customer Categories (editremove)','Displays a list of all the customer categories.
Next to each option you will find two options: Edit and Remove.
Edit:
Allows you to Edit the category.
Remove: Deletes the category. You cannot however delete any categories which have customers under
them. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('188','Add Customer Classification','Customer classifications allows you to divide your customers so they are easier to manage. Enter the
name of the classification you are adding, and click the Add button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('189','View Classifications','Displays a list of all customer classifications.
Next to each option you see two options: Edit and Remove.
Edit: Allows you to Edit the Classification.
Remove: Deletes the classifications. You cannot however delete any classifications which have
customers under them.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('190','Add Interest Bracket','Interest brackets allows you to set different interest rates for different ranges of loanscredit. Fill in the
min and max values under which the interest should be charged. Enter the percentage of interest you
wish to charge in this range.
Click the Confirm button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('191','View Interest Bracket (editremove)','Shows you a list of all the interest brackets you have added. You can Edit or Delete the interest bracket
by selecting the correct option next to each entry.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('192','Set Debtors Age Analysis Period Type','When a customer buys on credit, and pays that money back to you after a certain time, interest is
calculated by the time period(age) of the transaction. This sets how that time period (age) is calculated.
Use Cubit System Date: The age is calculated by the amount of days since the transaction was made.
Close Month Manually: You select the  Record month end for age analysis  option from the Sales
Menu. The age analysis monthperiod will move on from that.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('193','Set Debtors Statement Type','This gives you a choice between an Open Statement and Running Statement.
Use Open Item Statement: The Debtors statements will only display outstanding invoices. i.e. Invoices
that have not as yet been paid.
Use Running Statement: The Debtors statement will be displayed with opening balances and all
invoices made. i.e. Invoices that have been paid will be displayed, along with the payment made, etc.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('194','Set Interest Calculation Method','This sets the way interest is calculated.
Percentage will calculate a fixed percentage of the amount.
Interest brackets allows you to set different percentages of interest for specific ranges of credit.
Example R0-R1000 has 0% interest, and R1000-R2000 has 5% interest, etc
Customer Specific RateDefault Rate allows you to set a specific interest rate for specific customers.
Select which Account will be credited with the Interest received from the drop down menu and click
continue.. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('195','InvoiceStatement Template Settings','Here you can select and preview the different templates for invoices and statements. There are two
different types of invoicestatements, Html and PDF.
PDF: On this type of setting your company logo cannot as yet be displayed. However this setting will
look exactly how the preview looks.
Html: On this type of setting your company logo can be displayed. However this setting may adjust
your invoice, report etc to fit a certain size, and this might not look nice. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('196','Set VAT Account','This allows you to set the account for which VAT is handled.
On the first page, you select the category under which you are handling VAT. On the second page you
select the account which handles VAT. If you used the Quick Setup, this was automatically done for
you.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('197','Set Rounding Account','This allows you to set the account from which the rounding in handled.
On the first page you select the category under which you are handling Rounding. On the second page
you select the account which handles Rounding. If you used the Quick Setup, this was automatically
done for you.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('198','Set Credit Card Control Account','This allows you to select the account for which the credit card is handled.
On the first page you select the category under which you are handling your credit card. On the next
page you select the account which handles the Credit Card. If you used the Quick Setup, this was
automatically done for you.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('199','Set Invoice Number','When creating credit notes, invoices and performing purchases, you are assigned a temporary number
until it becomes official. This allows you to cancel and note, invoices or purchases without loss of
numbers in your ranges. If you do however for some reason wish to set what the next value should be,
you can do it from here.
Change the value in the boxes provided and click the Continue button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('200','Set Sales Rep Commission','This allows you to set the commission percentage for a sales representative on every item sold.
Select the store for which you want to change the commission on stock items.
Category: The category under which the stock item falls
Classification: The classification of the stock item.
On the next page enter the percentage of commission you are granting for each item.
When you are done click the confirm button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('201','Point Of Sale Rounding','This allows you to set the point of sales to round to the nearest 5 cent, always in favour of the
customer. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('202','InvoiceStatement Default Comment','Here you can type in a default comment that you would like to appear on all your invoices. For
example: Thank you for you support or Please call again.
Type in the comments you wish to appear and then click the confirm button.
This comment will then appear on all your invoices. If you wish to change this comment for a
particular invoice, simply click in the comment box in the invoice, delete the default comment and type
your new comment. This new comment will only appear on this specific invoice and the default
comment will still appear on all the other invoices.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('203','Set Customer Credit Limit Response','Here you can set the response that will occur when a customer reaches their credit limit.
Warn On Reaching Limit: When a customer reaches their credit limit you will be given a warning, but
Cubit will still allow you to sell beyond the customers credit limit.
Block On Reaching Limit: When a customer reaches their credit limit the account will be blocked. You
will not be able to make any more sales to this customer until they have paid and are below their credit
limit again. You will still be able to save invoices to them, these can then be processed once they are
below their credit limit again. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('204','Q: How do I create an Invoice?','A: Go to Sales, invoice, new invoice in the drop down menu. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('205','Q: How do I set Sales Rep, Commission?','A: Go to Settings, Sales settings, Add Sales Rep.
Once you have added a Sales Rep, you can then set their commission by going to Admin, Settings,
Sales, Set sales rep commission.
Commission can be set in 2 ways:: 1) % of each stock item.
2) % of everything he makes.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('206','New Order','This allows you to record a purchase you made from one of your suppliers.
First page
Select the department and optionally fill in the first letters of the suppliers. Click the Continue button.
Main Purchase Page
Select the supplier from the Supplier drop down menus. You can change the supplier at any time.
In the Purchase Details box on the right side of the page there is an Order Number field. This field is
optional.
In the body of the page, please select the product you wish to purchase from the Products drop down
list. Enter the quantity of the product you are purchasing. The delivery date is the date on which you
will be receiving your products. If the product you wish to add is not in the store shown to the left of
the product list, click the Different Store button at the bottom of the order form. This will allow you to
select another store.
To remove a product from the order items list, click the Remove checkbox to the right of the product.
When you have selected all the products you wish to have removed, click the Update button.
When you are done with your purchase, click the Update button. A new button labeled Done will
appear which will record the order.
');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('207','View Orders (Details, Delete, Print, Edit, Receive, Record Invoice)','First Page
Select the date range for which you wish to view purchases. Click the Search button.
Main View Purchases Page
You will see a list of the products which you created an order for. Next to each Order you will see the
following options:
Details: Shows you the purchase in a complete form.
Delete: Here you can delete the order. Note- You cannot delete an order once it has been recorded as an
invoice.
Print: This will generate a printable order.
Edit: Edit the purchase (remove products, add products, change supplier, etc..)
Received: When you have received a purchase click the Received option to add the products to your
stock list. Now fill in the number of items you have received. After you have completed it, click the
Write button.
Record Invoice: If you are recording the invoice before you have received the goods, click here to do
that.
Receive and Record Invoice: This is when you receive the goods and invoice at the same time. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('208','View Received Purchases (ReturnDetails)','This option will give you a listing of the purchases you have received.
First Page
Enter the range of the date for which you wish to see purchases you have already received.
NOTE:This date is the date on which you created the purchase, not the date you received it.
Next, select the period this order was placed in. Click the Search button.
Main Page
You will see a listing of all the received purchases matching the criteria supplied on the previous page.
Next to each entry you will see two options, Details and Return.
The Details option will give you detailed information about the purchase.
The Return option allows you to return a certain number of items you received in this order. When you
have clicked on this option you will get to a page where you have to enter the amount of each product
you wish to return. When you are done with this, click the Write button. This will automatically
deduct the selected products from the stock listings.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('209','View Returned Purchases ','Here you will be able to view all the orders you have returned.
On the first page select the date range and month of the returned orders you wish to view and click the
confirm button.
A list of all the returned orders will be displayed with an option to view details next to each one.
If you click on the details option next to a returned invoice you will be able to view all the details of
that returned order.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('210','New Non Stock Order','This allows you to record a purchase you made from one of your suppliers.
Main Purchase Page
Enter the Name and Address of the supplier you wish buy from.
In the Purchase Details box on the right side of the page there is an Order Number field. This field is
optional.
In the body of the page, enter the product number, description and unit price. The delivery date is the
date on which you will be receiving your products.
To remove a product from the order items list, click the Remove checkbox to the right of the product.
When you have selected all the products you wish to have removed, click the Update button.
When you have completed your purchase, click the Update button. By clicking on Done, it will
record the order.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('211','View Non Stock Order (Received, Cancel, Details, Print, Edit)','First Page
Select the date range for which you wish to view purchases. Click the Search button.
Main View Purchases Page
You will see a list of the products which you created a purchase for. Next to each Purchase entry you
will see a view option. Following this is a description of the options:
Details: Shows you the purchase in a complete form.
Edit: You can edit the purchase (remove products, add products, change supplier)
Cancel: Cancel a purchase.
Received: When you have received a purchase click this option to add the products to your stock list.
You will be taken to a page where you need to fill in more details of the purchase. In the supplier
details box at the top left of the page, select the account from which you are paying for the purchase. At
each product line enter the quantity of the items which you have received and select which the account
into which this products is being stored. After you are done, click the Write button.
Print: Gives you a printable non stock order that you can print out.
You can export your non-stock orders to a spreadsheet, by clicking on the export to spreadsheet
button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('212','New International Order','This allows you to record an international purchase you made from one of your suppliers.
First page
Select the department and optionally fill in the first letters of the suppliers. Click Continue button.
Main Purchase Page
Select the supplier from the Supplier drop down menus. You can change the supplier at any time.
In the Purchase Details box on the right side of the page there is an Order Number field. This field is
optional. There is also a Foreign Exchange option. Select the currency of the supplier you are buying
from. Then enter the exchange rate as it is at the time the purchase is made. In the body of the page,
please select the product you wish to purchase from the Products drop down list. Enter the quantity of
the product you are purchasing. Enter the Unit Price either in the currency you selected or in Rands.
Enter the cost of Duty. Only one of the Duty boxes need to be filled in, either in currency or in
percentage. If the product you wish to add is not in the store shown to the left of the product list, click
the Different Store button at the bottom of the order form. This will allow you to select another store.
To remove a product from the order items list, click the Remove checkbox to the right of the product.
When you have selected all the products you wish to have removed, click the Update button.
Once you have completed your purchase, click the Update button. The Done will appear which will
record the order.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('213','View International Orders (Print, Edit, Cancel, Receive, Invoice)','First Page
Select the date range for which you wish to view purchases. Click the Search button.
Main View Purchases Page
Displays a list of the products which you created a purchase for. Next to each Purchase entry you will
see view options. Following this is a description of the options:
Details: Shows you the purchase in a complete form.
Edit: Edit the purchase (remove products, add products, change supplier, etc.)
Cancel: Cancel a purchase.
Received: When you have received a purchase click this option to add the products to your stock list.
Once you have clicked on Received, fill in the number of items you have received on the following
page. Once you have completed this, click the Write button.
');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('214','View Received International Purchases','This option will give you a listing of the purchases you have received.
First Page
Enter the date range for which you wish to see purchases you have already received.
NOTE:This date is the date on which you created the purchase, not the date you received it.
Next, select the period this order was placed in. Click the Search button.
Main Page
You will see a listing of all the received purchases matching the criteria supplied on the previous page.
Next to each entry you will see two options, Details and Return.
The Details option will provide you with detailed info about the purchase.
The Return option allows you to return a certain number of items you received in this order. When you
have clicked on this option you will get to a page where you have to enter the amount of each product
you wish to return.
When you have completed this, click the Write button. This will automatically deduct the selected
products from the stock listings.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('215','New International Non Stock Order','On the first page select the International Supplier you wish you create an order for and click the
continue button.
Fill in all the necessary information and click the update button.
If you are finished with the order, but have not received the non stock items, click the done button.
This will move the order to view international non stock orders
If you have received the non stock item click the received button. On the next page you will have to
select a Ledger Account to allocate the expense to. Once you have done this click the confirm button
and then the write button. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('216','View International Non Stock Orders','On the first page select the date range and click the search button
A list of all your International Non Stock Purchases will be displayed, with the following options next
to each one:
Details: Here you can view the details of the purchase.
Edit: Here you can edit the details of the purchase.
Received: Click here when you receive the non stock purchase. Fill in the amount that you are
receiving and the Account you want the non stock to be stored in from the drop down menu, and then
click the confirm button and then the write button.
Cancel: If you have not received the non stock purchase yet, or recorded the invoice, you can cancel the
purchase');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('217','View Suppliers ','List of all your suppliers. The last line will show the total amount of money due to all suppliers. Next to
each supplier line there are options to affect the supplier account.
Add Payment: This allows you to add a payment you make to your supplier. From the first drop down
menu select if it is a cash payment, or from your bank account. Enter the date, description, cheque
number (if appropriate) and the amount of the payment. Then from the drop down menu select which
allocation method you want to use. Auto: Allocates the payment to the oldest invoice first. Age
Analysis: Allocates the payment to the age analysis. To each invoice: A list of all the invoices will be
displayed and you can allocate the amount you want to each invoice. Once all the information has been
filled in click the allocate button and then the write button.
Details gives you more details about the supplier. The same as which you entered on the New Supplier
page.
Statement will list every transaction you made concerning the supplier, ie. payments and purchases, in
a printable form. Clicking on the print button at the bottom of this printable screen, will not print the
statement. All that will do is remove all the buttons at the bottom of the screen, so that you can then
print the statement without buttons on. Press control and P to print, or go to file and print.
Transaction allows you to record a transaction with the supplier. More info below.
Edit and Remove allows you to edit and remove suppliers. You may only remove the suppliers which
you have not made any transactions for.
Block: You can block this customer by clicking on this option. No transactions will then be able to be
done with this customer.
Add Contact: Here you can add a contact for this supplier.
You can also email the statements to the suppliers, by selecting the supplier you wish to email and
clicking on the email statement button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('218','Suppliers Transactions','When clicking on the transaction option, you will be able to record payments and purchase transactions
made with the supplier. Reference number is automatically generated for you, you can change the
reference number but it is not necessary. Enter the date on which the transaction was made. Select
whether this was a Debit or Credit transaction. In the Contra Account field you select your account
from which you are doing this. If you selected to Debit the supplier account, you will Credit the the
Contra Account, and visa versa. Under Contra Account you can either select from the list OR fill a
custom description into the text box below the selection. Thereafter click the Enter Details button.
On the enter details page you just enter the amount, the descriptiondetails, and click the Record
Transaction button. You will be asked to confirm this transaction.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('219','Goods Received
Purchase Receive','Here you can record when an order is received.
Fill in the Purchase number and click the continue button.
The order you have just typed in will appear on the screen. Here you can enter a delivery note number.
Enter the amount of goods you have received, the date you received them on and any comments you
may wish you add. Make sure that the received select box has been ticked, if you have received the
goods.
Once you have done this click the confirm button and then the write button. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('220',' Bank: Payment To Supplier','When you pay a supplier, it is recorded here. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('221','Bank: Payment To Supplier Steps','Selection
From the drop down menu, select the supplier whom you paid and click the Enter Details button.
Transaction Details
Select the bank account from which you are paying the funds.
Enter the date and description of the transaction. If you paid by Cheque, enter the cheque number.
Enter the amount you paid.
Now decide how you are going to allocate the funds. The different allocations and their descriptions
follow:
Auto: Cubit will decide how it will allocate the funds of the payment. It starts by subtracting available
amounts from the oldest transactions, up to the newest.
Allocate to Age Analysis: Allocate the money manually by selecting how much funds should be used
for which transactions, according to the age of the transaction. This is for transactions that was made
more than 30 days ago, and which has not yet been fully paid.
Allocate to Each Invoice: Allocate the money manually by selecting how much funds should be used
for which invoices.
When you are done click the Allocate button.
Allocation
Automatic Allocation
Cubit decides where it will put the money, and displays where it is allocated to. If you are satisfied with
it, click the Confirm button, else click the Back button for a correction.
Allocation by Age Analysis
Below the general details for the transaction you will see 5 input boxes labeled, Current, 30 Days, 60
Days, 90 Days, 120 Days. Above each of these input boxes the amount owed for that period will be
displayed.
Enter in each input box the amount which you wish to allocate for that period.
When you are done click the Confirm button.
Allocation by Invoice
Below the general details for the transaction you will see an entry for each invoice, with an input box to
its right.
Enter the amount you wish to allocate for each invoice in the area provided.
When you are done click the Confirm button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('222','Bank: Receipt From Supplier','Here you can add a receipt from a supplier, similar to the way you can add a payment to a supplier.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('223','Petty Cash: Receipt From Supplier','Select this option when receiving petty cash from the suppliers account into the petty cash account.
Enter the date and select the Supplier from the drop down menu. Fill in the description and the amount
received. Then click the add button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('224',' Journal
Supplier Journal','List of all your suppliers. The last line will show the total amount of money due to all suppliers. Next to
each supplier line there are options to affect the supplier account.
Add Payment: This allows you to add a payment you make to your supplier. From the first drop down
menu select if it is a cash payment, or from your bank account. Enter the date, description, cheque
number (if appropriate) and the amount of the payment. Then from the drop down menu select which
allocation method you want to use. Auto: Allocates the payment to the oldest invoice first. Age
Analysis: Allocates the payment to the age analysis. To each invoice: A list of all the invoices will be
displayed and you can allocate the amount you want to each invoice. Once all the information has been
filled in click the allocate button and then the write button.
Details gives you more details about the supplier. The same as which you entered on the New Supplier
page.
Statement will list every transaction you made concerning the supplier, ie. payments and purchases, in
a printable form. Clicking on the print button at the bottom of this printable screen, will not print the
statement. All that will do is remove all the buttons at the bottom of the screen, so that you can then
print the statement without buttons on. Press control and P to print, or go to file and print.
Transaction allows you to record a transaction with the supplier. More info below.
Edit and Remove allows you to edit and remove suppliers. You may only remove the suppliers which
you have not made any transactions for.
Block: You can block this customer by clicking on this option. No transactions will then be able to be
done with this customer.
Add Contact: Here you can add a contact for this supplier.
You can also email the statements to the suppliers, by selecting the supplier you wish to email and
clicking on the email statement button.
Transactions
When clicking on the transaction option, you will be able to record payments and purchase transactions
made with the supplier. Reference number is automatically generated for you, you can change the
reference number but it is not necessary. Enter the date on which the transaction was made. Select
whether this was a Debit or Credit transaction. In the Contra Account field you select your account
from which you are doing this. If you selected to Debit the supplier account, you will Credit the the
Contra Account, and visa versa. Under Contra Account you can either select from the list OR fill a
custom description into the text box below the selection. Thereafter click the Enter Details button.
On the enter details page you just enter the amount, the descriptiondetails, and click the Record
Transaction button. You will be asked to confirm this transaction.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('225','Petty Cash: Pay Supplier','Select this option when paying a supplier with petty cash.
Enter the date and select the Supplier from the drop down menu. Fill in the description and the amount
paid. Then click the add button');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('226','View Canceled Orders','Before receiving the goods or recording the invoice, you can cancel an order under view order.
Under view canceled purchases you can view all canceled orders.
On the first page select the date range for which you wish to view the canceled orders and click the
search button.
A list of all your previously canceled orders will be displayed. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('227','View International Returned Purchases','On the first page select the date range and month you wish to view the international purchases. Then
click the search button.
A list of all your purchases will be displayed with an option next to each to view the details. Click on
this details option if you wish you view the details of a returned purchase.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('228','View Received Non Stock Purchases (Return, Details)','This option will give you a listing of the non stock purchases you have received.
First Page
Enter the dates for which you wish to see purchases you have already received.
NOTE: This date is the date on which you created the purchase, not the date you received it. Next,
select the period in which this purchase was made. Click the Search button.
Main Page
You will see a listing of all the received purchases matching the criteria supplied on the previous page.
Next to each entry you will see an option to view the details of the received purchase.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('229','View Returned Non Stock Purchases (Details)','5.2.5. View Returned Non Stock Purchases (Details)
On the first page select the date range and click the search button.
A list of all your Returned Non Stock Purchases will be displayed, with an option to view the details next to each one. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('230','View Received International Non Stock Purchase','On the first page select the date range and the month you wish to view the purchases, then click the
search button.
A list of all your received international non stock purchases will be displayed, with the following
options next to each one:
Return: Here you can select to return items on the purchase that you have already received. Select the
quantity that you are returning and click the confirm button and then the write button.
Details: Here you can view all the details of the International Non Stock Purchase.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('231','New Linked Non Stock Order','nter the order number of the order you want to link and click the enter button.
On the next page enter all the information of the non stock order you are linking to the normal order.
Once you have done this click the update button.
If you have received this non stock item then click the received button.
If you have not as yet received the non stock items, then click the done button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('232','Add Supplier','Here you can add suppliers to your list of suppliers. Enter the details of the supplier and press the
Confirm button. You will be asked to confirm the details. Then click the Write button.
A description of the fields are as follows:
Department: The department whose supplier this is for
Supplier Number: This will be filled in automatically for you
The banking details are the details of the suppliers bank account into which you deposit payments.
The rest of the supplier details boxs fields are self explanatory.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('233','Find Supplier','This helps you find a supplier. Either select a filter from the drop down menu and then enter the
relevant information and then click the apply filter button. Or click the view all button, if you wish to find all the suppliers.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('234','Add Supplier Group','Here you can add supplier groups.
On the first page enter the name you wish to give this supplier group. Then click the confirm button.
Check that the information is correct and click the write button.
You supplier group will then be recorded in the system.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('235','View Supplier Group (View, Add Supplier)','If there are no suppliers allocated to this group as yet. You can add to the groups here.
To add a supplier to a group: Select a supplier you wish to add to the group from the drop down menu.
The drop down menu will be next to the group. Then click the add button. You can add as many
suppliers to a group as you choose.
If you wish to remove a supplier from the group, tick the tick box next to the supplier and then click the
remove select button.
If there are already suppliers added to the groups. A screen of the groups and the suppliers in them will
be displayed. You can still add more or remove suppliers tofrom these groups.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('236','Add Price List','In Cubit you have the option of creating multiple price lists, where you can specify the price of each
stock item. This way you can allocate a price list to each customer in effect giving different prices to
different customers.
Enter the price list name in the field provided.
In the list below you will see all the stock items you have, enter a price for each of them.
Once you have completed the price list, click the confirm button. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('237','View Price List (Details, Print, Edit, Copy, Remove)','A list of all your price lists will be displayed.
Next to each price list you will see five options: Details, Print, Edit, Copy and Remove.
Details: Will show you the price list.
Print: Will display a printable prince list for you, which you can then print.
Edit: Allow you to edit the name or the prices of items on the price list.
Copy: Present you with a form similar to that of Add Price list, but with the selected price lists values
completed. From here you can change the prices and the names. Click the Confirm button to actually
create the price list.
Remove: delete the price list.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('238','Add Supplier Price List','Here you will be given a list of all the stock items. You can then deselect stock items which you do not
want to have on this suppliers price list, by clicking on the block on the right of the stock items, to
remove the ticks.
You can also change the stock items prices. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('239','View Supplier Price List (Details, Copy, Edit, Remove)','A list of all your suppliers price lists. Next to each one there are four options: details, edit, copy,
remove.
Details: You can view the details of the price list.
Edit: You can edit the name of the price list and the stock items prices.
Copy: Presents you with a form similar to that of Add Supplier Price list, but with the selected price
lists values completed. From here you can change the prices and the names. Click the confirm button
to actually create the price list.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('240','Suppliers Transaction Reports','Displays each supplier with a report of all the transactions you have undergone with them.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('241','Creditors Age Analysis','Here you can view a list of your suppliers, with a Creditors Age Analysis for each one. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('242','Creditors Ledger','On the first page, Either select all or select the supplier you wish to view the General Ledger of. Then
select the month you wish to view from the drop down menu. Select if you want the order to be by
transaction date (The date of the transaction that is required by GAAP) or by system date (The date the
transaction was entered into the system). Then click the confirm button.
The Creditors Ledger that you have select will be displayed on the screen. You can export this ledger
to a spreadsheet, by clicking the export to spreadsheet button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('243','Print Suppliers List','A printable screen will be displayed of a list of all the suppliers and the amount outstanding to each
one, along with the total outstanding amount owed to all the suppliers.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('244','View Previous Year Creditors Ledger','Here you will be able to view Creditors ledgers from previous years. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('245','Q: How do I set it up so that I can sell items at different prices to different customers?','A: You do this by setting up different price lists and allocating them to your debtors.
To set up different price lists go to Admin, Settings, Stock, Add price list, in the drop down menu.
Then once you have added different price lists, when you add customers, you can add different price
lists for each one. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('246','Q: How do I create a Purchase?','A: Go to Purchases, Order, New Order from the drop down menu. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('247','View Stock (Report, Details, Edit, Transaction, Bar code, Block, View
Allocation) ','First Page
This will create a list of all the stock you currently have at your stores. From this list you can viewedit
the stock item. Select the store from the Store drop down list. Select the Category of items you wish
to see. Select the Classification. Then click the Search button.
Main Page
snapshot19.png
You will see all of the stock matching the criteria you created on the previous page. Next to each item
there is a list of options.
Following is the descriptions of all of the options:
Report: Gives you more info on the stock item. It is important to note that only the selling price of the
stock item is displayed in this report and not the cost price. The stock allocations can also be viewed
here.
Details: Shows the details you entered when you created the stock item. The selling price along with
the cost price of a stock item is displayed under details. If you have uploaded a stock image, this is
where it will be displayed.
It is important to be aware of the difference between report and details when adding a user. You may
not want certain users to know what the cost price of the stock items.
Edit: This allows you to Edit the details of the stock items. The stock code can also be edited here.
When changing the stock code, all transactions and invoices with that stock code will be changed to the
new stock code.
Transaction: Record a transaction regarding the stock item. This is used when you made an error on
your salespurchases, and you need to correct this, or when you need to decrease stock due to theft,
damage etc. Remember to add an expense account for stock loss, before decreasing your stock due to a
loss.
Barcode: If instead of a stock item TYPE having a barcode, you give each SEPERATE item its own
barcode in this option.
Block: Selecting this option prevents you from doing any transactions with this item until it is
unblocked. This includes purchases and sales.
Remove: Remove this item.
You can export this entire page to a spreadsheet, by clicking on the export to spreadsheet button. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('248','Receive Stock','On the First page select the date range and click the search button.
Main Page
Here you will see a list of all the stock that has not yet been received, but that you have ordered. Next
to each stock item there will be an option to receive the stock or to receive and record the invoice.
This enables you to work in real time.
This works the same as Receive Order, under purchases. You can record that you have received the
stock under either of these options. They will update each other.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('249','Return Stock','On the First page select the date rage and click the search button.
Main Page
Here you will see a list of all the stock items, with the option to return the stock next to each stock item.
There is also an option to view the details of the stock and to record a credit note.
You can export this whole page to a spreadsheet, by clicking on the export to spreadsheet page.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('250','Stock Search','If you need to match stock to a certain criteria, eg. all stock that has the word Can in their name, you
can use this feature to list them.
First Page
Here you need to specify what you want to search for. In the first drop down menu you select the store
whose stock you would like to search through. The second one you select the field you would like to
search. Then type a keyword that the values of the search should contain. Click the View button.
Results
A list of all the stock items with this key word will be displayed. Next to each stock item there will be
similar option as in view stock.
You can export this whole page to a spreadsheet by clicking on the export to spreadsheet button. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('251','Stock Taking','When you count your stock, you need to have reports of what you should have in stock. This function
keeps record of what you should have in stock.
Select the store whose stock you are taking. Then you can view the stock by category, classification or
all of the stock at once. To view by Category or Classification, select the appropriate option from the
drop down box, click the View button. To view all, click the View All button.
It will display a report of the stock you searched for.
This can then be printed and used when you do your stock taking.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('252','Stock Transfer (Store)','Stock transfer (Store) is used to transfer stock between different stores. To transfer stock, first select
which stock you wish to view and click the view button. A list of all the stock in the store, category,
classification you selected will appear. Next to each stock item will be the option to transfer. (You
cannot transfer negative stock. If you do not have any stock on hand, then there will be no option to
transfer that particular stock item)
Click this and fill in the transfer details, and click the button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('253','Stock Transfer (Branch)','Stock transfer (branch) is used to transfer stock between different branches. To transfer stock, first
select which stock you wish to view and click the view button. A list of all the stock in the store,
category, classification you selected will be displayed. Next to each stock item will be the option to
transfer. Click this and fill in the transfer details, and click the button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('254','View Stock In Transit','Here you can view stock that has been transferred.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('255','Find Stock Serial Number','This helps you to find your stock. Type in the stock serial number and click the continue button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('256','Available Stock','Displays a list of all the available stock. this report shows you the stock, available units, cost amount,
minimum level, maximum, selling price.
You can export this report to a spreadsheet, by clicking the export to spreadsheet button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('257','Stock Levels','Selection Page
Select how you would like the stock level reports generated. This can be done by Category or
Classification. To view by category select the category from the drop down menu, under Category
heading and click the View button. Do the same under the Classification heading, to view by
Classification.
You can also select to only view the stock that is below minimum levels. If you do not wish this, make
sure you un-tick this tick box.

To view all the stock levels, click the View All button.
A list of all the stock levels will be displayed. Next to each stock you will be able to see the available
amount, minimum level and maximum level. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('258','Stock Levels','Selection Page
Select how you would like the stock level reports generated. This can be done by Category or
Classification. To view by category select the category from the drop down menu, under Category
heading and click the View button. Do the same under the Classification heading, to view by
Classification.
You can also select to only view the stock that is below minimum levels. If you do not wish this, make
sure you un-tick this tick box.

To view all the stock levels, click the View All button.
A list of all the stock levels will be displayed. Next to each stock you will be able to see the available
amount, minimum level and maximum level. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('259',' Stock Sales Report','On the first page select how you wish to view the Stock Sales Report. You can either select the
category or classification. If you wish to view all the stock click the view all button.
On the next page select the stock item you wish to view from the drop down menu and the date range,
then click the continue button.
A report of the stock sales for that stock will be displayed');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('260','Stock Sales Report (By Invoice)','On the First page select the date range and click the view button.
Your Sales report will be displayed on the screen, separated into invoices. Your Credit notes will also be displayed below your Sales Report.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('261','Stock Movement Report','On the first page select the store and the date range and click the view all button.
A report of the stock movement will be displayed. You can export this report to a spreadsheet, by
clicking on the export to spreadsheet button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('262','View Previous Year Stock Ledger','Here you will be able to view Stock ledgers from previous years.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('263','Add Stock','Before you can create any invoicesquotationsorders you need to add stock to your list. This option
will allow you to add stock to your list.
Adding stock
Fill in the form for on the Add Stock page. After you have done this, click the Write button. You will
be asked to confirm what you entered. Click the Confirm button.
Following this is a description of the different fields you need to fill in.
Serial Number: The serial number of the product you are selling.
Stock Code: Identification of the product. This can contain both letters, numbers and spaces. It can also
be the full name of the product
Stock Description: A description of the product. Useful for when the Stock Code is in unreadable
form, and you want the invoice to be understood by the customer.
Type: What type of product this is.
Category: The category to which you wish to add this product.
Upload stock image: If you select yes here, then you will be able to upload a image of the stock item.
Buying Unit of Measure: When you purchase a product you purchase it in Units, this is the
measurement of units, eg per 5 meters, so you would enter a 5 in this field.
Selling Unit of Measure: Same as above.
Selling Units Per Buying Unit: If you bought the product in one unit, but you sell it in a different unit,
you specify how many units of the Selling units you have, for each Buying unit. eg. you buy breads per
dozen, but you sell them separately, so you have 12 selling units per buying unit.
Location: In bigger companies, this simply helps find the product in the store.
Selling price per unit: The price you are selling it for.
Minimum and Maximum level: The minmax amount of items you may have in your stock. When your
stock reaches this limit you will receive a notification. This will not prevent you from continuing with
what you are doing it is just a reminder that your stock limit has been reached.
Bar Code: If you use a bar coded system, you can enter the bar code for the item here.
VAT code: Select the VAT code for this products VAT type.
Mark Up Percentage: Enter the percentage mark up from cost that you set you selling price at. For
example an item with a cost price of 10 is sold at a mark up of 10%. Therefore the selling price is 11.
Supplier 1-3: Select the suppliers you prefer to purchase this item from.
Click the confirm button and then the write button. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('264','Add Stock Category','Stock category allows you to divide your stock so they are easier to manage. Enter the details of the
stock item, and click the Add button.
Category Code: Is a code to identify the category. This can consist of numbers and letters.
Category Name: Name of the category you are adding.
Description: Description of the item.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('265','View Stock Categories (Details, Edit)','Displays the list of all the stock categories. Next to each option you see three options: Edit, Details and
Remove.
Details: Displays more info about the stock category.
Edit: Allows you to Edit the category.
Remove: Deletes the category. However you cannot delete any categories which still have stock.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('266','Add Stock Classification','Stock classifications allows you to divide your stock so they are easier to manage. Enter the details of
the stock item, and click the Add button.
Classification Code: Is a code to identify the classification by. This can consist of numbers and letters.
Classification Name: Name of the classification you are adding.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('267','View Stock Classification (Edit, Remove)','Displays the list of all the stock classification. Next to each option you see two options: Edit and
Remove.
Edit: Allows you to Edit the Classification.
Remove: Deletes the category. However you cannot delete any categories which still have stock.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('268','Add Store','This will allow you to create more stores.
Enter the store number, this can be any number to identify the store, and the store name.
Select the account related to this stores stock.
Select the account related to sale costs.
Select the account related to stock Control.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('269','View Store (Edit, Remove)','Shows you a list of all the stores you have added.
Next to each store you have two options, Edit and Remove.
Edit allows you to Edit the stores info, and Remove will delete it.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('270','Increase Selling Price','There are two ways to increase the selling price of your stock. Either by Category or Classification.
Select the price list from the price list drop down you wish to increase selling price for. Select whether
you wish to increase by percentage or manually. If you choose to increase by percentage all items of
the specified criteria will be increased by this percentage. If you choose manually, a form will be
displayed for you to fill in all the values.
Next choose the type of items under price list you wish to increase the price of. You can increase all
items under a certain category, or all items under a certain classification. To do this select from the
drop down menu under which you want to increase and click on the Increase button next to it.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('271','Set Default Store','This will set the store that is selected by default whenever you do purchases, invoices and quotations.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('272','Set Selling Price VAT Type
','This option will set whether your selling price for stock is VAT Inclusive or VAT exclusive. Once
you have made your selection you can still select different VAT options for each individual invoice,
setting the VAT type is merely to select your default VAT option for stock.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('273','Set Stock Purchase Approval','Here you can select if purchases must be approved or not. Quick setup selects that purchases must be
approved for you, but you can change it if you so wish.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('274',' Stock Point Of Sale Setting','With point of sale you can assign bar codes to stock, and enter the bar code of the item to identify it.
The Point of Sale settings is where you select how these bar codes are allocated. You can allocate bar
codes in two ways, either by giving a product type a bar code, or giving each separate product its own
bar code, in other words no items share bar codes.
To select the product type allocation, choose Yes from the drop down menu and click the Confirm
button.
To select separate bar code allocation, choose No from the drop down menu and click the Confirm
button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('275','Set Cost Variance Account','Here you can select a cost category to link to cost variance. Quick setup has set this up for you.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('276','Q: When adding stock to a warehouse, does all the stock need to be assigned to a warehouse before a
price list can be added?','A: You cannot have stock without it belonging to a warehouse, so yes.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('277','Q: How do you transfer stock to a warehouse?','A: Under the Stock menu: Transfer Stock and follow the on-screen instructions.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('278','Q: How do I transfer stock from one store to the another?','A: Go to Stock, transfer stock (store), in the drop down menu.
Before you can transfer stock between stores, you need to set up other stores. You do this under
(settings, stock, add store).
You will only be able to transfer stock that you have on hand. You cannot transfer negative stock. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('279','Q: How do I add stock items excluding VAT?','A: Go to Admin, Settings, Stock, set selling price VAT type. Here you can select if you want ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('280','Employee Journal','A list of all the employees are displayed. with the salary that is due to this employee. Next to each
employee there are the following options:
Details: View detailed information about the employee. The same details as entered when you added
the employee.
Year to date: Select the date range and print a salary slip for this date range.
Pay: Here you can select to pay the Employee. Enter the amount you are paying and click the confirm
button, then the write button.
Transaction: Here you will be able to enter a Transaction with this specific employee.
IRP5:
IT3(a):
View Documents: Here you can view documents that have been saved for this employee.
Edit: Edit the information displayed when you click Details.
Expense Account: Here you will be able to nominate the account to which cost to company expenses
must be posted to. It is done by selecting the desired account in the drop down menu. If the account
does not exist, you must create on first (under Admin, Settings, Accounts, Add new Account).
View Available Leave: Here you can view the employees available leave.
Left Company: This is filled in when the employee leaves the company. By filling this in you will
move the employee to past employees. Here you can fill in the date they left and the reason for them
leaving.
You can also process the employees salaries per batch. Select the employees you want to process by
ticking the tick boxes next to the employees and click one of the process button. Process daily salaries
for example. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('281','Pay Salaries','Select the Pay option next to the employees name in the employees window.
Enter the amount you are paying and click the confirm button, then the write button. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('282','Process Salary Per Individual','This allows you to process each staff members salaries for a specific month.
IMPORTANT NOTE: Once you have processed a staff members salary, you cannot process it again
for the same month.
First page
Select the employee and the month for which you wish to process the salary. Click the Process button.

Main Page
The Basic Salary field is the basic salary of the employee.
It will then give you the total hours that they should have worked. Below this you can fill in the actual
amount of hours that they worked.
Then you can fill in the hours they worked over time or on a public holiday.
The Special BonusAdditional Salary is a once off amount. It is not an annual Bonus that they get ever
year.
With the Commission field you can give the employee an extra amount of money for any reason.
The Travel allowance, medical aid, etc, will be filled in for you if you entered these amounts when
adding an employee and calculating their salary.
If you need to change the PAYE amount, click the override PAYE tick box and type in the amount of
PAYE you are going to pay them.
Then click the confirm button.
The Net pay + Reimbursements is the amount that you need to pay. If you are paying the employee
now, fill this amount in below. If you are just processing this salary and are not paying now, leave this
amount as 0.
Click the write button.
A new window will open with a printable employees salary slip.
Once you have processed an employees salary, Cubit will not allow you to process the salary again for
the same month (if they are monthly employees), week (if they are weekly employees) and so on. This
is a protection for you.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('283','Process Salary Per Individual','This allows you to process each staff members salaries for a specific month.
IMPORTANT NOTE: Once you have processed a staff members salary, you cannot process it again
for the same month.
First page
Select the employee and the month for which you wish to process the salary. Click the Process button.

Main Page
The Basic Salary field is the basic salary of the employee.
It will then give you the total hours that they should have worked. Below this you can fill in the actual
amount of hours that they worked.
Then you can fill in the hours they worked over time or on a public holiday.
The Special BonusAdditional Salary is a once off amount. It is not an annual Bonus that they get ever
year.
With the Commission field you can give the employee an extra amount of money for any reason.
The Travel allowance, medical aid, etc, will be filled in for you if you entered these amounts when
adding an employee and calculating their salary.
If you need to change the PAYE amount, click the override PAYE tick box and type in the amount of
PAYE you are going to pay them.
Then click the confirm button.
The Net pay + Reimbursements is the amount that you need to pay. If you are paying the employee
now, fill this amount in below. If you are just processing this salary and are not paying now, leave this
amount as 0.
Click the write button.
A new window will open with a printable employees salary slip.
Once you have processed an employees salary, Cubit will not allow you to process the salary again for
the same month (if they are monthly employees), week (if they are weekly employees) and so on. This
is a protection for you.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('284','Process Salary Per Batch','A list of all the employees are displayed. with the salary that is due to this employee. Next to each
employee there are the following options:
Details: View detailed information about the employee. The same details as entered when you added
the employee.
Year to date: Select the date range and print a salary slip for this date range.
Pay: Here you can select to pay the Employee. Enter the amount you are paying and click the confirm
button, then the write button.
Transaction: Here you will be able to enter a Transaction with this specific employee.
IRP5:
IT3(a):
View Documents: Here you can view documents that have been saved for this employee.
Edit: Edit the information displayed when you click Details.
Expense Account: Here you will fill in all the expense accounts, where the companys contributions
will be expensed to for the deduction to the employee. This has been set at default expense accounts.
View Available Leave: Here you can view the employees available leave.
Left Company: This is filled in when the employee leaves the company. By filling this in you will
move the employee to past employees. Here you can fill in the date they left and the reason for them
leaving.
You can also process the employees salaries per batch. Select the employees you want to process by
ticking the tick boxes next to the employees and click one of the process button. Process daily salaries
for example. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('285','Add New Employee','Before adding an employee it is important to review your salary system with reference to the make up
of your salary structures or packages. The issues to be addressed are deductions from employees
salaries and allowances to employees. Below is a brief summary of how to configure Cubit to your own
needs:
Add New Employee
You must add a new employee only after you have set or configured Cubit to satisfy your own
particular needs. The notes below will assist you to do this.
When you have configured Cubit, click on Salaries-Processing-Add New Employee and follow the
instructions.
To add an employee fill in the employee details and click the Confirm button. You will be asked to
verify the details, then click the Write button.
The required fields in this form are the First name, Surname, Basic Salary, the ID number and at least
one list of the Residential Address.
snapshot21.png
Calculate Salary: Click on the calculate salary button.
On this screen add the salary type (monthly, weekly, etc). Add in the remuneration per annum. The
bonus is a bonus that is paid every year. It is not a once off bonus.
When adding a per hour employee, remuneration per annum can be left as 0 if you are unsure of
the number of hours this employee will be working. Then when processing the employees salary you
will be given the option to fill in the rate per hour and directly beneath this check box the number of
hours this employee worked. By doing this your PAYE, UIF etc will all be calculated correctly.
All the amounts under the block with the red writing are monthly amounts. Make sure that you convert
the amounts to monthly amounts before filling them in here.
snapshot20.png
Bonuses:
When a bonus, other than an annual bonus, is paid to an employee the system assumes that such a
bonus will be of a recurring nature. In other words, if
not amended by the user, the employee will suffer P A Y E in all months subsequent to the payment of
such bonus until the end of February of the next
year. Where an employee is employed at a fixed salary supplemented by, for instance performance
bonuses, it is best for of the employee to apply for a
directive from SARS in order not to suffer abnormally high P A Y E deductions from hisher
remuneration. Directives are recommended in cases where employees receive more than one bonus per
years. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('286','Reverse Salaries','If you have processed an employees salary and have made a mistake, you can reverse that salary here.
By doing this you will create the opposite entries to the ones you made when processing the salary. In
that way the salary will be reversed. Once you have done this, you will then have to process the
employees salary again.
Select the employee whose salary you wish to reverse and the month you wish to reverse it for. Then
click the process button.
If nothing changes, click the confirm button. Then enter the amount (if you necessary) and click the
write button.
A new salary slip will be displayed that you will be able to print.
The salary has now been reversed.
Note: In the unlikely event that you will reverse hourly salaries paid to an employee, be sure to enter
the number of hours in respect of which such a reversal applies. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('287','Grant Employee Loan','Record a loan made to an employee.
Enter the amount of the loan. Enter the monthly interest that will be charged and the amount of months
in which it will be paid.
You can either select a bank account that the loan must come out of, or a ledger account. In the absence
of an existing account you can create a new general ledger account.
Note: The interest is set at 8% by default, if you set it at anything less then this it will become a low
interest loanFringe benefit.
A loan granted to an employee at interest rates that are lower than the official rate is considered to
be a taxable benefit. The first issue to consider is whether the loan will be repaid to the employee
in cash or whether a creditor must be created. In the later case a creditors account must be
created: General Ledger-Regular Processing- New Account: Add. Then click on salaries-
processing-loan: grant. After entering the employees name fill in the required details of the loan.
Note: Before entering the Employee loan account, it is essential to first create a sub-account for the
employee loan account. You can do this under Admin, Settings, Accounts, Add new accounts. For
example the main account will be 6700000 employee loan account. You create a sub account called
6700001 Gail- employee loan account.
Click the Confirm button.

The interest and the monthly amount paid back in installments will be calculated for you. To see what
the monthly installment is, go to view loan.
The monthly installment is deducted from the employees salary. When you process the employees
salary, you will see that the monthly installment appears under low or interest free loanloan
installment, and will be deducted from the employees salary. Fringe benefit tax is automatically
calculated and taken into consideration for PAYE purposes. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('288','View employee Loans (edit)','Lists the current loans. This tell you what the loan amount is, including interest, what the outstanding
amount is, what the monthly installment is, the interest rate and the length of the loan.
To edit a loan, click the Edit option. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('289','Add Salary Deduction','The first issue is whether the deduction from an employee salary would be credited to an in-house
account or whether the deduction is made in or to settle an employees personal debt. In the former case
you must create an in-house account and in the latter case you will first have to create a creditors
account. This is done by clicking on general ledger-regular processing-account new: add. Then proceed
as follows: click on salaries-processing-salary deduction: add: make your selection between in-house
and creditor. Note that the default is set to ignore such a deduction for the purpose of PAYE. Link
your selection to salaries by following the instructions.
First Screen
If a certain deduction does not appear as an option under calculate salary in add employee then you
can add it to the list here.
Firstly, give the deduction a name, for example Medical Aid.
Then give the creditor a name, or you can click on the in house option to make it in house. This is just
for reference purposes.
Select the category of the account you are deducting from, using the drop down menu.
Enter details of the creditor, eg. a telephone number. The deduct before PAYE option, selects whether
or not tax should only be subtracted from the salary after the deduction has been made. If this is set to
no then tax will be subtracted, and only then will the deduction be made.
When you are done click the Confirm button.
Account selection screen
From the drop down menu, select the account you want to deduct from. Click the Write button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('290','View Salary Deductions (editremove)','This will list all the types of salary deductions you have created.
Next to each one you can select one of two options, Edit or Delete.
With Delete you can remove the salary deduction.
With Edit you can edit all the details of deduction, except the account it is deducted from.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('291','Add Allowance','Before you can add a new allowance you have to decide where the cost of the allowance should be
posted to. If you wish the allowance to be posted to salaries in default account number 2500000 then
you need not create a new account. However, if you want the cost of the allowance to be accumulated
in a different account, you need to first create a new account. This is done by clicking on: General
Ledger-Regular Processing-Account New: Add. After adding new accounts proceed as follows: click
on Salaries-Processing-Allowance Add: give the allowance a name and instruct Cubit to either
withhold PAYE or to ignore the allowance for the purpose of PAYE. The default is to subject the
allowance to PAYE. Link the allowance to an existing account or to the new account that you have
created by selection from the dropdown menu.
First Screen
If a certain allowance does not appear in the allowance list when calculating a salary under add
employee, then you can add it here, and it will appear on the list..
Note: This amount will be treated as a monthly allowance.
Firstly, give the allowance a name, for example Faulty Deduction.
From the drop down menu select the category of the account you are giving the allowance from. The
Add before PAYE option, sets whether or not tax should only be subtracted from the salary after the
allowances is added. If you select no, then tax will be subtracted, and the allowance will be added.
When you are done click the Confirm button.
Account selection screen
From the drop down menu, select the account you are giving the allowance from. Click the Write
button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('292','View Allowances (editremove)','This will list all the types of salary allowances you have created.
Next to each one you can select one of two options, Edit or Delete.
With Delete you can remove the salary allowance.
With Edit you can edit all the details of the allowance, except the account it comes from.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('293','Add Fringe Benefits','If a certain Fringe Benefit does not appear in the allowance list when calculating a salary under add
employee, then you can add it here, and it will appear on the list..
Give the Fridge Benefit a name and click the confirm button and then the write button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('294','Add Reimbursement','If a certain reimbursement does not appear in the allowance list when calculating a salary under add
employee, then you can add it here, and it will appear on the list..
Enter the Reimbursement name
The account category is set on expense by default. Click the confirm button.
Select a reimbursement account from the drop down menu and click the write button. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('295','View Salaries Paid','Select the date range for the payslips you wish to view. Click the view button.
A list of all your employees paid in this month will be displayed with the following options next to
each one:
View: This allows you to view the paid salary.
Print: This gives you a printable salary slip.
You can export this list of your employees to a spreadsheet by clicking the export to spreadsheet
button. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('296','View Salaries Reversed','Here you will be able to view a salary that you have reversed.
Select the month you want to view and click the view button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('297','View Employee Salaries','Select the employee and month you wish to view and click the view button.
The selected employees detailed salary will be displayed, with the following options next to it:
View: This displays the details of the employees salary underneath each other, instead of next to each
other, as on the previous page.
Print: This displays a printable salary slip.
You can export the view of the employees salary to a spreadsheet, by clicking on the export to
spreadsheet button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('298','View Past Employees','Here you can view all the employees who have left the company. You can view their details, when they
left and what their reasons were for leaving.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('299','HR: Add Employee Report','You can add a customers report about an employee here. These are helpful if you want to record
something about an employee, other than that directly supported by Cubit, for example, Warnings you
may have given them.
Note: You first have to add a new report type, before you can add an employee report.
First Page
Select the employee you wish to create a report about from the employees list. Select the type of report
you are creating from the report type selection. Enter the name of the person creatingsubmitting this
report. If any other people are involved in creating the report, enter their names in the 3 boxes
provided.
Enter the report details.
When you are done click the Confirm button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('300','HR: View Employee Report','There are four ways to view employee reports:
1. View a specific type of report for a specific employee
2. View a specific type of report for all employees
3. View all types of reports for a specific employee
4. View all types of reports for all employees
1. View a specific type of report for a specific employee
Select the employee and the report type. Click Show Reports button.
2. View a specific type of report for all employees
Select the ALL option from the employees drop down menu, and select the type of report you want to
view. Click the Show Reports button.
3. View all types of reports for a specific employee
Select the employee from the employee drop down menu, and select the ALL option form the report
type drop down. Click the Show Reports button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('301',' View all types of reports for all employees','Select the ALL option from the employee drop down menu, and select the ALL option from the
report type drop down. Click the Show Reports button.
A report will be displayed with the option to print the report next to it.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('302','HR: New Report Type','From here you can create a new employee report type.
Reports types are used to sort reports you made about employees, and they can have any value you
want them to have, for example: Warnings to record all the warnings you gave an employee.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('303','View Archived Loans','This gives you a list of all the previous loans on the system.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('304','View Employees On Leave','This shows you a list of all the employees currently on leave.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('305','Apply For Leave','This function is used by the employees themselves to apply for leave. When you apply for leave, the
requests can be viewed by selecting the View Leave selection on the menu.
To apply for leave select your name from the Employee drop down list. Specify the date range for
which you are applying for leave.
Select the type of leave.
When youre done click the Confirm button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('306','View Leave Applications (approvecancel)','This function allows you to view leave applications,Cancel or Approve them.
Select the date range for which leave is applied for.
To approve leave requested, click the Approve option. To cancel it, press the Cancel option.
When you choice the Approve option a new page will appear where you can fill in the non-working
days (excluding weekends) that fall within the dates the employee has applied for leave. Non-working
days being for example Public Holidays.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('307','Employee Ledger','Select the Employee you wish to view, or select all if you wish to view all the employees ledgers.
Select the period and if you wish it to be displayed by transaction date (The date of the transaction
required by GAAP) or system date (The date the transaction was entered into the system, then click the
continue button.
A list of all the employees you selected along with their ledgers will be displayed.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('308','View Fringe Benefits (delete)','Here you can view all the Fringe benefits you have created, with an option to delete next to each one. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('309','View Reimbursements (editremove)','A list of all your reimbursements are displayed with the option to edit or delete next to each one.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('310','Add Salary Account Link','Select the account out of which you are paying salaries. Quick setup has selected this for you, but you
can change it.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('311','Add Interest Received Account Link','Select the account you wish to receive interest into. Quick setup has selected this for you, but you can
change it.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('312','Add Salary Control Account Link','Select the salary control account ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('313','Add Commission Account Link','Select the account out of which you are paying commission. Quick setup has already selected this for
you, but you can change it.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('314','Add PAYE Account Link','Select the account out of which you are paying PAYE. Quick setup has already selected this for you,
but you can change it.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('315','Add UIF Account Link','Select the account out of which you are paying UIF. Quick setup has already selected this for you, but
you can change it if you wish to.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('316','Add Pension Expense Account link','Select the account out of which you are paying Pension. Quick setup has already selected this account
for you, but you can change it.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('317','Add Pension Expense Account link','Select the account out of which you are paying Pension. Quick setup has already selected this account
for you, but you can change it.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('318','Add RAF Expense Account Link','Select the account out of which you are paying the retirement annuity fund. Quick setup has done this
for you, but you can change it if you choose to.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('319','Add Medical Aid Expense Account Link','Select the account out of which you are paying medical aid. Quick setup has already done this for you,
but you can change it of you choose to. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('320','Add Pension Control Account Link','Select the pension control account. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('321','Add RAF Control Account Link','Select the retirement annuity fund control account. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('322','Add Medical Aid Control Account Link','Select the medical aid control account. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('323','Add Cash Control Account Link','Select the account out of which you pay cash. Quick setup has already done this for you, but you can
change it if you choose to.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('324','Bank Account Types','Here you can create different type of bank accounts. i.e savings, cheque, etc.
There is also a list of types of bank accounts with a option next to each one to edit or delete it.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('325','General Settings','Allows you to set general settings for employees and their salaries.
A description of all the fields follow:
Commission on Sales: Percentage of commission employees get for each sale
Interest on Employee Loans: Percentage of interest you charge for loans made by employees.
Loan Payback Period: How many months an employee is allowed to pay back loans before starting to
charge interest.
Paid Sick Leave: How many days sick leave an employee receives.
Paid Study Leave: How many days study leave an employee receives.
Paid Vacation Leave: How many days vacation leave an employee receives.
Pension Rate: Amount subtracted for pension funds. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('326','Setting Cost Centers For Salaries','The following steps are necessary to create cost centers to which salary costs of a company may be
allocated:
1.General Ledgerregular processingnew account add: in this window create a new expense account in
the general ledger as a main account. For example the number should have a 000 suffix such as
2167000.
2.General Ledgerregular processingnew account add: After creating the new account in 1 above create
a sub account where the suffix will not be 000 but for
example should be 001. A sub account of the main account created in 1 above would then be 2167001
and maybe named maintenance salaries.
(see section 8.1.14.)
3.Enable your cost center usage by going to the drop down menu: Admin-Settings-Accounts-Set Cost
Center Usage. Then go to General Ledgermaintenancecost center add: create a cost center by giving it
a name such as maintenance cost center.
4.Salariesprocessingadd new employee: create an employee and indicate in the checkbox at the
bottom of this window whether the cost of the employee
concerned should be allocated to the maintenance cost center that you have created in point #3 above
-for example 100%.
5.Salariesregular processingsalary Journal: the window that opens will display a list of employees.
Click on Exp.Accs and link the various costs
that are listed in this window to the sub account that you have created in point #2 above, namely
2167001.
6.You have now successfully created cost centers and once you process salaries the cost to company of
those employees that work in the maintenance cost
center would have been allocated automatically to an expense account called maintenance salaries. In
other words the various costs to company, such as
U I F, would not have been posted to U I F account in the general ledger but rather automatically to
maintenance salaries in account 2167001.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('327','Add Journal Transaction','This feature is used to record a new transaction made from one account to another.
Creating the Transaction
Fill in the date and reference number of the transaction.
You can select the accounts involved in one of two ways:
Selecting them from drop down menus. Typing in the account numbers manually. Choose one of the
options, and use both fields (debit and credit), you cant use the Selection menu for one, and the input
box for the other.
When you are done, click the Enter Details button next to the option you chose. (Either selecting for
the drop down, or typing in accounts manually)
The Transaction Details
In this form, fill in the reference number for the transaction, the Amount being transfered, select if
VAT is charged or not and enter details about the transaction. Click the Record Transaction button.
Check that all the details are correct and then click the write button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('328','Add Journal Transaction (One DRCR, Multiple CRDR)','This is used to CreditDebit one account, and DebitCredit multiple accounts from the other one, for
example paying from your Bank Account, both your Employee Loans and Inventory costs.
Selecting the DRCR account you wish to work from.
Enter the date of the transaction. Select the account you are working from, then select whether you are
Debiting or Crediting this account.
The contra accounts are the ones you are going to transfer the funds to. Enter the number of Contra
Accounts in the field provided.
Click the Continue button.
Entering Contra Accounts Details
You will see a row for every Contra Account you chose. Each row represents a separate transaction.
For each transaction, enter the reference number of the transaction, select the account you are
DebitingCrediting, enter the amount of funds you wish to transfer, and description (optional).
When you are done click the Record Transaction button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('329','Entering straight debitscredits','If you have a list of transactions and wish to enter all the debits first and then all the credits separately
you can follow these steps.
Note : It is important that the total balance of all the debits you are entering is equal to the total balance
of all credits. If you get this wrong it will cause you a lot of trouble.
First select the Opening BalanceSuspense account and set it to credit. Then select how many debit
transactions you wish to enter and click the continue button.
On the next screen enter all your debit transactions. These transactions will be linked to the Opening
BalanceSuspense account.
Once you have done this you have to enter all the credit transactions relating to these debit transactions.
Follow the same procedure as mentioned above, however select Opening BalanceSuspense account as
debit this time.
Important to note:
The total balance of all debits must equal the total balance of all credits. You can check this by
(Accounting-Financial-show all report options-general ledger) selecting the Opening BalanceSuspense
account. The Opening BalanceSuspense account should have a zero balance. If this is not the case, you
have probably entered an incorrect amount and will have to perform a correcting journal entry.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('330','Add Recurring Journal Transaction ','This option is used when you have transactions that get repeated on a regular basis, for example Rent.
The amount you pay for Rent generally stays the same for a number of months and therefore you can
use the Recurring transactions option for these entries. Once you have entered the transaction you do
not have to enter it again the following month, you only need to view your Recurring transactions and
process the applicable ones. If, for example, the amount for Rent changes, you can view the Recurring
Transactions and edit the amount by clicking on the Edit button for the applicable transaction and
entering the correct amount.
Enter the reoccurring journal transaction in exactly the same way you would a single, once off
transaction.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('331','View Recurring Journal Transaction ','Here you can view the recurring transactions and process the applicable ones, so that you dont have to
enter them every time. If the amount of a recurring transaction changes, then you can change the
amount by clicking on the edit button next to the transaction.
On the first page enter the date range or the journal numbers you wish to view and click the search
button. You can also view all the reoccurring transactions by clicking the view all button.
If you wish to process a journal. Select that journal by ticking the tick box next to it and then click the
process selected button.
You can also remove a journal transaction by ticking the tick box next to it and clicking the remove
selected button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('332','Add Multiple Journal Transactions','This is exactly the same as adding a single transaction, except you can add more than one transaction
on the same page.
Select how you want to select Accounts, by Account number, or name. Enter the number of journal
transactions you wish to do and then click the confirm button.
You will then be able to add multiple journal entries. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('333','Add Journal Transactions To Batch','This feature is used to add a new transaction made from one account to another to the batch file. A
batch transaction is one that is added to a list, but not yet processed. The person allocated to process the
batch transaction, can view the list and select those which heshe wants to process and those heshe
wants to delete.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('334','Add Multiple Journal Transactions To Batch','This is exactly the same as adding a single transaction, except you can add more than one transaction
on the same page. In this one you select the debit and credit account for each transaction from the Drop
down menus, and you enter the amount and description for each one in the fields next to it. Simple
enter as many transactions as you wish and click the Confirm Transaction button.
Creating the Transaction
Fill in the date and reference number of the transaction.
You can select the accounts involved in one of two ways:
Selecting them from drop down menus. Typing in the account numbers manually. You must choose
one option, and use both fields (Debit and Credit), you cant use the Selection menu from one, and the
input box for the other.
When you are done, click the Enter Details button next to the option you chose.
The Transaction Details
In this form, fill in the reference number for the transaction, the Amount being transfered, and details
about the transaction. Click the confirm button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('335','Cash Book Entry ','Making a cash book payment:
When you make a payment it is recorded here.
Select the bank account out of which you are making the payment from the drop down menu labeled
Bank Account. It is important to select the correct bank account here. If you do not select a bank
account, the entry will be posted to the default bank account, causing your bank reconciliation to not
balance at the end of the month.
Enter the date on which the transaction was made. Enter the second person involved in the Paid
toReceived From input box. Enter the description of the transaction. If you paid by cheque you can
enter the cheque number in the field labeled Cheque Number. Enter the amount you are transferring.
Select the account into which you are transferring the funds from the Select Account Involved drop
down menu.
When you are done click the Confirm button.
Making a Cash Book Receipt:
When you receive a payment you record it here.
Select the bank account into which you are transferring the payment, from the drop down menu labeled
Bank Account. Enter the date on which the transaction was made. Enter the second person involved in
the Paid toReceived From input box. Enter the description of the transaction. If you paid by cheque
you can enter the cheque number in the field labeled Cheque Number. Enter the amount you are
transferring. Select the account out of which you are transferring the funds from the Select Account
Involved drop down menu.
When you are done click the Confirm button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('336',' Add Bank Payment (Non Suppliers)','Here you can add bank payments for someone who is not a supplier. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('337','Add Receipt (Non Customers)','Here you can add bank receipts for someone who is not a Customer.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('338','Add Multiple Bank Payments (Split)','This is similar to adding a single bank payment, except here you can add multiple bank payments on
one page.
It is important to note that the bank payment is split between different payments.
For example: You pay one cheque of R500. R250 of that cheque is for stock and the other R250 is for
services. Under add multiple bank payments you will be able to add split the payment made.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('339','Add Multiple Entries','This is exactly the same as adding a single entry, except here you can add multiple entries all on the
same page. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('340','Add Multiple Bank Receipts (Split)','This is similar to adding a single bank receipt, except here you can add multiple bank receipts all on the
same page.
It is important to note that the bank receipt is split between different receipts, similar to multiple bank
payments.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('341','Add New Account ','This will allow you to create a new ledger account.
Select the type of account you wish to add (Income, Expenditure or Balance) and click the Enter
Details button.
Select the category this account falls under. Enter an account number for this account (if the option is
enabled in Account Creation).
Enter a name for the account. A good idea is to view the accounts already created to give you an idea of
what account numbers to use for your new accounts. When you are done click the Confirm button.
A Main Account is an Account with a 000 at the end. You can create a sub-account for a Main Account
by adding an Account with a 001 at the end.
Example: Main Account: Employee loan 6700000
Sub Account: Employee loan-Gail 6700001 ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('342','Add Petty Cash Requisition','To add a petty cash requisition enter the date, who it is paid to, the details and the amount. The
Account paid to, is the account that must be debited (if it is a payment). Then click confirm and write
it. Approve the requisition by going to view petty cash requisitions.
For example: Buying milk for the Staff. This will come out of petty cash and the Account paid to will
be staff expenses, you will have to set this account up beforehand. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('343','Add Multiple Petty Cash Requisitions ','This is exactly the same as adding a single Cash Requisition, except here you can add multiple
requisitions on one page. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('344','Add Cash Flow Budget Entry','Add the description of your cash flow entry. Select if the funds are going in or out. Enter the date and
the amount. Then click the confirm button. Check that all the information is correct and click the
write button. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('345','Bank Reconciliation','This will take you to the Banking page. When you are on this page click on Bank Reconciliation.
Select which bank account you wish to reconcile and if you want the output type to be normal or not,
then click the view button.
You will now be presented with a page where you can fill in the opening and closing balance. Tick the
deposits and Payments that appear on your bank statement. If they all appear, then click the select all
button.
Once you have done this, click the update button.
A printable Bank Reconciliation will then be displayed on your screen. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('346','Bank Transfer ','Here you can transfer money between the companys bank accounts. Cubit does not talk to the bank, so
this is purely an Accounting transfer and does not actually tell the banks to transfer the money.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('347','Transfer Funds tofrom Foreign Accounts','This is similar to Bank Transfer, but between the companys foreign and local Bank Accounts.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('348',' Import Bank Statement ','Here you can import a bank statement.
Select which of the companys banks statements you want to import, then select from which bank
(FNB, Nedbank, etc.)
You will have to save your bank statement as a CSV file (or ASCll for ABSA)
Then click on browse and select this CSV file.
Once you have done this click the import button.
Your bank statement will be displayed and you can select which transactions you want to import and
under which Account you want each transaction to be recorded. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('349','Transfer Funds To Petty Cash Account ','When you transferring funds to your petty cash account it is recorded here.
Select the Bank Account out of which you are transferring the funds, from the Bank Account drop
down list. Select the date you are doing this transfer. If you are not transferring the funds from the bank
account, select the ledger account you are transferring them out of. Enter a name you are using on your
reports in the Paid To field. This need only be changed when transferring for special reasons. The
description for the transaction is optional. If the funds were transfered by Cheque, enter the cheque
number. Enter the amount you are transferring.
When you are done, click the Confirm button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('350','View Petty Cash Requisitions','Select the date range for which you wish to view petty cash book requisitions.
Click the View button.
Displays a list of all the Petty Cash Book requisitions matching the criteria specified on the previous
page.
Here you can accept the requisition by clicking the accept option. Once you have accepted, you can
then record the receipt by selecting that option.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('351','Bank Petty Cash','Select this option to transferDeposit petty cash funds to the bank.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('352','Add High Speed Ledger','When you have certain paymentsreceipts that should be subtracted at every month end, a high speed
ledger is used . You create ledgers and at the end of the month you Run them, to make them execute.
First Page
Enter the descriptive name for the ledger, so you can refer back to it in the future. Select both the
account you are Debiting and Crediting with this ledger.
Enter the number of entries this ledger consists of (the number of transactions) in the number of entries
field. The date entry selection consists of two options, System and User Input. The system option will
set the date automatically at every run of the ledger. It will set the date to the System date (the date the
transactions are entered into the system). The User Input option will allow you to change it every time
your run the ledger. Select whether you are charging vat on this ledger.
There are three ways how the description of the ledger is handled. They are: An Empty Input Box:
Every time you run the ledger, you should enter a description for it Once Only Setting: You enter a
description in the field provided and this will be the description of the ledger in the future. Default
Editable: You enter a description in the field provided and whenever you run the ledger, there will be
an input box with this value in it. Then if needed, you can change the value of the input box, and that
will be the description for the applicable ledger.
The reference number for the ledger is used if you want to refer back to that ledger, or identify it in a
transaction. There are four ways you can specify a reference number. They are as follows: Auto
Number: Every time you run the ledger Cubit will generate a new reference number for you Empty
Input Box: You have to fill in a custom value every time you run the ledger. Once Only Setting: You
fill in the reference number in the field provided and this value will be fixed for every run for this
ledger in the future. Default Editable Input: You enter a reference number in the field provided and
whenever you run the ledger, there will be an input box with this value in it. If needed, you can then
change the value of the input box, and that will be the new reference number for the applicable ledger.
When you are done click the Continue button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('353','View High Speed Ledgers','When you view a ledger, you have the option to Run it, Edit it or Delete it. This feature allows you to
do just that.
First Page
There are two ways by which you can view ledgers. This is by searching for one that contains the
specified keyword, or by simply viewing all of them. To view all of them, click the View All button.
To search for a ledger, enter a keyword in the Search by Name input box, and click the Search button.
The search is done on the name field, so all ledgers which names contain the specified keyword will be
returned as a result.
Main Page
The results of your query are displayed on this page.
To Run a ledger, click on the ledgers name or the Run option.
To View the ledger in details, click on the View Details options next to the ledger entry.
To Edit a ledger, click on the Edit option. This will display a form like the one you used to create a
new ledger. Make your changes and click the Continue button.
To Delete a ledger, click the Delete option. You will be asked to confirm the deletion. Please make
sure you are deleting the the correct entry, as there is no way to restore it after you have deleted it.
Running a Ledger
When you have clicked the Run option next to the ledger name you will be presented with a form you
need to fill in to complete the Run of the ledger. Fill in the fields of the form, and click the Continue
button. Note that you need to complete at least one entry.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('354','Add Asset','Asset Ledgers are for recording and listing your Assets (accounting purposes).
To add an asset fill in the Asset group, Location, Description, the method (if it was purchased, or if you
are just adding it), the date and Amount fields.
The amount field is the value you purchased the asset for, excluding vat. The serial number field is
optional.
Click the Confirm button.
Note: Before you can add an Asset you will have to add an Asset Group.
Example: Asset Group: Computers
Assets: Computer 001
Laptop.
If you are purchasing an asset, then it will take you to a purchase screen when you click the confirm
button.
On the purchase page you can add in a supplier, select the VAT type for the asset you are purchasing. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('355','View Asset (Edit, Report, Depreciate, Appreciate, Remove)','A list of all the Assets are displayed with total values at the bottom. Next to each Asset there is the
option to edit, depreciate, appreciate, Report or Remove.
Edit: Edit the Asset
Depreciate: Enter the amount the asset must be depreciated by. The Net value of the asset will then be
decreased by this amount.
Appreciate:Enter the amount the asset must appreciate by. The Net value of the asset will then be
increased by this amount.
Report: Here you are given a report of the Net Value resulting from depreciation or appreciation.
Remove: The asset will be removed if you click this button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('356','View Accounts ','This allows you to view accounts created by the Select Default Accounts or New Account options.
Select the type of Account you want to view (Income, Expenditure or Balance). Click the View
Accounts button.
Select the category of which you want to view accounts. Click the View Accounts button.
A display of all the accounts matching the specified criteria will be displayed.
Next to each account you will see four options, Edit, View Transactions, Change category, Delete (An
Account with transactions cannot be deleted)
Edit: Edit the details of the account.
View Transactions: Will show you all the transactions made under this account.
Change Category: This Allows you to change the Account category.
Delete: Delete the account(this option is only available on accounts which have not had any entries or
transactions recorded in them).');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('357','Add Asset','Asset Ledgers are for recording and listing your Assets (accounting purposes).
To add an asset fill in the Asset group, Location, Description, the method (if it was purchased, or if you
are just adding it), the date and Amount fields.
The amount field is the value you purchased the asset for, excluding vat. The serial number field is
optional.
Click the Confirm button.
Note: Before you can add an Asset you will have to add an Asset Group.
Example: Asset Group: Computers
Assets: Computer 001
Laptop.
If you are purchasing an asset, then it will take you to a purchase screen when you click the confirm
button.
On the purchase page you can add in a supplier, select the VAT type for the asset you are purchasing. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('358','Show All Reports Option ','This will display all the report options that are available.
Most of these Reports have been discussed under another section, so we will not discuss them again
here.
However there are a few that only appear here and these will now be discussed:
Banking
Bank Reconciliation (Discussed in another section)
List Outstanding Bank PaymentsReceipts:
Select the bank account you wish to view and click the view button.
A lost of all the outstanding Bank Payments and Receipts will be displayed, with an option next to each
one of ReturnedUnpaid.
Cash Book Analysis Of PaymentsReceipts
Select the bank account and the date range you wish to view and then click the view button.
This will give you a list of all the payments and receipts made in this period.
View Saved Bank Reconciliation
Here you will be able to view Previous Bank Reconciliations that you have preformed.
Accounts
All Categories (This has been discussed in another section)
Custom Financial Statements
Generate Trial Balance
Here you can Generate a Trial Balance
Set Trial Balance
Here you will be able to set your trial balance so that it will appear how you wish it to. You do this by
adding groups
Generate Income Statement
Here you will be able to generate an Income Statement
Set Income Statement
Here you will be able to set your income statement so that it will appear how you wish it to.
Generate Balance Sheet
Here you can generate a Balance Sheet
Set Balance Sheet
Here you will be able to set your balance sheet so that it will appear how you wish it to.
Other
Inventory Ledger
Select the stock items you wish to view, or select all if you wish to view all the items. Select the
period from the drop down menu and click the continue button.
The Stock Item you selected will appear will a list of all the transactions entered into with it.
Employee Ledger
Select the Employee you wish to view, or select all if you wish to view all the employees ledgers.
Select the period and if you wish it to be displayed by transaction date (the date of the transaction
required by GAAP) or system date (the date the transaction was entered into the system), then click the
continue button.
A list of all the employees you selected along with their ledgers will be displayed.
View VAT Report
Select the VAT type (Input, Output or ALL), select the VAT code (01,02...)and the date range, then
click the view report button. Your VAT report will then be displayed.
View Current Period
This will show you the period you are currently working in.
POS Cash Report
Select the User, date and type. If there is a starting amount (like a float) fill this in and then click the
view report button. A report of your POS sales will appear.
POS Sales Report
Select the date rage and click if you want a summarized report or a report of all the POS sales. A report
of the POS Sales will then be displayed on the screen.
Financial Statements
Generate Trial Balance
Here you can view your Trial Balance
View Saved Trial Balance
If you have saved previous Trial balances, this is where you can view them.
Generate Income Statement
Here you can view your Income Statement
View Saved Income Statements
If you have saved previous Income Statements, this is where you can view them.
Generate Balance Sheet
Here you can view your Balance Sheet
View Saved Balance Sheet
If you have saved previous Balance Sheets, this is where you will be able to view them.
Generate Statement Of Cash Flow
Here you will be able to view your Cash Flow Statement
Debtors and Creditors (This has been discussed in another section)
Journals
General Ledger
Select the Account you wish to view, or select all if you wish to view the General ledger of all the
account. Select the period and if you want the order to be by transaction date (the date of the
transaction required by GAAP) or system date (the date the transaction was entered into the system) .
Then click the continue button.
The General ledger for the Accounts you have selected will be displayed on the screen.
Period Range General Ledger
The selection process works exactly the same as General ledger above, except here you will be able to
select a period range in which to view the general ledger. For example from March to May.
Detailed General Ledger
To view your detailed General Ledger, you can either select the date range, journal number range or
period in which you wish to view the ledger. Then click the search or view all button. A detailed
General Ledger Report will then be displayed.
Year Review General Ledger
Select the Accounts you wish to view or select all if you wish to view all the Accounts. Then click the
continue button. The year review for the ledger account you selected will then be displayed.
All Journal Entries
Select the date range and period and the journal account you wish to view, or select the journal number
range. A journal entry report will then be displayed for this date range. If you wish to view all journals,
then select all accounts.
For example: If you select a date range from 15 March to 30 March in the period March and select
Bank as the account. Then a list of all the journal entries with Bank either as the debit or credit entry,
in the selected date range, will be displayed
All Journal Entries By Ref. Number
The selection process works exactly the same as Journal Entries above. You will then be able to view
the Journal Entries by Reference number.
All Journal Entries (Period Range)
This is exactly the same as All Journal Entries above, except here you can select the period range in
which to view your journal entries. For example from March to May.
Journal Entries Per Account
Here you can select the Account name or the Account number and then the period range, then click the
view transactions button. The Account you have selected with all the Transactions that have been done
in it will be displayed.
Journal Entries Per Account (Period Range)
The selection process is the same as above, except here you are able to select a period range in which to
view the Account Journal Entries. For example from March to May.
Journal Entries Per Main Account
You can either select the Main Account name or the Main Account Number that you wish to view.
Then select the period and click the enter details or view button. The Main Account will then be
displayed, along with any Sub Accounts, if Sub Accounts have been set up for it. The transaction in
these accounts will also be displayed.
Journal Entries Per Category
Select the Category (Income, Expense or Balance) that you wish to view, then click the continue
button. On the next screen select the period you wish to view and click the view transactions button.
The details of all the transactions under the category you have selected will be displayed. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('359','Show All AccountsCategories','This displays a list of all the Accounts and Categories that the Cubit Quick setup has setup for you.
A list with all the Account Categories and all the Accounts under each category will be displayed. The
Account names and numbers will be displayed. This is useful when setting up Sub accounts or adding
new accounts. You can also open this list in a new window.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('360','Branch Reports','You can view the branch reports by clicking on the branch report button. The select process of these
reports work exactly the same as the head office reports.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('361','View Batch (Journal)','To process batch transactions, only the persons allocated to do this process may do so by first Viewing
them, and then deciding what should happen to them. When you process a transaction it will be
converted into a normal transaction and removed from the Batch file.
First Page
On this page you can select which batch transactions you would like to view. You do this in three
ways. By Date Range: Specify the date range in which your transaction should fall. Journal Number:
Specify the range in which the journal number should fall.
View All: Clicking this button, you will view all unprocessed batch transactions.
Batch Transactions
A list of all the Batch Transactions that satisfy the criteria you specified on the previous page will be
displayed. Next to each transaction you will see a checkbox, and two options, Edit and Remove. The
Remove option will remove the transaction from the list.
The Edit option will allow you to edit a transaction. To process transactions, select all those
transactions you wish to process, by ticking the checkboxes next to each transaction, and clicking the
Process Selected button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('362',' View Batch (Cash Book)','Select the Bank Account and the date range of Batch Entries you wish to view and click the view
button.
Batch Transactions
A list of all the Batch Transactions that satisfy the criteria you specified on the previous page will be
displayed. Next to each transaction you will see a checkbox, and two options, Edit and Remove. The
Remove option will remove the transaction from the list.
The Edit option will allow you to edit a transaction. To process transactions, select all those
transactions you wish to process, by ticking the checkboxes next to each transaction, and clicking the
Process Selected button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('363','View Cash Book','First Page
Select the bank account and date range for which you would like to see your cash book entries. Click
the View button.
Cash Book
Your entries will be displayed in two tables, Receipts at the top, and Payments below it. At the bottom
of each table there is a total for that table. Next to each entry in the table, you can click the
ReturnUnpaid option to return a payment or for a bounced cheque to reverse the transaction in the
cash book. .');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('364','Petty Cash Book Report','Select the date range for which you wish to view petty cash book entries.
Click the View button.
You are presented with a listing of all the Petty Cash Book entries matching the criteria specified on the
previous page.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('365','View Budgets','Here you will be able to view all the budgets you have created. Next to each budget there will be five
options: Details, Edit, Remove, Report (A print out of your budgets), Export.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('366','View Cash Flow Budget Entries','Here you can view all the cash flow budget entries. Next to each cash flow budget entry there is an
option to edit or remove.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('367','Cash Flow Budget Report','This gives you a report of your Cash Flow Budget.
');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('368','Cost Center Report','Select the month for which you wish to view the cost center report.
Next to each Cost Center transaction there will be an option to see the detailed transaction. If you click
the details option a new window will open with the details of the transaction. You can then close this
window and continue from where you were.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('369','Generate Previous Year Trial Balance','This will create a trial balance of all the previous year transactions for each of your ledger accounts.
Next to each previously saved Trail Balance will be an option to print or move to spreadsheet. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('370','Generate Previous Year Income Statement ','Displays a list of all the Incomes and Expenditures for the previous year.
Next to each previously saved Income Statements is the option to print or move to spreadsheet. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('371','Generate Previous Year Balance Sheet','This generates a balance sheet for the previous years transactions.
Nest to each previously saved Balance Sheets is an option to print or move to spreadsheet. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('372','View Previous Year General Ledger','This allows you to view previous years General Ledgers.
Select the year you wish to view and click the next button. A list of all the General Ledger accounts
will be displayed on the screen.
At the top of the screen you can select all accounts, or manually selected only the accounts you wish to
view.
Select the month you wish to view these accounts and click continue button.
The Account will then be displayed. You can export this to a spreadsheet if you so wish. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('373','View Previous Year General Ledger By Period Range','This is exactly the same as View Previous Year General Ledger, except here you are given the option
to select a month from and a moth to, that you wish to view the general ledger.
For example: From January to March.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('374','Add Credit Card','Here you can add the details for a Credit Card for the company. Once you have entered all the details
click the confirm button. Then check that all the information is correct and click the write button.
Add Petrol Card
Here you can add the details for a Petrol Card for the company. Once you have entered all the details ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('375',' Add Bank Account','Add one of your bank accounts to the database.
First Page
Enter the details of the bank account, and click the Confirm button.
Thereafter a description of all the fields is displayed:
Type of Account: ex. savings, check, credit card, etc.
Bank Name: The name of the bank at which this account is held.
Branch Name: Name of the branch at which you created this account.
Branch Code: The code of the above branch. This is supplied by the bank.
Account Name: Name of the account.
Account Number: The accounts account number.
Account Category: The category of the business you are using this account for.
Details: A short description and comments on this bank account.
Ledger Selection
When you have clicked the confirm button, you are given the option to select what this account is used
for, in other words a Ledger account. Select this from the drop down menu and click the Write button.');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('376','ViewEdit Bank Account','A list of all the bank accounts are displayed with two options next to each one, Edit and Delete.
Edit to change the details of the bank account. Delete to remove the bank account from the system. ');
INSERT INTO supp_db_questions ("id","heading","content") VALUES('377','New Monthly Budgets','Here you can create a monthly budget.
Enter a name for your monthly budget. Select between budgeting for cost centers or Accounts. Select
your budget type from the drop down menu. Select your budget period.
Once all this information has been entered click the continue button.
If you selected to budget for a cost center, then on the screen that follows select the cost centers you
wish to add to your monthly budget and add the amount you wish to budget for each month in your
budget. Then click the continue button. Check that all the information is correct and click the
continue button.
If you selected to budget for Accounts, then on the next screen that follows select the Accounts you
wish to add to your budget and enter the amounts you wish to allocate to the each Account in your
budget for each month. Then click the continue button. Check that all the information is correct and
click the continue button. ');
CREATE TABLE purch_int ("purid" serial NOT NULL PRIMARY KEY ,"deptid" numeric DEFAULT 0,"supid" numeric DEFAULT 0,"supaddr" varchar ,"terms" numeric DEFAULT 0,"pdate" date ,"ddate" date ,"remarks" varchar ,"received" varchar ,"done" varchar ,"refno" varchar ,"curr" varchar ,"recved" varchar ,"prd" numeric DEFAULT 0,"div" numeric DEFAULT 0,"purnum" varchar ,"shipchrg" numeric(16, 2) DEFAULT 0,"subtot" numeric(16, 2) DEFAULT 0,"total" numeric(16, 2) DEFAULT 0,"balance" numeric(16, 2) DEFAULT 0,"tax" numeric(16, 2) DEFAULT 0,"xrate" numeric(16, 2) DEFAULT 0,"duty" numeric(16, 2) DEFAULT 0,"rvat" numeric(16, 2) DEFAULT 0,"rshipchrg" numeric(16, 2) DEFAULT 0,"rsubtot" numeric(16, 2) DEFAULT 0,"rtotal" numeric(16, 2) DEFAULT 0,"toggle" varchar ,"jobid" numeric DEFAULT 0,"jobnum" varchar ,"fcid" numeric DEFAULT 0,"currency" varchar ,"fbalance" numeric(16, 2) DEFAULT 0,"fsubtot" numeric(16, 2) DEFAULT 0,"fshipchrg" numeric(16, 2) DEFAULT 0,"rtax" numeric(16, 2) DEFAULT 0,"rfshipchrg" numeric(16, 2) DEFAULT 0,"invcd" varchar ,"rlsubtot" numeric(16, 2) DEFAULT 0) WITH OIDS;
SELECT setval('purch_int_purid_seq',1);
CREATE TABLE diary_categories ("category_id" serial NOT NULL PRIMARY KEY ,"category_name" varchar ,"category_img" varchar ) WITH OIDS;
SELECT setval('diary_categories_category_id_seq',1);
CREATE TABLE today ("id" serial NOT NULL PRIMARY KEY ,"date" date ,"section_id" numeric DEFAULT 0,"info" varchar ,"link" varchar ,"title" varchar ,"user_id" numeric DEFAULT 0,"link_id" numeric DEFAULT 0,"team_id" numeric DEFAULT 0) WITH OIDS;
SELECT setval('today_id_seq',1);
CREATE TABLE mail_datatypes ("type_id" serial NOT NULL PRIMARY KEY ,"name" varchar ,"icon" varchar ) WITH OIDS;
SELECT setval('mail_datatypes_type_id_seq',1);
CREATE TABLE statement_history ("id" serial NOT NULL PRIMARY KEY ,"date" date ,"amount" numeric(16, 2) DEFAULT 0,"description" varchar ,"contra" varchar ,"code" varchar ,"ex1" varchar ,"ex2" varchar ,"ex3" varchar ,"by" varchar ,"bank" varchar ,"account" int4 DEFAULT 0) WITH OIDS;
SELECT setval('statement_history_id_seq',1);
CREATE TABLE lead_times ("id" serial NOT NULL PRIMARY KEY ,"supid" numeric DEFAULT 0,"stkid" numeric DEFAULT 0,"lead_time" numeric DEFAULT 30,"purid" numeric DEFAULT 0) WITH OIDS;
SELECT setval('lead_times_id_seq',1);
CREATE TABLE stock_purch ("id" serial NOT NULL PRIMARY KEY ,"stkid" numeric DEFAULT 0,"date" date ,"units" numeric DEFAULT 0,"cost" numeric DEFAULT 0,"div" numeric DEFAULT 0) WITH OIDS;
SELECT setval('stock_purch_id_seq',1);
CREATE TABLE todo_main ("id" serial NOT NULL PRIMARY KEY ,"title" varchar ,"user_id" numeric DEFAULT 0,"team_id" numeric DEFAULT 0) WITH OIDS;
SELECT setval('todo_main_id_seq',1);
CREATE TABLE credit_notes_stock ("id" serial NOT NULL PRIMARY KEY ,"creditnote_id" numeric DEFAULT 0,"stkid" numeric DEFAULT 0,"stkunits" numeric DEFAULT 0,"stkcosts" numeric DEFAULT 0) WITH OIDS;
SELECT setval('credit_notes_stock_id_seq',1);
CREATE TABLE cust_dates ("id" serial NOT NULL PRIMARY KEY ,"user_id" numeric DEFAULT 0,"cust_id" numeric DEFAULT 0,"date" date ,"notes" varchar ) WITH OIDS;
SELECT setval('cust_dates_id_seq',1);
CREATE TABLE serial8 ("stkid" numeric DEFAULT 0,"serno" varchar ,"rsvd" varchar ,"warranty" date ) WITH OIDS;
CREATE TABLE emp_times ("id" serial NOT NULL PRIMARY KEY ,"user_id" numeric DEFAULT 0,"in_time" timestamp ,"out_time" timestamp ) WITH OIDS;
SELECT setval('emp_times_id_seq',1);
CREATE TABLE sorders_items ("sordid" numeric DEFAULT 0,"whid" numeric DEFAULT 0,"stkid" numeric DEFAULT 0,"qty" numeric(16, 3) DEFAULT 0,"div" numeric DEFAULT 0,"amt" numeric(16, 2) DEFAULT 0,"discp" numeric(16, 2) DEFAULT 0,"disc" numeric(16, 2) DEFAULT 0,"unitcost" numeric(16, 2) DEFAULT 0,"hidden" varchar ,"funitcost" numeric(16, 2) DEFAULT 0,"famt" numeric(16, 2) DEFAULT 0,"pinv" numeric(16, 2) DEFAULT 0,"vatcode" int4 DEFAULT 0,"description" varchar ,"account" int4 DEFAULT 0,"jobcard_id" numeric DEFAULT 0,"iqty" numeric(16, 3) DEFAULT 0,"id" serial NOT NULL PRIMARY KEY ) WITH OIDS;
SELECT setval('sorders_items_id_seq',1);
CREATE TABLE document_departments ("id" serial NOT NULL PRIMARY KEY ,"dep_name" varchar ) WITH OIDS;
SELECT setval('document_departments_id_seq',1);
CREATE TABLE scons_img ("id" serial NOT NULL PRIMARY KEY ,"scon_id" numeric DEFAULT 0,"type" varchar ,"file" varchar ,"size" numeric DEFAULT 0) WITH OIDS;
SELECT setval('scons_img_id_seq',1);
CREATE TABLE sorders ("sordid" serial NOT NULL PRIMARY KEY ,"deptid" numeric DEFAULT 0,"cusnum" numeric DEFAULT 0,"cordno" varchar ,"ordno" varchar ,"chrgvat" varchar ,"terms" numeric DEFAULT 0,"salespn" varchar ,"odate" date ,"accepted" varchar ,"comm" varchar ,"done" varchar ,"username" varchar ,"deptname" varchar ,"cusacc" varchar ,"cusname" varchar ,"surname" varchar ,"cusaddr" varchar ,"cusordno" varchar ,"cusvatno" varchar ,"prd" numeric DEFAULT 0,"div" numeric DEFAULT 0,"disc" numeric(16, 2) DEFAULT 0,"discp" numeric(16, 2) DEFAULT 0,"delchrg" numeric(16, 2) DEFAULT 0,"subtot" numeric(16, 2) DEFAULT 0,"traddisc" numeric(16, 2) DEFAULT 0,"balance" numeric(16, 2) DEFAULT 0,"vat" numeric(16, 2) DEFAULT 0,"total" numeric(16, 2) DEFAULT 0,"jobid" numeric DEFAULT 0,"jobnum" varchar ,"dir" varchar ,"location" varchar ,"fcid" numeric DEFAULT 0,"currency" varchar ,"xrate" numeric(16, 2) DEFAULT 0,"fbalance" numeric(16, 2) DEFAULT 0,"fsubtot" numeric(16, 2) DEFAULT 0,"discount" numeric(16, 2) DEFAULT 0,"delivery" numeric(16, 2) DEFAULT 0,"delvat" int4 DEFAULT 0,"display_costs" varchar ,"proforma" varchar ,"pinvnum" varchar ,"ddate" date ,"slip_done" varchar DEFAULT 'n'::character varying,"del_addr" varchar DEFAULT ''::character varying) WITH OIDS;
SELECT setval('sorders_sordid_seq',1);
CREATE TABLE sub_sub_projects ("id" serial NOT NULL PRIMARY KEY ,"sub_sub_project_name" varchar ,"sub_project_id" numeric DEFAULT 0,"project_id" numeric DEFAULT 0) WITH OIDS;
SELECT setval('sub_sub_projects_id_seq',3);
INSERT INTO sub_sub_projects ("id","sub_sub_project_name","sub_project_id","project_id") VALUES('1','No Sub Sub Project','1','1');
INSERT INTO sub_sub_projects ("id","sub_sub_project_name","sub_project_id","project_id") VALUES('3','Sub Sub Project','3','3');
CREATE TABLE recon_creditor_balances ("id" serial NOT NULL PRIMARY KEY ,"supid" numeric DEFAULT 0,"balance" numeric(16, 2) DEFAULT 0) WITH OIDS;
SELECT setval('recon_creditor_balances_id_seq',1);
CREATE TABLE serialrec ("recid" serial NOT NULL PRIMARY KEY ,"serno" varchar ,"stkid" numeric DEFAULT 0,"edate" date ,"cusname" varchar ,"invnum" numeric DEFAULT 0,"typ" varchar ,"div" numeric DEFAULT 0,"tdate" date ) WITH OIDS;
SELECT setval('serialrec_recid_seq',1);
CREATE TABLE sorders_nitems ("id" serial NOT NULL PRIMARY KEY ,"sordid" numeric DEFAULT 0,"cod" varchar ,"des" varchar ,"qty" numeric DEFAULT 0,"unitcost" numeric(16, 2) DEFAULT 0,"amt" numeric(16, 2) DEFAULT 0,"csprice" numeric(16, 2) DEFAULT 0,"div" numeric DEFAULT 0,"funitcost" numeric(16, 2) DEFAULT 0,"famt" numeric(16, 2) DEFAULT 0,"pinv" numeric(16, 2) DEFAULT 0) WITH OIDS;
SELECT setval('sorders_nitems_id_seq',1);
CREATE TABLE empfringe ("id" serial NOT NULL PRIMARY KEY ,"fringeid" numeric DEFAULT 0,"empnum" numeric DEFAULT 0,"amount" numeric DEFAULT 0,"type" varchar ,"accid" numeric DEFAULT 0,"div" numeric DEFAULT 0) WITH OIDS;
SELECT setval('empfringe_id_seq',1);
CREATE TABLE pos_quotes ("quoid" serial NOT NULL PRIMARY KEY ,"deptid" numeric DEFAULT 0,"ordno" varchar ,"chrgvat" varchar ,"terms" numeric DEFAULT 0,"salespn" varchar ,"odate" date ,"accepted" varchar ,"comm" varchar ,"done" varchar ,"username" varchar ,"deptname" varchar ,"cusacc" varchar ,"cusname" varchar ,"surname" varchar ,"cusaddr" varchar ,"prd" numeric DEFAULT 0,"div" numeric DEFAULT 0,"disc" numeric(16, 2) DEFAULT 0,"discp" numeric(16, 2) DEFAULT 0,"delchrg" numeric(16, 2) DEFAULT 0,"subtot" numeric(16, 2) DEFAULT 0,"traddisc" numeric(16, 2) DEFAULT 0,"balance" numeric(16, 2) DEFAULT 0,"vat" numeric(16, 2) DEFAULT 0,"total" numeric(16, 2) DEFAULT 0,"discount" numeric(16, 2) DEFAULT 0,"delivery" numeric(16, 2) DEFAULT 0,"delvat" int4 DEFAULT 0,"fcid" numeric DEFAULT 0,"telno" varchar ,"lead" varchar ,"ncdate" date ) WITH OIDS;
SELECT setval('pos_quotes_quoid_seq',1);
CREATE TABLE unique_id ("id" serial NOT NULL PRIMARY KEY ,"entry" varchar ) WITH OIDS;
SELECT setval('unique_id_id_seq',1);
CREATE TABLE coms_invoices ("id" serial NOT NULL PRIMARY KEY ,"rep" varchar ,"invdate" date ,"inv" int4 DEFAULT 0,"amount" numeric(16, 2) DEFAULT 0,"com" numeric(16, 2) DEFAULT 0) WITH OIDS;
SELECT setval('coms_invoices_id_seq',1);
CREATE TABLE salr ("id" serial NOT NULL PRIMARY KEY ,"empnum" numeric DEFAULT 0,"month" varchar ,"bankid" numeric DEFAULT 0,"salary" numeric DEFAULT 0,"comm" numeric DEFAULT 0,"uifperc" numeric DEFAULT 0,"uif" numeric DEFAULT 0,"payeperc" numeric DEFAULT 0,"paye" numeric DEFAULT 0,"totded" numeric DEFAULT 0,"totben" numeric DEFAULT 0,"totallow" numeric DEFAULT 0,"loanins" numeric DEFAULT 0,"div" numeric DEFAULT 0,"showex" varchar ,"display" varchar ,"saldate" date ,"week" int4 DEFAULT 0,"totded_employer" numeric DEFAULT 0,"cyear" varchar ,"true_ids" int4 ,"hovert" numeric DEFAULT 0,"novert" numeric DEFAULT 0,"taxed_sal" numeric DEFAULT 0,"tot_fringe" numeric DEFAULT 0,"hours" numeric DEFAULT 0,"salrate" numeric DEFAULT 0,"bonus" numeric DEFAULT 0) WITH OIDS;
SELECT setval('salr_id_seq',1);
CREATE TABLE template_settings ("id" serial NOT NULL PRIMARY KEY ,"template" varchar ,"filename" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
SELECT setval('template_settings_id_seq',1);
CREATE TABLE purchases ("purid" serial NOT NULL PRIMARY KEY ,"deptid" numeric DEFAULT 0,"supid" numeric DEFAULT 0,"supaddr" varchar ,"terms" numeric DEFAULT 0,"pdate" date ,"ddate" date ,"remarks" varchar ,"received" varchar ,"done" varchar ,"refno" varchar ,"vatinc" varchar ,"prd" numeric DEFAULT 0,"ordernum" varchar ,"part" varchar ,"div" numeric DEFAULT 0,"purnum" varchar ,"edit" numeric DEFAULT 0,"supname" varchar ,"supno" varchar ,"shipchrg" numeric(16, 2) DEFAULT 0,"subtot" numeric(16, 2) DEFAULT 0,"total" numeric(16, 2) DEFAULT 0,"balance" numeric(16, 2) DEFAULT 0,"vat" numeric(16, 2) DEFAULT 0,"supinv" varchar ,"apprv" varchar ,"appname" varchar ,"appdate" date ,"rvat" numeric(16, 2) DEFAULT 0,"rshipchrg" numeric(16, 2) DEFAULT 0,"rsubtot" numeric(16, 2) DEFAULT 0,"rtotal" numeric(16, 2) DEFAULT 0,"jobid" numeric DEFAULT 0,"jobnum" varchar ,"toggle" varchar ,"cash" varchar ,"shipping" numeric(16, 2) DEFAULT 0,"invcd" varchar ,"rshipping" numeric(16, 2) DEFAULT 0,"noted" varchar ,"returned" varchar ,"iamount" numeric(16, 2) DEFAULT 0,"ivat" numeric(16, 2) DEFAULT 0,"delvat" int4 DEFAULT 0,"trh_status" varchar ) WITH OIDS;
SELECT setval('purchasesids_seq',1);
CREATE TABLE recinv_items ("id" serial NOT NULL PRIMARY KEY ,"invid" numeric DEFAULT 0,"whid" numeric DEFAULT 0,"stkid" numeric DEFAULT 0,"qty" numeric DEFAULT 0,"ss" varchar ,"div" numeric DEFAULT 0,"noted" numeric DEFAULT 0,"amt" numeric(16, 2) DEFAULT 0,"discp" numeric(16, 2) DEFAULT 0,"disc" numeric(16, 2) DEFAULT 0,"unitcost" numeric(16, 2) DEFAULT 0,"serno" varchar ,"vatcode" int4 DEFAULT 0,"description" varchar ,"account" int4 DEFAULT 0) WITH OIDS;
SELECT setval('recinv_items_id_seq',1);
CREATE TABLE teams ("id" serial NOT NULL PRIMARY KEY ,"team_name" varchar ,"team_description" varchar ,"team_email" varchar ) WITH OIDS;
SELECT setval('teams_id_seq',1);
CREATE TABLE pos_trans_electricity ("id" serial NOT NULL PRIMARY KEY ,"trans_id" numeric DEFAULT 0,"hire_trans_id" numeric DEFAULT 0,"meter" varchar ,"amount" numeric(16, 2) DEFAULT 0,"cost_price" numeric(16, 2) DEFAULT 0,"serial" varchar ) WITH OIDS;
SELECT setval('pos_trans_electricity_id_seq',1);
CREATE TABLE assets_prev ("id" serial NOT NULL PRIMARY KEY ,"asset_id" numeric DEFAULT 0,"serial" varchar ,"locat" varchar ,"des" varchar ,"date" date ,"bdate" date ,"amount" numeric DEFAULT 0,"div" numeric DEFAULT 0,"grpid" numeric DEFAULT 0,"accdep" numeric DEFAULT 0,"dep_perc" numeric DEFAULT 0,"dep_month" varchar ,"team_id" numeric DEFAULT 0,"puramt" numeric(16, 2) DEFAULT 0,"conacc" numeric DEFAULT 0,"remaction" varchar ,"saledate" date ,"saleamt" numeric(16, 2) DEFAULT 0,"invid" numeric DEFAULT 0,"autodepr_date" date ,"sdate" date ,"temp_asset" varchar DEFAULT 'n'::character varying,"nonserial" varchar ,"type_id" numeric DEFAULT 0,"split_from" numeric DEFAULT 1,"serial2" varchar ,"profit" numeric(16, 2) DEFAULT 0) WITH OIDS;
SELECT setval('assets_prev_id_seq',1);
CREATE TABLE ss3 ("stock" int4 DEFAULT 0,"code" varchar ,"div" numeric DEFAULT 0,"active" varchar DEFAULT 'yes'::character varying) WITH OIDS;
CREATE TABLE supp_payment_files ("id" serial NOT NULL PRIMARY KEY ,"payment" numeric DEFAULT 0,"sdate" date ,"first_val" varchar DEFAULT ''::character varying,"branch_val" varchar DEFAULT ''::character varying,"empno_val" varchar DEFAULT ''::character varying,"bankacc_val" varchar DEFAULT ''::character varying,"second_val" varchar DEFAULT ''::character varying,"paidamt_val" varchar DEFAULT ''::character varying,"name_val" varchar DEFAULT ''::character varying,"third_val" varchar DEFAULT ''::character varying) WITH OIDS;
SELECT setval('supp_payment_files_id_seq',1);
CREATE TABLE sd ("name" varchar ,"date" date ,"amount" numeric DEFAULT 0,"div" numeric DEFAULT 0) WITH OIDS;
CREATE TABLE posrec ("id" serial NOT NULL PRIMARY KEY ,"userid" int4 DEFAULT 0,"username" varchar ,"amount" numeric(16, 2) DEFAULT 0,"pdate" date ,"inv" int4 DEFAULT 0) WITH OIDS;
SELECT setval('posrec_id_seq',1);
CREATE TABLE emp_groups ("id" serial NOT NULL PRIMARY KEY ,"emp_group" varchar DEFAULT ''::character varying) WITH OIDS;
SELECT setval('emp_groups_id_seq',4);
INSERT INTO emp_groups ("id","emp_group") VALUES('1','Management');
INSERT INTO emp_groups ("id","emp_group") VALUES('2','Monthly');
INSERT INTO emp_groups ("id","emp_group") VALUES('3','Weekly');
INSERT INTO emp_groups ("id","emp_group") VALUES('4','Default');
CREATE TABLE pur_canc_items ("id" serial NOT NULL PRIMARY KEY ,"purid" numeric DEFAULT 0,"whid" numeric DEFAULT 0,"stkid" numeric DEFAULT 0,"qty" numeric DEFAULT 0,"ddate" date ,"div" numeric DEFAULT 0,"qpack" numeric DEFAULT 0,"upack" numeric DEFAULT 0,"ppack" numeric DEFAULT 0,"svat" numeric DEFAULT 0,"rqty" numeric DEFAULT 0,"tqty" numeric DEFAULT 0,"unitcost" numeric(16, 2) DEFAULT 0,"amt" numeric(16, 2) DEFAULT 0,"iqty" numeric(16, 2) DEFAULT 0,"vatcode" int4 DEFAULT 0,"description" varchar ,"account" int4 DEFAULT 0) WITH OIDS;
SELECT setval('pur_canc_items_id_seq',1);
CREATE TABLE callout_docs_scanned ("calloutid" numeric DEFAULT 0,"image" text ,"image_type" text ,"div" varchar ) WITH OIDS;
CREATE TABLE custran ("id" serial NOT NULL PRIMARY KEY ,"cusnum" numeric DEFAULT 0,"odate" date ,"div" numeric DEFAULT 0,"age" numeric DEFAULT 0,"balance" numeric(16, 2) DEFAULT 0,"fcid" numeric DEFAULT 0,"fbalance" numeric(16, 2) DEFAULT 0,"actual_date" varchar ,"invid" numeric DEFAULT 0,"invtype" varchar ) WITH OIDS;
SELECT setval('custran_id_seq',1);
CREATE TABLE diary_entries ("entry_id" serial NOT NULL PRIMARY KEY ,"username" varchar ,"time_start" timestamp ,"time_end" timestamp ,"time_entireday" varchar ,"title" varchar ,"location" varchar ,"homepage" varchar ,"description" text ,"type" varchar ,"repetitions" varchar ,"rep_date" date ,"rep_forever" varchar ,"category_id" int4 DEFAULT 0,"notify" int4 DEFAULT 0,"lead_id" numeric DEFAULT 0,"loc_id" numeric DEFAULT 0,"team_id" numeric DEFAULT 0) WITH OIDS;
SELECT setval('diary_entries_entry_id_seq',1);
CREATE TABLE mail_priv_folders ("fp_id" serial NOT NULL PRIMARY KEY ,"folder_id" int4 DEFAULT 0,"priv_owner" varchar ) WITH OIDS;
SELECT setval('mail_priv_folders_fp_id_seq',1);
CREATE TABLE purch_batch_entries_newcostcenters ("id" serial NOT NULL PRIMARY KEY ,"project" varchar ,"costcenter" varchar ,"costperc" numeric(16, 2) DEFAULT 0) WITH OIDS;
SELECT setval('purch_batch_entries_newcostcenters_id_seq',1);
CREATE TABLE transit ("id" serial NOT NULL PRIMARY KEY ,"trandate" date ,"stkid" numeric DEFAULT 0,"sdiv" numeric DEFAULT 0,"swhid" numeric DEFAULT 0,"tunits" numeric DEFAULT 0,"notes" varchar ,"cstamt" numeric DEFAULT 0,"div" numeric DEFAULT 0) WITH OIDS;
SELECT setval('transit_id_seq',1);
CREATE TABLE product ("id" serial NOT NULL PRIMARY KEY ,"prod_code" varchar ,"name" varchar ,"price" numeric(16, 2) DEFAULT 0,"min_stock" int4 DEFAULT 0,"max_stock" int4 DEFAULT 0) WITH OIDS;
SELECT setval('product_id_seq',1);
CREATE TABLE pickslips ("slipid" serial NOT NULL PRIMARY KEY ,"deptid" numeric DEFAULT 0,"cusnum" numeric DEFAULT 0,"cusname" varchar ,"contname" varchar ,"cellno" varchar ,"jobid" numeric DEFAULT 0,"jobnum" varchar ,"pdate" date ,"pname" varchar ,"username" varchar ,"printed" varchar ,"div" numeric DEFAULT 0) WITH OIDS;
SELECT setval('pickslips_slipid_seq',1);
CREATE TABLE serial4 ("stkid" numeric DEFAULT 0,"serno" varchar ,"rsvd" varchar ,"warranty" date ) WITH OIDS;
CREATE TABLE credit_note_accounts ("id" serial NOT NULL PRIMARY KEY ,"accid" numeric DEFAULT 0) WITH OIDS;
SELECT setval('credit_note_accounts_id_seq',1);
CREATE TABLE stmnt ("type" varchar ,"cusnum" numeric DEFAULT 0,"invid" numeric DEFAULT 0,"date" date ,"st" varchar ,"div" numeric DEFAULT 0,"amount" numeric(16, 2) DEFAULT 0,"timeadded" timestamp ,"docref" varchar ,"branch" varchar ,"id" serial NOT NULL PRIMARY KEY ,"refnum" numeric DEFAULT 0,"allocation_date" date ,"allocation" varchar DEFAULT ''::character varying,"reverse_allocation_dates" varchar ,"reverse_allocation" varchar ,"reverse_allocation_amounts" varchar ,"allocation_balance" numeric(16, 2) DEFAULT 0,"allocation_processed" numeric DEFAULT 0,"allocation_linked" varchar DEFAULT ''::character varying,"allocation_amounts" varchar DEFAULT ''::character varying) WITH OIDS;
SELECT setval('stmnt_id_seq',1);
CREATE TABLE mail_account_settings ("account_id" int4 DEFAULT 0,"fid_inbox" int4 DEFAULT 0,"fid_draft" int4 DEFAULT 0,"fid_sent" int4 DEFAULT 0,"fid_trash" int4 DEFAULT 0,"fid_outbox" int4 DEFAULT 0) WITH OIDS;
CREATE TABLE ss7 ("stock" int4 DEFAULT 0,"code" varchar ,"div" numeric DEFAULT 0,"active" varchar DEFAULT 'yes'::character varying) WITH OIDS;
CREATE TABLE req ("id" serial NOT NULL PRIMARY KEY ,"sender" varchar ,"recipient" varchar ,"message" varchar ,"timesent" timestamp ,"reference" varchar ,"reference_id" int4 DEFAULT 0,"viewed" varchar ,"div" numeric DEFAULT 0,"alerted" varchar ) WITH OIDS;
SELECT setval('req_id_seq',1);
CREATE VIEW prd_pinvoices AS ((((((((((SELECT '1' AS iprd, pinvoices.invid, pinvoices.deptid, pinvoices.cusnum, pinvoices.cordno, pinvoices.ordno, pinvoices.chrgvat, pinvoices.terms, pinvoices.salespn, pinvoices.odate, pinvoices.printed, pinvoices.comm, pinvoices.done, pinvoices.username, pinvoices.deptname, pinvoices.cusacc, pinvoices.cusname, pinvoices.surname, pinvoices.cusaddr, pinvoices.cusordno, pinvoices.cusvatno, pinvoices.prd, pinvoices.invnum, pinvoices.div, pinvoices.prints, pinvoices.disc, pinvoices.discp, pinvoices.delchrg, pinvoices.subtot, pinvoices.traddisc, pinvoices.balance, pinvoices.vat, pinvoices.total, pinvoices.discount, pinvoices.delivery, pinvoices.nbal, pinvoices.rdelchrg, pinvoices.serd, pinvoices.rounding, pinvoices.delvat, pinvoices.vatnum, pinvoices.pcash, pinvoices.pcheque, pinvoices.pcc, pinvoices.pcredit, pinvoices.systime, pinvoices.telno FROM "1".pinvoices UNION SELECT '2' AS iprd, pinvoices.invid, pinvoices.deptid, pinvoices.cusnum, pinvoices.cordno, pinvoices.ordno, pinvoices.chrgvat, pinvoices.terms, pinvoices.salespn, pinvoices.odate, pinvoices.printed, pinvoices.comm, pinvoices.done, pinvoices.username, pinvoices.deptname, pinvoices.cusacc, pinvoices.cusname, pinvoices.surname, pinvoices.cusaddr, pinvoices.cusordno, pinvoices.cusvatno, pinvoices.prd, pinvoices.invnum, pinvoices.div, pinvoices.prints, pinvoices.disc, pinvoices.discp, pinvoices.delchrg, pinvoices.subtot, pinvoices.traddisc, pinvoices.balance, pinvoices.vat, pinvoices.total, pinvoices.discount, pinvoices.delivery, pinvoices.nbal, pinvoices.rdelchrg, pinvoices.serd, pinvoices.rounding, pinvoices.delvat, pinvoices.vatnum, pinvoices.pcash, pinvoices.pcheque, pinvoices.pcc, pinvoices.pcredit, pinvoices.systime, pinvoices.telno FROM "2".pinvoices) UNION SELECT '3' AS iprd, pinvoices.invid, pinvoices.deptid, pinvoices.cusnum, pinvoices.cordno, pinvoices.ordno, pinvoices.chrgvat, pinvoices.terms, pinvoices.salespn, pinvoices.odate, pinvoices.printed, pinvoices.comm, pinvoices.done, pinvoices.username, pinvoices.deptname, pinvoices.cusacc, pinvoices.cusname, pinvoices.surname, pinvoices.cusaddr, pinvoices.cusordno, pinvoices.cusvatno, pinvoices.prd, pinvoices.invnum, pinvoices.div, pinvoices.prints, pinvoices.disc, pinvoices.discp, pinvoices.delchrg, pinvoices.subtot, pinvoices.traddisc, pinvoices.balance, pinvoices.vat, pinvoices.total, pinvoices.discount, pinvoices.delivery, pinvoices.nbal, pinvoices.rdelchrg, pinvoices.serd, pinvoices.rounding, pinvoices.delvat, pinvoices.vatnum, pinvoices.pcash, pinvoices.pcheque, pinvoices.pcc, pinvoices.pcredit, pinvoices.systime, pinvoices.telno FROM "3".pinvoices) UNION SELECT '4' AS iprd, pinvoices.invid, pinvoices.deptid, pinvoices.cusnum, pinvoices.cordno, pinvoices.ordno, pinvoices.chrgvat, pinvoices.terms, pinvoices.salespn, pinvoices.odate, pinvoices.printed, pinvoices.comm, pinvoices.done, pinvoices.username, pinvoices.deptname, pinvoices.cusacc, pinvoices.cusname, pinvoices.surname, pinvoices.cusaddr, pinvoices.cusordno, pinvoices.cusvatno, pinvoices.prd, pinvoices.invnum, pinvoices.div, pinvoices.prints, pinvoices.disc, pinvoices.discp, pinvoices.delchrg, pinvoices.subtot, pinvoices.traddisc, pinvoices.balance, pinvoices.vat, pinvoices.total, pinvoices.discount, pinvoices.delivery, pinvoices.nbal, pinvoices.rdelchrg, pinvoices.serd, pinvoices.rounding, pinvoices.delvat, pinvoices.vatnum, pinvoices.pcash, pinvoices.pcheque, pinvoices.pcc, pinvoices.pcredit, pinvoices.systime, pinvoices.telno FROM "4".pinvoices) UNION SELECT '5' AS iprd, pinvoices.invid, pinvoices.deptid, pinvoices.cusnum, pinvoices.cordno, pinvoices.ordno, pinvoices.chrgvat, pinvoices.terms, pinvoices.salespn, pinvoices.odate, pinvoices.printed, pinvoices.comm, pinvoices.done, pinvoices.username, pinvoices.deptname, pinvoices.cusacc, pinvoices.cusname, pinvoices.surname, pinvoices.cusaddr, pinvoices.cusordno, pinvoices.cusvatno, pinvoices.prd, pinvoices.invnum, pinvoices.div, pinvoices.prints, pinvoices.disc, pinvoices.discp, pinvoices.delchrg, pinvoices.subtot, pinvoices.traddisc, pinvoices.balance, pinvoices.vat, pinvoices.total, pinvoices.discount, pinvoices.delivery, pinvoices.nbal, pinvoices.rdelchrg, pinvoices.serd, pinvoices.rounding, pinvoices.delvat, pinvoices.vatnum, pinvoices.pcash, pinvoices.pcheque, pinvoices.pcc, pinvoices.pcredit, pinvoices.systime, pinvoices.telno FROM "5".pinvoices) UNION SELECT '6' AS iprd, pinvoices.invid, pinvoices.deptid, pinvoices.cusnum, pinvoices.cordno, pinvoices.ordno, pinvoices.chrgvat, pinvoices.terms, pinvoices.salespn, pinvoices.odate, pinvoices.printed, pinvoices.comm, pinvoices.done, pinvoices.username, pinvoices.deptname, pinvoices.cusacc, pinvoices.cusname, pinvoices.surname, pinvoices.cusaddr, pinvoices.cusordno, pinvoices.cusvatno, pinvoices.prd, pinvoices.invnum, pinvoices.div, pinvoices.prints, pinvoices.disc, pinvoices.discp, pinvoices.delchrg, pinvoices.subtot, pinvoices.traddisc, pinvoices.balance, pinvoices.vat, pinvoices.total, pinvoices.discount, pinvoices.delivery, pinvoices.nbal, pinvoices.rdelchrg, pinvoices.serd, pinvoices.rounding, pinvoices.delvat, pinvoices.vatnum, pinvoices.pcash, pinvoices.pcheque, pinvoices.pcc, pinvoices.pcredit, pinvoices.systime, pinvoices.telno FROM "6".pinvoices) UNION SELECT '7' AS iprd, pinvoices.invid, pinvoices.deptid, pinvoices.cusnum, pinvoices.cordno, pinvoices.ordno, pinvoices.chrgvat, pinvoices.terms, pinvoices.salespn, pinvoices.odate, pinvoices.printed, pinvoices.comm, pinvoices.done, pinvoices.username, pinvoices.deptname, pinvoices.cusacc, pinvoices.cusname, pinvoices.surname, pinvoices.cusaddr, pinvoices.cusordno, pinvoices.cusvatno, pinvoices.prd, pinvoices.invnum, pinvoices.div, pinvoices.prints, pinvoices.disc, pinvoices.discp, pinvoices.delchrg, pinvoices.subtot, pinvoices.traddisc, pinvoices.balance, pinvoices.vat, pinvoices.total, pinvoices.discount, pinvoices.delivery, pinvoices.nbal, pinvoices.rdelchrg, pinvoices.serd, pinvoices.rounding, pinvoices.delvat, pinvoices.vatnum, pinvoices.pcash, pinvoices.pcheque, pinvoices.pcc, pinvoices.pcredit, pinvoices.systime, pinvoices.telno FROM "7".pinvoices) UNION SELECT '8' AS iprd, pinvoices.invid, pinvoices.deptid, pinvoices.cusnum, pinvoices.cordno, pinvoices.ordno, pinvoices.chrgvat, pinvoices.terms, pinvoices.salespn, pinvoices.odate, pinvoices.printed, pinvoices.comm, pinvoices.done, pinvoices.username, pinvoices.deptname, pinvoices.cusacc, pinvoices.cusname, pinvoices.surname, pinvoices.cusaddr, pinvoices.cusordno, pinvoices.cusvatno, pinvoices.prd, pinvoices.invnum, pinvoices.div, pinvoices.prints, pinvoices.disc, pinvoices.discp, pinvoices.delchrg, pinvoices.subtot, pinvoices.traddisc, pinvoices.balance, pinvoices.vat, pinvoices.total, pinvoices.discount, pinvoices.delivery, pinvoices.nbal, pinvoices.rdelchrg, pinvoices.serd, pinvoices.rounding, pinvoices.delvat, pinvoices.vatnum, pinvoices.pcash, pinvoices.pcheque, pinvoices.pcc, pinvoices.pcredit, pinvoices.systime, pinvoices.telno FROM "8".pinvoices) UNION SELECT '9' AS iprd, pinvoices.invid, pinvoices.deptid, pinvoices.cusnum, pinvoices.cordno, pinvoices.ordno, pinvoices.chrgvat, pinvoices.terms, pinvoices.salespn, pinvoices.odate, pinvoices.printed, pinvoices.comm, pinvoices.done, pinvoices.username, pinvoices.deptname, pinvoices.cusacc, pinvoices.cusname, pinvoices.surname, pinvoices.cusaddr, pinvoices.cusordno, pinvoices.cusvatno, pinvoices.prd, pinvoices.invnum, pinvoices.div, pinvoices.prints, pinvoices.disc, pinvoices.discp, pinvoices.delchrg, pinvoices.subtot, pinvoices.traddisc, pinvoices.balance, pinvoices.vat, pinvoices.total, pinvoices.discount, pinvoices.delivery, pinvoices.nbal, pinvoices.rdelchrg, pinvoices.serd, pinvoices.rounding, pinvoices.delvat, pinvoices.vatnum, pinvoices.pcash, pinvoices.pcheque, pinvoices.pcc, pinvoices.pcredit, pinvoices.systime, pinvoices.telno FROM "9".pinvoices) UNION SELECT '10' AS iprd, pinvoices.invid, pinvoices.deptid, pinvoices.cusnum, pinvoices.cordno, pinvoices.ordno, pinvoices.chrgvat, pinvoices.terms, pinvoices.salespn, pinvoices.odate, pinvoices.printed, pinvoices.comm, pinvoices.done, pinvoices.username, pinvoices.deptname, pinvoices.cusacc, pinvoices.cusname, pinvoices.surname, pinvoices.cusaddr, pinvoices.cusordno, pinvoices.cusvatno, pinvoices.prd, pinvoices.invnum, pinvoices.div, pinvoices.prints, pinvoices.disc, pinvoices.discp, pinvoices.delchrg, pinvoices.subtot, pinvoices.traddisc, pinvoices.balance, pinvoices.vat, pinvoices.total, pinvoices.discount, pinvoices.delivery, pinvoices.nbal, pinvoices.rdelchrg, pinvoices.serd, pinvoices.rounding, pinvoices.delvat, pinvoices.vatnum, pinvoices.pcash, pinvoices.pcheque, pinvoices.pcc, pinvoices.pcredit, pinvoices.systime, pinvoices.telno FROM "10".pinvoices) UNION SELECT '11' AS iprd, pinvoices.invid, pinvoices.deptid, pinvoices.cusnum, pinvoices.cordno, pinvoices.ordno, pinvoices.chrgvat, pinvoices.terms, pinvoices.salespn, pinvoices.odate, pinvoices.printed, pinvoices.comm, pinvoices.done, pinvoices.username, pinvoices.deptname, pinvoices.cusacc, pinvoices.cusname, pinvoices.surname, pinvoices.cusaddr, pinvoices.cusordno, pinvoices.cusvatno, pinvoices.prd, pinvoices.invnum, pinvoices.div, pinvoices.prints, pinvoices.disc, pinvoices.discp, pinvoices.delchrg, pinvoices.subtot, pinvoices.traddisc, pinvoices.balance, pinvoices.vat, pinvoices.total, pinvoices.discount, pinvoices.delivery, pinvoices.nbal, pinvoices.rdelchrg, pinvoices.serd, pinvoices.rounding, pinvoices.delvat, pinvoices.vatnum, pinvoices.pcash, pinvoices.pcheque, pinvoices.pcc, pinvoices.pcredit, pinvoices.systime, pinvoices.telno FROM "11".pinvoices) UNION SELECT '12' AS iprd, pinvoices.invid, pinvoices.deptid, pinvoices.cusnum, pinvoices.cordno, pinvoices.ordno, pinvoices.chrgvat, pinvoices.terms, pinvoices.salespn, pinvoices.odate, pinvoices.printed, pinvoices.comm, pinvoices.done, pinvoices.username, pinvoices.deptname, pinvoices.cusacc, pinvoices.cusname, pinvoices.surname, pinvoices.cusaddr, pinvoices.cusordno, pinvoices.cusvatno, pinvoices.prd, pinvoices.invnum, pinvoices.div, pinvoices.prints, pinvoices.disc, pinvoices.discp, pinvoices.delchrg, pinvoices.subtot, pinvoices.traddisc, pinvoices.balance, pinvoices.vat, pinvoices.total, pinvoices.discount, pinvoices.delivery, pinvoices.nbal, pinvoices.rdelchrg, pinvoices.serd, pinvoices.rounding, pinvoices.delvat, pinvoices.vatnum, pinvoices.pcash, pinvoices.pcheque, pinvoices.pcc, pinvoices.pcredit, pinvoices.systime, pinvoices.telno FROM "12".pinvoices;
CREATE VIEW salamt_pay AS SELECT salpaid.empnum, salpaid.saldate, salpaid.cyear, salpaid."month", salpaid.week, salpaid.true_ids, salpaid.taxed_sal AS payegross, ((salpaid.salary + salpaid.paye) + salpaid.uif) AS netgross, salpaid.paye FROM cubit.salpaid;
CREATE VIEW salamt_rev AS SELECT salr.empnum, salr.saldate, salr.cyear, salr."month", salr.week, salr.true_ids, salr.taxed_sal AS payegross, ((salr.salary + salr.paye) + salr.uif) AS netgross, salr.paye FROM cubit.salr;

