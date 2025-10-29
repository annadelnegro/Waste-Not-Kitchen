# Waste-Not-Kitchen
A web app that connects restaurants with surplus food to customers, donors, and those in need. Built with PHP, MySQL, and JavaScript, it enables restaurants to list plates, users to reserve or donate meals, and admins to manage reports through a simple, role-based platform.

## Database (MAMP) — simple workflow

Keep all your schema changes in `database/schema.sql`. It already contains:

- `CREATE DATABASE IF NOT EXISTS waste_not_kitchen ...`
- `USE waste_not_kitchen;`

Start MySQL in the MAMP app, then apply the file from the project root:

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

If your MAMP ships MySQL 5.7, replace `mysql80` with `mysql57` in the path.

Commit your schema changes to Git:

```zsh
git add database/schema.sql
git commit -m "chore(db): update schema"
git push origin main (or your branch)
```

Optional: avoid typing the password each time without exposing it in commands by using a secure login-path:

```zsh
# One-time setup (stores creds securely in your keychain/login-path)
/Applications/MAMP/Library/bin/mysql80/bin/mysql_config_editor set \
	--login-path=mamp \
	--socket=/Applications/MAMP/tmp/mysql/mysql.sock \
	--user=root --password
# (enter your password when prompted)

# Then run without -p/-proot
/Applications/MAMP/Library/bin/mysql80/bin/mysql --login-path=mamp \
	< database/schema.sql

# If inside database folder already, just run
/Applications/MAMP/Library/bin/mysql80/bin/mysql --login-path=mamp < schema.sql
```

seed.sql is a script that fills database with initial sample data. To run (assuming you already set up a one time password and you are inside the database folder)
```zsh
/Applications/MAMP/Library/bin/mysql80/bin/mysql --login-path=mamp < seed.sql
```
Secrets note: `.env` is git-ignored in this repo; keep credentials there or in your local login-path, not in documentation or committed files.
