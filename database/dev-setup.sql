-- DocuQuest local development databases.
-- Run once as the postgres superuser, e.g. in pgAdmin's Query Tool or:
--   psql -U postgres -f database/dev-setup.sql
-- Tests use docuquest_test (forced in phpunit.xml); Dusk uses docuquest_dusk.

CREATE DATABASE docuquest;
CREATE DATABASE docuquest_test;
CREATE DATABASE docuquest_dusk;
