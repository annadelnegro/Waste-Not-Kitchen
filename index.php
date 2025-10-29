<?php /* Simple landing page while wiring up routing/controllers. */ ?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<title>Waste‑Not‑Kitchen</title>
		<style>
			body { font-family: -apple-system, system-ui, Segoe UI, Roboto, Helvetica, Arial, sans-serif; margin: 40px; }
			code { background: #f4f4f4; padding: 2px 6px; border-radius: 4px; }
			.links a { display: inline-block; margin-right: 12px; }
		</style>
	</head>
	<body>
		<h1>Waste‑Not‑Kitchen</h1>
		<p>If you can see this page, your MAMP web server is serving the project correctly.</p>

		<div class="links">
			<a href="/phpMyAdmin/">Open phpMyAdmin</a>
				<a href="/Waste-Not-Kitchen/test-db.php">DB test page</a>
			<a href="/MAMP/">MAMP start page</a>
		</div>

		<hr>
		<p>Next up, add routes/controllers under <code>modules/</code> and point this file to them.</p>
	</body>
	</html>