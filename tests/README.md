# Run Lizmap stack with docker compose

Steps:

- Launch Lizmap with docker compose
    ```
    # Clean previous versions (optional)
    make clean

    # Run the different services (default is Lizmap 3.8.rc-1)
    make run

    # Install the modules
    make install-module

    make import-lizmap-acl

    make import-data
    ```

- Open your browser at `http://localhost:9012`

For more information, refer to the [docker compose documentation](https://docs.docker.com/compose/)

# Testing Profil from PostgreSQL database table

* Go to `http://localhost:9012/admin.php`
* Click on `Altiprofil` menu
* Click on `Modify` button
* Choose `PostgreSQL database table` for `Altitude provider`
* Put `5` for `Resolution`
* Enter `raster.rgealti_5m_mtp` for `Database table`
* Put `2154` for `SRID`

## Access to the dockerized PostgreSQL instance

You can access the docker PostgreSQL test database `lizmap` from your host by configuring a
[service file](https://docs.qgis.org/latest/en/docs/user_manual/managing_data_source/opening_data.html#postgresql-service-connection-file).
The service file can be stored in your user home `~/.pg_service.conf` and should contain this section

```ini
[lizmap-altiprofil]
dbname=lizmap
host=localhost
port=9014
user=lizmap
password=lizmap1234!
```

Then you can use any PostgreSQL client (psql, QGIS, PgAdmin, DBeaver) and use the `service`
instead of the other credentials (host, port, database name, user and password).

```bash
psql service=lizmap-altiprofil
```

## Access to the lizmap container

If you want to enter into the lizmap container to execute some commands,
execute `make shell`.
