CREATE TABLE cubit.public_holidays (
id serial,
holiday_name varchar,
holiday_type varchar,
holiday_date date
);

CREATE TABLE cubit.loan_types (
id serial,
loan_type varchar
);

ALTER TABLE cubit.emp_loanarchive ADD loan_type numeric default 0;

CREATE TABLE cubit.bursaries (
id serial,
bursary_name varchar,
bursary_details varchar,
date_added date
);

ALTER TABLE cubit.bursaries ADD used varchar;

CREATE TABLE cubit.active_bursaries (
id serial,
bursary numeric default 0,
rec_name varchar,
rec_add1 varchar,
rec_add2 varchar,
rec_add3 varchar,
rec_add4 varchar,
rec_idnum varchar,
rec_telephone varchar,
from_date date,
to_date date,
notes varchar
);

CREATE TABLE cubit.loan_requests (
id serial,
empnum numeric default 0,
loanamt numeric default 0,
loaninstall numeric default 0,
loanint numeric default 0,
loanperiod numeric default 0,
loandate date,
loan_type numeric default 0,
div numeric default 0,
loan_account numeric default 0,
bankacc numeric default 0,
date date,
totamount numeric(13,2) default 0,
loanint_amt numeric(13,2) default 0,
fringebenefit numeric (13,2) default 0,
ldate date,
account numeric default 0,
accid numeric default 0
);

CREATE TABLE cubit.vat_returns_archive (
id serial,
start_date date,
end_date date,
registration_number varchar,
enquire_telephone varchar,
rendering_date varchar,
payment_amount varchar,
remittance_rec_date varchar,
payment_method varchar,
payment_area varchar,
payment_tax_period varchar,
client_data1 varchar,
client_data2 varchar,
client_data3 varchar,
client_data4 varchar,
client_data5 varchar,
trading_name varchar,
tax_period_end1 varchar,
tax_period_end2 varchar,
vat_registration_number varchar,
acc_number1 varchar,
acc_number2 varchar,
acc_number3 varchar,
acc_number4 varchar,
date_received varchar,
area varchar,
area2 varchar,
field_1 varchar,
field_1a varchar,
field_2 varchar,
field_3 varchar,
field_4 varchar,
field_4a varchar,
field_5 varchar,
field_6 varchar,
field_7 varchar,
field_8 varchar,
field_9 varchar,
field_10 varchar,
field_11 varchar,
field_12 varchar,
field_13 varchar,
field_14 varchar,
field_15 varchar,
field_16 varchar,
field_17 varchar,
field_18 varchar,
field_19 varchar,
field_20 varchar,
field_25 varchar,
field_26 varchar,
field_26_1 varchar,
field_27 varchar,
field_28 varchar,
field_29 varchar,
field_31 varchar,
field_32 varchar,
field_32_1 varchar,
field_33 varchar,
field_34 varchar,
field_36 varchar,
field_37 varchar,
field_37_1 varchar,
field_38 varchar,
field_39 varchar,
field_40 varchar
);


ALTER TABLE cubit.customers ADD add1 varchar;
ALTER TABLE cubit.customers ADD add2 varchar;

ALTER TABLE cubit.serialrec ADD tdate date;

ALTER TABLE "1".inv_notes ADD branch numeric default 0;
ALTER TABLE "2".inv_notes ADD branch numeric default 0;
ALTER TABLE "3".inv_notes ADD branch numeric default 0;
ALTER TABLE "4".inv_notes ADD branch numeric default 0;
ALTER TABLE "5".inv_notes ADD branch numeric default 0;
ALTER TABLE "6".inv_notes ADD branch numeric default 0;
ALTER TABLE "7".inv_notes ADD branch numeric default 0;
ALTER TABLE "8".inv_notes ADD branch numeric default 0;
ALTER TABLE "9".inv_notes ADD branch numeric default 0;
ALTER TABLE "10".inv_notes ADD branch numeric default 0;
ALTER TABLE "11".inv_notes ADD branch numeric default 0;
ALTER TABLE "12".inv_notes ADD branch numeric default 0;
ALTER TABLE "13".inv_notes ADD branch numeric default 0;
ALTER TABLE "14".inv_notes ADD branch numeric default 0;

