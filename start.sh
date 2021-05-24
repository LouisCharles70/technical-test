docker-compose up --build -d

#Wait for all the containers to be ready
sleep 10

docker exec lalamove-app php artisan migrate
