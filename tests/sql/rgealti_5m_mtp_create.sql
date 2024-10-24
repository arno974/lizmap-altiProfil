BEGIN;
CREATE SCHEMA IF NOT EXISTS "raster";
DROP TABLE IF EXISTS "raster"."rgealti_5m_mtp";
CREATE TABLE "raster"."rgealti_5m_mtp" ("rid" serial PRIMARY KEY,"rast" raster);
END;
