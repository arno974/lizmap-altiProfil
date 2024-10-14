BEGIN;
CREATE INDEX ON "raster"."rgealti_5m_mtp" USING gist (st_convexhull("rast"));
ANALYZE "raster"."rgealti_5m_mtp";
SELECT AddRasterConstraints('raster','rgealti_5m_mtp','rast',TRUE,TRUE,TRUE,TRUE,TRUE,TRUE,FALSE,TRUE,TRUE,TRUE,TRUE,TRUE);
END;
VACUUM ANALYZE "raster"."rgealti_5m_mtp";
