ALTER TABLE bankacct DROP accnum;
ALTER TABLE bankacct ADD accnum varchar;

ALTER TABLE bankacct ADD cardnum varchar;
ALTER TABLE bankacct ADD mon varchar;
ALTER TABLE bankacct ADD year varchar;
ALTER TABLE bankacct ADD digits varchar;
ALTER TABLE bankacct ADD cardtype varchar;
ALTER TABLE bankacct ADD type varchar;
