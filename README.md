# Waste-Not-Kitchen
A small web app that connects restaurants with surplus food to customers, donors, and those in need. Built with PHP, MySQL (MAMP), and vanilla JavaScript, it lets restaurants list plates, users reserve or donate meals, and admins view reports via a role-based interface.

**Tech:** PHP 7+/8+, MySQL (MAMP), JavaScript, CSS

**Quick start (MAMP)**
**Prerequisites:**
- **MAMP**: Start Apache & MySQL from the MAMP app.
- **PHP**: Use the PHP bundled with MAMP or your system PHP compatible with the app.

**1) Put project in MAMP web root**
- Copy the project folder to MAMP's `htdocs`, e.g. ` /Applications/MAMP/htdocs/Waste-Not-Kitchen` (this repo already lives there).

**2) Create the database**
Keep schema changes in `database/schema.sql`. It already contains the `CREATE DATABASE` and `USE` statements.

Run one of these depending on your MAMP configuration.

Preferred (UNIX socket):

```zsh
/Applications/MAMP/Library/bin/mysql80/bin/mysql \
	--socket=/Applications/MAMP/tmp/mysql/mysql.sock \
	-u root -p \
	< database/schema.sql
```

Fallback (host/port):

```zsh
/Applications/MAMP/Library/bin/mysql80/bin/mysql \
	-h 127.0.0.1 -P 8889 -u root -p \
	< database/schema.sql
```

If your MAMP uses MySQL 5.7 change `mysql80` to `mysql57` in the path.

Commit your schema changes to Git when you update `database/schema.sql`:

```zsh
git add database/schema.sql
git commit -m "chore(db): update schema"
git push origin <your-branch>
```

**3) (Optional) Install seed data**

To load sample data from `database/seed.sql` (recommended for development):

```zsh
/Applications/MAMP/Library/bin/mysql80/bin/mysql --login-path=mamp < database/seed.sql
```

If you don't want to type a password each time, set a secure login path (one-time):

```zsh
# store credentials (one-time)
/Applications/MAMP/Library/bin/mysql80/bin/mysql_config_editor set \
	--login-path=mamp \
	--socket=/Applications/MAMP/tmp/mysql/mysql.sock \
	--user=root --password
# then run without -p
/Applications/MAMP/Library/bin/mysql80/bin/mysql --login-path=mamp < database/schema.sql
```

**4) Configure app secrets**
- The project expects a local `.env` for secrets (this file is gitignored). Add DB creds there or rely on your MAMP login-path. See `config/config.php` for how DB is loaded.

**5) Open the app**
- In a browser, open the MAMP Apache URL. Common defaults:
	- `http://localhost:8888/Waste-Not-Kitchen/` (if Apache uses port 8888)
	- `http://localhost/Waste-Not-Kitchen/` (if default HTTP port)

**Files & useful paths**
- `index.php`: public landing / entry
- `admin-dashboard.php`, `landing-page.php`, `logout.php`: top-level pages
- `config/config.php`: app configuration
- `database/schema.sql` and `database/seed.sql`: DB schema and seed data
- `modules/`, `customer/`, `donor/`, `needy/`: role-based modules and controllers
- `assets/`: CSS, fonts, images; `js/`: client scripts

**Developer notes**
- Branch: you're currently working on branch `anna`.
- No automated tests are included. Use the browser + MAMP logs for verification.
- To debug API requests, check `tmp/last_response.json` and `tmp/forgot_request_debug.txt`.

**Contributing**
- Keep DB changes in `database/schema.sql` and add migration notes in PR descriptions.

**Secrets**
- `.env` is gitignored — keep credentials local or use MAMP login path. Do not commit secrets.

Questions or issues? Open an issue or contact the repo owner.
