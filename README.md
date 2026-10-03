# Bunutan

A name roulette for a family gathering. Each person types their name, picks **Matanda** or **Bata**, and spins. The wheel shows only numbers. When it stops, the name behind that number pops up, and the result is saved.

## Rules

- Matanda only draws Matanda names; Bata only draws Bata names.
- You can't draw your own name (when your typed name matches the list).
- Each name can be drawn only once.
- Each person can spin only once.

Any typed name is accepted. If it matches someone on the list (the full name, or a first name only one person has), that person's own number is left off their wheel.

## Run it

Requirements: XAMPP (PHP 8.3 and MySQL) and Composer.

1. Start **MySQL** in the XAMPP Control Panel.
2. First time only:
   ```bash
   composer install
   php artisan migrate
   ```
   The database is `bunutan` (see `.env`). Create it in phpMyAdmin first if it doesn't exist.
3. Start the app:
   ```bash
   php artisan serve
   ```
   Open http://localhost:8000. Or, with Apache running in XAMPP, open http://localhost/name-roulette/public.

To let phones on the same Wi-Fi join, run `php artisan serve --host=0.0.0.0` and open `http://<this computer's IP>:8000` on the phones.

## Organizer

The organizer page isn't linked from the players' page. Open `/admin` directly (e.g. http://localhost:8000/admin). The password is `ROULETTE_ORGANIZER_PASSWORD` in `.env`.

There you can:
- paste a list of names (split automatically by new lines, commas, semicolons or tabs) and add them as Matanda or Bata
- move a name to the other group, or remove it
- see who drew whom
- start over (erases the spins, keeps the names)

## Tests

```bash
php artisan test
```
