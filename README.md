# Bunutan

A name roulette for a family gathering. Each person picks **Matanda** or **Bata**, chooses their name from that group's list, and spins. The wheel shows numbers. When it stops, the name behind that number pops up, and the result is saved.

## Rules

- Matanda only draws Matanda names; Bata only draws Bata names.
- Only names on the list can spin, and only in the group the organizer put them in.
- You can't draw your own name.
- Each name can be drawn only once.
- Each person can spin only once.

After picking Matanda or Bata, players see only that group's list. They copy their name from it (tap a name to fill it in, or type to filter the list). The spelling must match; capital letters and extra spaces don't matter. Under the wheel is the numbered list of names still on it. The pick itself is random and made by the server.

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
