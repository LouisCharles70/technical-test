cp .env.example .env
echo 'DB_HOST=lalamove-database' >> .env
echo 'DB_DATABASE=database' >> .env
echo 'DB_USERNAME=laraveluser' >> .env
echo 'DB_PASSWORD=root' >> .env


docker-compose up --build -d

#Wait for all the containers to be ready
sleep 10

docker exec lalamove-app composer install --no-interaction && npm install && php artisan key:generate
sleep 2
docker exec lalamove-app php artisan migrate
