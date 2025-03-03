# Berlinger Club World Cup

## Requirements

- Docker and Docker Compose

## Setup

1. Clone the repository:
```bash
git clone git@github.com:agilasadi/carrier.git
cd carrier
```

2. Copy the environment file:
```bash
cp .env.example .env
```

3. Start the application:
```bash
docker-compose up -d
```

4. Set up the application:
```bash
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate --seed
```

## Running the Tournament

To run the tournament and see the results:
```bash
docker-compose exec app php artisan cup:play-matches
```

## Testing

Run the test suite:
```bash
docker-compose exec app php artisan test
```

## License

This project is open-sourced software licensed under the MIT license.
