# NorthCare

NorthCare is an internship project for finding healthcare practitioners in Northern Cyprus. It is built with Laravel 13, Blade, Tailwind CSS 4, and Vite.

## Current implementation

- Patient, practitioner, and institution registration; admin sign-in is separate.
- Private provider application documents, admin review and profile matching, and published profiles.
- Admin-created practitioner/institution profiles, including multiple institution locations.
- Admin-managed cities, regions, two-level categories, services, suggestions, and translations.
- Practitioner service selection and public/registered/private profile visibility.
- Directory results come only from published database profiles; no practitioner data is seeded.

Scheduling, ratings, and moderated reviews are not implemented yet.

## Local setup

Requirements: PHP 8.4+, Composer, Node.js/npm, and a running MySQL server.

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
npm install
npm run build
```

Create a MySQL database named `northcare`, then set `DB_PASSWORD` directly in the local `.env` file. Keep `.env` private; Git ignores it.

```powershell
php artisan migrate --seed
php artisan northcare:admin:create owner@example.com "System Owner"
php artisan serve
```

The admin command prompts for a password without echoing it. Existing administrators can create additional admin accounts from the protected dashboard.

Run `npm run dev` instead of `npm run build` when you want Vite to watch frontend changes during development.

## Checks

```powershell
php artisan test --compact
npm run build
```

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
