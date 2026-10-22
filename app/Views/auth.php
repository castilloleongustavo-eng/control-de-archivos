<?php $register = $mode === 'register'; ?>
<span class="eyebrow">BIENVENIDO A ADMOSA</span>
<h2><?= $register ? 'Crea tu espacio' : 'Qué bueno verte de nuevo' ?></h2>
<p class="muted mb-4"><?= $register ? 'Comienza a organizar tus archivos en un solo lugar.' : 'Inicia sesión para acceder a tus archivos.' ?></p>
<?php if (isset($error)): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
<form action="/<?= $register ? 'register' : 'login' ?>" method="post" class="auth-form">
<?= csrf() ?>
<?php if ($register): ?>
<label for="username">Nombre de usuario</label><input class="form-control" id="username" name="username" required pattern="[a-zA-Z0-9_]{3,40}" minlength="3" maxlength="40" autocomplete="username" value="<?= e($_POST['username'] ?? '') ?>"><small class="field-hint">Entre 3 y 40 letras, números o guiones bajos.</small>
<label for="email">Correo electrónico</label><input class="form-control" id="email" type="email" name="email" required maxlength="254" autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>">
<?php else: ?>
<label for="login">Usuario o correo electrónico</label><input class="form-control" id="login" name="login" required maxlength="254" autocomplete="username" placeholder="Tu usuario o correo" value="<?= e($_POST['login'] ?? '') ?>">
<?php endif; ?>
<label for="password">Contraseña</label><div class="password-field"><input class="form-control" id="password" type="password" name="password" required <?= $register ? 'minlength="8" maxlength="72"' : '' ?> autocomplete="<?= $register ? 'new-password' : 'current-password' ?>" placeholder="<?= $register ? 'Al menos 8 caracteres' : 'Tu contraseña' ?>"><button type="button" class="password-toggle" aria-label="Mostrar contraseña" aria-controls="password">Ver</button></div>
<?php if ($register): ?><label for="password_confirmation">Confirmar contraseña</label><input class="form-control" id="password_confirmation" type="password" name="password_confirmation" required minlength="8" maxlength="72" autocomplete="new-password"><?php endif; ?>
<button type="submit" class="btn btn-primary w-100 auth-submit"><?= $register ? 'Crear cuenta' : 'Iniciar sesión' ?> <span aria-hidden="true">→</span></button>
</form>
<p class="auth-switch"><?= $register ? '¿Ya tienes una cuenta?' : '¿Aún no tienes una cuenta?' ?> <a href="/<?= $register ? 'login' : 'register' ?>"><?= $register ? 'Inicia sesión' : 'Crea una cuenta' ?></a></p>
<div class="auth-security"><span class="secure-dot"></span> Tus archivos solo están disponibles para ti.</div>
