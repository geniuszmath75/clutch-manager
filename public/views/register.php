<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
</head>
<body>
    <h1>Clutch Manager</h1>
    <h2>Register</h2>

    <form action="/auth/register" method="POST" >
        <label>
            Nickname
            <input type="text" name="nickname" required minlength="3" maxlength="100">
        </label>
        <label>
            Email
            <input type="email" name="email" required>
        </label>
        <label>
            Password
            <input type="password" name="password" required minlength="10">
        </label>

        <label>
            Account type
            <select name="system_role_ident" id="system_role_ident" required>
                <option value="">-- select --</option>
                <option value="PLAYER">Player</option>
                <option value="COACH">Coach</option>
            </select>
        </label>

        <label id="team_role_label">
            Team role
            <select name="team_role_ident" id="team_role_ident">
                <option value="">-- select --</option>
                <?php foreach ($teamRoles as $role): ?>
                    <option value="<?= htmlspecialchars($role['ident']) ?>">
                        <?= htmlspecialchars($role['ident']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit">Create account</button>
    </form>

    <p>Already have an account? <a href="/auth/login">Log in</a></p>
</body>
</html>