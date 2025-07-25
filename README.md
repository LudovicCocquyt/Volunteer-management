# Volunteer Management
Private event creation, public volunteer registration form, volunteer planning management in the private section.

## Description
Volunteer Management is a web application designed to streamline the process of managing volunteers for various events and organizations. It allows administrators to create and manage volunteer opportunities, and volunteers to sign up and track their participation.

## Features
- **Event Creation    :** Administrators can create and manage volunteer events.
- **Volunteer Sign-Up :** Volunteers can sign up for events.
- **Event Management  :** Track volunteer attendance and performance.
- **User Management   :** Manage user roles and permissions.

## Technologies Used
- Symfony 7 (PHP framework)
- Preact (JS framework)
- Doctrine ORM
- Twig (templating engine)
- tailwindcss (CSS framework)
- MySQL or PostgreSQL (database)

## Installation

### Prerequisites
- PHP 8.2 or higher
- Composer
- MySQL or PostgreSQL

### Steps
1. Clone the repository:
    ```bash
    git clone https://github.com/LudovicCocquyt/Volunteer-management.git
    cd Volunteer-management
    ```

2. Install dependencies:
    ```bash
    composer install
    ```

3. Set up the database:
    ```bash
    cp .env.example .env
    # Edit .env to configure your database connection and other settings
    # Edit .env to configure your recaptcha connection
    # Edit .env to configure your mailer connection
    # Edit .env to configure your fullcalendar connection, default development
    ```

4. Configure environment variables:
    ```bash
    php bin/console doctrine:database:create
    php bin/console doctrine:shema:update -f

    Add user
        * Id 1 - User Test - Mail test@test.com - Password 123456789
        * INSERT INTO "user"("roles","firstname","email","password","lastname","id") VALUES('[]','Test','test@test.com','$2y$13$pOw4nno9Msgvz2mzJORuieFdAZHxyFlNZjHoV1JV9SfgnMsPmDy6O','Test',1);
    ```

5. Start the dev server:
    ```bash
    php: symfony server:start
    nvm use 20
    node: npm run watch
    ```

6. Access the application at `symfony server URL`.

## Prod
composer install
APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear
./node_modules/.bin/yarn encore production

## Usage
- **Administrators:** Login and create/manage events and users.
- **Volunteers:** Sign up for events.

## Contributing
Contributions are welcome! Please follow these steps:
1. Fork the repository.
2. Create a new branch for your feature or bugfix.
3. Commit your changes and push to your branch.
4. Submit a pull request.

## License
This project is free to use, but it is subject to a specific licence @fullcalendar see conditions and pricing ( https://fullcalendar.io/ ). You are free to use, modify and distribute it as you see fit. However, please note that there is no guarantee or liability regarding its use.