ALTER TABLE cubit.emp_loaninstallments ADD fdate date;
ALTER TABLE cubit.emp_loaninstallments ADD fperiod integer default 0;
ALTER TABLE cubit.emp_loaninstallments ADD interest numeric(16,2) default 0;

CREATE TABLE cubit.occ_level (
id serial,
level varchar
);

CREATE TABLE cubit.occ_cat (
id serial,
cat varchar
);

CREATE TABLE cubit.pos_filled (
id serial,
method varchar
);

CREATE TABLE cubit.departments (
id serial,
department varchar,
date_added date
);

CREATE TABLE cubit.unions (
id serial,
union_name varchar,
date_added date
);
ALTER TABLE cubit.unions ADD req_perc numeric(16,2) default 0;

INSERT INTO cubit.occ_cat (cat) VALUES  ('Executive Management');
INSERT INTO cubit.occ_cat (cat) VALUES  ('Other/Senior Managers');
INSERT INTO cubit.occ_cat (cat) VALUES  ('Professionals');
INSERT INTO cubit.occ_cat (cat) VALUES  ('Technicians & Assoc Prof.');
INSERT INTO cubit.occ_cat (cat) VALUES  ('Clerical, Secretarial & Admin');
INSERT INTO cubit.occ_cat (cat) VALUES  ('Services & Sales');
INSERT INTO cubit.occ_cat (cat) VALUES  ('Crafts & Related Trades');
INSERT INTO cubit.occ_cat (cat) VALUES  ('Plant & Machine Operators and assemblers');
INSERT INTO cubit.occ_cat (cat) VALUES  ('Labourers & Related/Elementary Occupations');

INSERT INTO cubit.occ_level (level) VALUES  ('Top Management');
INSERT INTO cubit.occ_level (level) VALUES  ('Senior Management');
INSERT INTO cubit.occ_level (level) VALUES  ('Qualified Specialists/Mid-management');
INSERT INTO cubit.occ_level (level) VALUES  ('Qualified workers,Junior management,Superintendents');
INSERT INTO cubit.occ_level (level) VALUES  ('Semi-skilled and discretionary decision making');
INSERT INTO cubit.occ_level (level) VALUES  ('Unskilled & Defined Decision Making');

INSERT INTO cubit.pos_filled (method) VALUES ('Internally');
INSERT INTO cubit.pos_filled (method) VALUES ('Externally');
INSERT INTO cubit.pos_filled (method) VALUES ('Other');

INSERT INTO cubit.departments (department,date_added) VALUES ('Permanents','now');
INSERT INTO cubit.departments (department,date_added) VALUES ('Night Staff','now');
INSERT INTO cubit.departments (department,date_added) VALUES ('Botswana','now');

INSERT INTO cubit.unions (union_name,date_added,req_perc) VALUES ('SAUJ','now','14.29');
INSERT INTO cubit.unions (union_name,date_added,req_perc) VALUES ('SATU','now','14.29');
INSERT INTO cubit.unions (union_name,date_added,req_perc) VALUES ('CEPPWAWU','now','14.29');
INSERT INTO cubit.unions (union_name,date_added,req_perc) VALUES ('MWASA','now','14.29');
INSERT INTO cubit.unions (union_name,date_added,req_perc) VALUES ('ECCAWUSA','now','14.29');
INSERT INTO cubit.unions (union_name,date_added,req_perc) VALUES ('S.A.C.C.A.W.A','now','14.29');
INSERT INTO cubit.unions (union_name,date_added,req_perc) VALUES ('NON-UNION','now','14.29');
