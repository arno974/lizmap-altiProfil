SHELL = bash
.ONESHELL:
.PHONY: env
#

LIZMAP_DIR=$(shell pwd)/lizmap

LIZMAP_USER_ID:=$(shell id -u)
LIZMAP_USER_GID:=$(shell id -g)

LIZMAP_VERSION_TAG:=3.9.0-rc.3
QGIS_VERSION_TAG:=3.40
POSTGIS_VERSION_TAG:=14-3

QGIS_MAP_WORKERS:=1

LIZMAP_PORT:=127.0.0.1:9012
OWS_PORT:=127.0.0.1:9013
LZMPGPORT:=127.0.0.1:9014

BASEDIR:=$(shell realpath ..)

PGMETADATA_MODULE_SRC:=$(shell realpath ..)

main:
	@cat <<- EOF
		Commandes:
			run:   initialise environnement and run stack
			stop:  stop docker and remove containers
			clean: reset containers and remove lizmap configuration

			Note: Un fichier d'environment pour docker compose
			est crée à chaque invocation de 'run'.
			Cela permet d'utiliser les commandes 'docker compose'
			sans passer par le Makefile
	EOF

dirs:
	@mkdir -p $(LIZMAP_DIR)/var/log/nginx \
		      $(LIZMAP_DIR)/var/nginx-cache \
		      $(LIZMAP_DIR)/qgis-server-plugins \
		      $*

env: dirs
	@cat <<- EOF > .env
		COMPOSE_PROJECT_NAME=altiprofil
		LIZMAP_DIR=$(LIZMAP_DIR)
		LIZMAP_USER_ID=$(LIZMAP_USER_ID)
		LIZMAP_USER_GID=$(LIZMAP_USER_GID)
		QGIS_MAP_WORKERS=$(QGIS_MAP_WORKERS)
		LIZMAP_PORT=$(LIZMAP_PORT)
		OWS_PORT=$(OWS_PORT)
		LZMPGPORT=$(LZMPGPORT)
		PGMETADATA_MODULE_SRC=$(PGMETADATA_MODULE_SRC)
		LIZMAP_VERSION_TAG=$(LIZMAP_VERSION_TAG)
		QGIS_VERSION_TAG=$(QGIS_VERSION_TAG)
		POSTGIS_VERSION_TAG=$(POSTGIS_VERSION_TAG)
		EOF

run: stop env install-plugins
	docker compose up -d

stop:
	docker compose down || true

clean: stop
	docker compose down -v
	rm -rf $(LIZMAP_DIR)/www/*
	rm -rf $(LIZMAP_DIR)/var/*

# Pull images from github 3liz
pull:
	docker pull 3liz/lizmap-web-client:${LIZMAP_VERSION_TAG}
	docker pull 3liz/qgis-map-server:${QGIS_VERSION_TAG}
	docker pull 3liz/postgis:${POSTGIS_VERSION_TAG}

shell:
	docker compose exec -u ${LIZMAP_USER_ID} lizmap /bin/sh -l

install-module:
	docker compose exec -u ${LIZMAP_USER_ID} lizmap php /www/lizmap/install/configurator.php altiProfil
	docker compose exec -u ${LIZMAP_USER_ID} lizmap php /www/lizmap/install/configurator.php altiProfilAdmin
	docker compose exec -u ${LIZMAP_USER_ID} lizmap php /www/lizmap/install/installer.php -v


import-lizmap-acl:
	@echo "Set repositories and users rights if needed"
	@echo "Set view project"
	docker compose exec -u $(LIZMAP_USER_ID) lizmap /bin/sh -c 'php lizmap/console.php acl2:add __anonymous "lizmap.repositories.view" altiprofil'
	docker compose exec -u $(LIZMAP_USER_ID) lizmap /bin/sh -c 'php lizmap/console.php acl2:add users "lizmap.repositories.view" altiprofil'
	docker compose exec -u $(LIZMAP_USER_ID) lizmap /bin/sh -c 'php lizmap/console.php acl2:add admins "lizmap.repositories.view" altiprofil'
	@echo "Display WMS links"
	docker compose exec -u $(LIZMAP_USER_ID) lizmap /bin/sh -c 'php lizmap/console.php acl2:add __anonymous "lizmap.tools.displayGetCapabilitiesLinks" altiprofil'
	docker compose exec -u $(LIZMAP_USER_ID) lizmap /bin/sh -c 'php lizmap/console.php acl2:add users "lizmap.tools.displayGetCapabilitiesLinks" altiprofil'
	docker compose exec -u $(LIZMAP_USER_ID) lizmap /bin/sh -c 'php lizmap/console.php acl2:add admins "lizmap.tools.displayGetCapabilitiesLinks" altiprofil'
	@echo "Edition"
	docker compose exec -u $(LIZMAP_USER_ID) lizmap /bin/sh -c 'php lizmap/console.php acl2:add __anonymous "lizmap.tools.edition.use" altiprofil'
	docker compose exec -u $(LIZMAP_USER_ID) lizmap /bin/sh -c 'php lizmap/console.php acl2:add users "lizmap.tools.edition.use" altiprofil'
	docker compose exec -u $(LIZMAP_USER_ID) lizmap /bin/sh -c 'php lizmap/console.php acl2:add admins "lizmap.tools.edition.use" altiprofil'
	@echo "Export layers"
	docker compose exec -u $(LIZMAP_USER_ID) lizmap /bin/sh -c 'php lizmap/console.php acl2:add __anonymous "lizmap.tools.layer.export" altiprofil'
	docker compose exec -u $(LIZMAP_USER_ID) lizmap /bin/sh -c 'php lizmap/console.php acl2:add users "lizmap.tools.layer.export" altiprofil'
	docker compose exec -u $(LIZMAP_USER_ID) lizmap /bin/sh -c 'php lizmap/console.php acl2:add admins "lizmap.tools.layer.export" altiprofil'

import-data:
	export PGPASSWORD=lizmap1234!; psql -h localhost -U lizmap -d lizmap -p 9014 -f sql/rgealti_5m_mtp_create.sql
	export PGPASSWORD=lizmap1234!; psql -h localhost -U lizmap -d lizmap -p 9014 -f sql/rgealti_5m_mtp_insert1.sql
	export PGPASSWORD=lizmap1234!; psql -h localhost -U lizmap -d lizmap -p 9014 -f sql/rgealti_5m_mtp_insert2.sql
	export PGPASSWORD=lizmap1234!; psql -h localhost -U lizmap -d lizmap -p 9014 -f sql/rgealti_5m_mtp_optimize.sql


install-plugins:
	docker run \
	    -u $(LIZMAP_USER_ID):$(LIZMAP_USER_GID) \
	    --rm -it \
	    -e QGSRV_SERVER_PLUGINPATH=/srv/plugins \
	    -v $(LIZMAP_DIR)/qgis-server-plugins:/srv/plugins \
	    -v $(shell pwd)/:/src \
	    --entrypoint /src/add-server-plugins.sh \
	    3liz/qgis-map-server:${QGIS_VERSION_TAG}
