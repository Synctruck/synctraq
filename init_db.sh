#!/bin/bash

echo "Waiting for MySQL to be ready..."
until mysql -u root -ppassword -h localhost -e "SELECT 1" 2>/dev/null; do
  sleep 5
done

echo "Importing database..."
mysql -u root -ppassword synctruck < /docker-entrypoint-initdb.d/db_backup.sql
echo "Database import completed."
