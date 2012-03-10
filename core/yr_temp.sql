--
-- Selected TOC Entries:
--
\connect - postgres

--
-- TOC Entry ID 5 (OID 18181)
--
-- Name: pga_queries Type: TABLE Owner: postgres
--

CREATE TABLE "pga_queries" (
	"queryname" character varying(64),
	"querytype" character(1),
	"querycommand" text,
	"querytables" text,
	"querylinks" text,
	"queryresults" text,
	"querycomments" text
);

--
-- TOC Entry ID 6 (OID 18181)
--
-- Name: pga_queries Type: ACL Owner: 
--

REVOKE ALL on "pga_queries" from PUBLIC;
GRANT ALL on "pga_queries" to PUBLIC;
GRANT ALL on "pga_queries" to "postgres";

--
-- TOC Entry ID 7 (OID 18186)
--
-- Name: pga_forms Type: TABLE Owner: postgres
--

CREATE TABLE "pga_forms" (
	"formname" character varying(64),
	"formsource" text
);

--
-- TOC Entry ID 8 (OID 18186)
--
-- Name: pga_forms Type: ACL Owner: 
--

REVOKE ALL on "pga_forms" from PUBLIC;
GRANT ALL on "pga_forms" to PUBLIC;
GRANT ALL on "pga_forms" to "postgres";

--
-- TOC Entry ID 9 (OID 18191)
--
-- Name: pga_scripts Type: TABLE Owner: postgres
--

CREATE TABLE "pga_scripts" (
	"scriptname" character varying(64),
	"scriptsource" text
);

--
-- TOC Entry ID 10 (OID 18191)
--
-- Name: pga_scripts Type: ACL Owner: 
--

REVOKE ALL on "pga_scripts" from PUBLIC;
GRANT ALL on "pga_scripts" to PUBLIC;
GRANT ALL on "pga_scripts" to "postgres";

--
-- TOC Entry ID 11 (OID 18196)
--
-- Name: pga_reports Type: TABLE Owner: postgres
--

CREATE TABLE "pga_reports" (
	"reportname" character varying(64),
	"reportsource" text,
	"reportbody" text,
	"reportprocs" text,
	"reportoptions" text
);

--
-- TOC Entry ID 12 (OID 18196)
--
-- Name: pga_reports Type: ACL Owner: 
--

REVOKE ALL on "pga_reports" from PUBLIC;
GRANT ALL on "pga_reports" to PUBLIC;
GRANT ALL on "pga_reports" to "postgres";

--
-- TOC Entry ID 13 (OID 18201)
--
-- Name: pga_schema Type: TABLE Owner: postgres
--

CREATE TABLE "pga_schema" (
	"schemaname" character varying(64),
	"schematables" text,
	"schemalinks" text
);

--
-- TOC Entry ID 14 (OID 18201)
--
-- Name: pga_schema Type: ACL Owner: 
--

REVOKE ALL on "pga_schema" from PUBLIC;
GRANT ALL on "pga_schema" to PUBLIC;
GRANT ALL on "pga_schema" to "postgres";

--
-- TOC Entry ID 2 (OID 18206)
--
-- Name: acccat_catid_seq Type: SEQUENCE Owner: postgres
--

CREATE SEQUENCE "acccat_catid_seq" start 1 increment 1 maxvalue 9223372036854775807 minvalue 1 cache 1;

--
-- TOC Entry ID 4 (OID 18206)
--
-- Name: acccat_catid_seq Type: ACL Owner: 
--

REVOKE ALL on "acccat_catid_seq" from PUBLIC;
GRANT ALL on "acccat_catid_seq" to "postgres";
GRANT ALL on "acccat_catid_seq" to "cubit";

--
-- TOC Entry ID 15 (OID 18208)
--
-- Name: info Type: TABLE Owner: postgres
--

CREATE TABLE "info" (
	"prdname" character varying DEFAULT 'x',
	"prddb" character varying,
	"prdactive" character varying(2)
);

--
-- TOC Entry ID 16 (OID 18208)
--
-- Name: info Type: ACL Owner: 
--

REVOKE ALL on "info" from PUBLIC;
GRANT ALL on "info" to "postgres";
GRANT ALL on "info" to "cubit";

--
-- Data for TOC Entry ID 17 (OID 18181)
--
-- Name: pga_queries Type: TABLE DATA Owner: postgres
--


COPY "pga_queries" FROM stdin;
\.
--
-- Data for TOC Entry ID 18 (OID 18186)
--
-- Name: pga_forms Type: TABLE DATA Owner: postgres
--


COPY "pga_forms" FROM stdin;
\.
--
-- Data for TOC Entry ID 19 (OID 18191)
--
-- Name: pga_scripts Type: TABLE DATA Owner: postgres
--


COPY "pga_scripts" FROM stdin;
\.
--
-- Data for TOC Entry ID 20 (OID 18196)
--
-- Name: pga_reports Type: TABLE DATA Owner: postgres
--


COPY "pga_reports" FROM stdin;
\.
--
-- Data for TOC Entry ID 21 (OID 18201)
--
-- Name: pga_schema Type: TABLE DATA Owner: postgres
--


COPY "pga_schema" FROM stdin;
\.
--
-- Data for TOC Entry ID 22 (OID 18208)
--
-- Name: info Type: TABLE DATA Owner: postgres
--


COPY "info" FROM stdin;
\.
--
-- TOC Entry ID 3 (OID 18206)
--
-- Name: acccat_catid_seq Type: SEQUENCE SET Owner: 
--

SELECT setval ('"acccat_catid_seq"', 3, true);

