CREATE TABLE cubit.document_types (id serial, type_name varchar);
CREATE TABLE cubit.teams (id serial, team_name varchar, team_description varchar, team_email varchar);
CREATE TABLE cubit.documents (id serial, project varchar, area varchar, discipline varchar, doc_type varchar, revision varchar, drawing_num varchar, sheet_num varchar, title varchar, location varchar, contract varchar, contractor varchar, code varchar, issue_for varchar, comments varchar, qs varchar, status varchar, team_id numeric);
CREATE TABLE cubit.document_movement (id serial, timestamp timestamp default CURRENT_TIMESTAMP, doc_id numeric, movement_description varchar, project varchar, area varchar, discipline varchar, doc_type varchar, revision varchar, drawing_num varchar, sheet_num varchar, title varchar, location varchar, contract varchar, contractor varchar, code varchar, issue_for varchar, comments varchar, qs varchar, status varchar, team_id numeric);
CREATE TABLE cubit.document_files ("id" serial NOT NULL PRIMARY KEY ,"doc_id" numeric DEFAULT 0,"filename" varchar ,"file" varchar ,"type" varchar ,"size" varchar ) WITH OIDS;
CREATE TABLE cubit.actions (id serial, doc_id numeric, title varchar, description varchar, date date);
CREATE TABLE cubit.document_departments (id serial, dep_name varchar);
CREATE TABLE cubit.diary_locations (id serial, location varchar, user_id numeric DEFAULT 0):
ALTER TABLE cubit.diary_entries ADD COLUMN loc_id numeric DEFAULT 0;

---------------------------------

CREATE TABLE cubit.todo_main (
	id serial,
	title varchar,
	team_id numeric DEFAULT 0,
	user_id numeric DEFAULT 0
);
CREATE TABLE cubit.todo_sub (id serial, datetime timestamp, description varchar, done varchar, main_id numeric);

ALTER TABLE cubit.document_movement ADD COLUMN location varchar;
ALTER TABLE cubit.todo_main ADD COLUMN team_id numeric DEFAULT 0;