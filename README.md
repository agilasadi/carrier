# Berlinger Club World Cup

A Laravel application that simulates the Berlinger Club World Cup tournament with two leagues (A and B) and a final match between the league winners.

## Requirements

- Docker and Docker Compose
- PHP 8.2+
- Composer

## Setup

1. Clone the repository:
```bash
git clone <repository-url>
cd berlinger-cup
```

2. Copy the environment file:
```bash
cp .env.example .env
```

3. Build and start the Docker containers:
```bash
docker-compose up -d --build
```

4. Install dependencies:
```bash
docker-compose exec app composer install
```

5. Generate application key:
```bash
docker-compose exec app php artisan key:generate
```

6. Run database migrations and seed initial data:
```bash
docker-compose exec app php artisan migrate --seed
```

## Running the Tournament

The tournament simulation is divided into three commands that should be run in sequence:

1. Generate matches for both leagues:
```bash
docker-compose exec app php artisan cup:generate-matches
```

2. Play all league matches and display standings:
```bash
docker-compose exec app php artisan cup:play-matches
```

3. Play the final match between league winners:
```bash
docker-compose exec app php artisan cup:final-match
```

## Project Structure

- `app/Models/` - Contains Team, GameMatch, and Standing models
- `app/Services/` - Contains TournamentService for business logic
- `app/Console/Commands/Cup/` - Contains Artisan commands for tournament simulation
- `database/migrations/` - Database schema definitions
- `database/seeders/` - Initial data seeders for teams

## Testing

Run the test suite:
```bash
docker-compose exec app php artisan test
```

## License

This project is open-sourced software licensed under the MIT license.
