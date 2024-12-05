#!/bin/bash

# Actualizar repositorios e instalar dependencias necesarias
echo "Actualizando repositorios e instalando dependencias..."
sudo apt-get update
sudo apt-get install -y ca-certificates curl

# Agregar el directorio para las llaves de Docker
echo "Configurando llaves GPG para Docker..."
sudo install -m 0755 -d /etc/apt/keyrings
sudo curl -fsSL https://download.docker.com/linux/debian/gpg -o /etc/apt/keyrings/docker.asc
sudo chmod a+r /etc/apt/keyrings/docker.asc

# Agregar el repositorio oficial de Docker
echo "Agregando el repositorio de Docker..."
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/debian \
$(. /etc/os-release && echo "$VERSION_CODENAME") stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# Actualizar repositorios nuevamente
echo "Actualizando repositorios después de agregar Docker..."
sudo apt-get update

# Instalar Docker y sus componentes
echo "Instalando Docker y sus componentes..."
sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

# Crear el directorio db_backup
echo "Creando directorio db_backup..."
mkdir -p db_backup

# Descargar el archivo db_backup.sql dentro del directorio db_backup
echo "Descargando db_backup.sql en el directorio db_backup..."
wget -O db_backup/db_backup.sql https://storage.googleapis.com/synphp_info/db_backup.sql

# Ejecutar docker compose up
echo "Ejecutando docker compose up..."
sudo docker compose up

echo "Script completado."
