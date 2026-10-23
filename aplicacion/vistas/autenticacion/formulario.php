<?php /** Formulario compartido: datos escapados, nombres propios en español y CSRF en todo POST. */ $registro=$modo==='registrarse'; ?>
<h1 class="visually-hidden"><?= $registro?'Crear cuenta':'Iniciar sesión' ?></h1>
<?php if(isset($error)):?><div class="alert alert-danger" role="alert"><?= escapar($error) ?></div><?php endif;?>
<form action="<?= $registro?'/registrarse':'/ingresar' ?>" method="post" class="formulario-autenticacion">
<?= campoCsrf() ?>
<?php /* El registro añade usuario, correo y confirmación; el acceso solicita identidad y contraseña. */ ?>
<?php if($registro):?>
<label class="visually-hidden" for="nombre_usuario">Nombre de usuario</label><input class="form-control" id="nombre_usuario" name="nombre_usuario" required pattern="[a-zA-Z0-9_]{3,40}" minlength="3" maxlength="40" autocomplete="username" placeholder="Nombre de usuario" title="Entre 3 y 40 letras, números o guiones bajos" value="<?= escapar($_POST['nombre_usuario']??'') ?>">
<label class="visually-hidden" for="correo">Correo electrónico</label><input class="form-control" id="correo" type="email" name="correo" required maxlength="254" autocomplete="email" placeholder="Correo electrónico" value="<?= escapar($_POST['correo']??'') ?>">
<?php else:?>
<label class="visually-hidden" for="acceso">Usuario o correo electrónico</label><input class="form-control" id="acceso" name="acceso" required maxlength="254" autocomplete="username" placeholder="Usuario o correo electrónico" value="<?= escapar($_POST['acceso']??'') ?>">
<?php endif;?>
<label class="visually-hidden" for="contrasena">Contraseña</label><div class="campo-contrasena"><input class="form-control" id="contrasena" type="password" name="contrasena" required <?= $registro?'minlength="8" maxlength="72"':'' ?> autocomplete="<?= $registro?'new-password':'current-password' ?>" placeholder="<?= $registro?'Contraseña (mínimo 8 caracteres)':'Contraseña' ?>"><button type="button" class="alternar-contrasena" aria-label="Mostrar contraseña" aria-controls="contrasena">Ver</button></div>
<?php /* El registro añade usuario, correo y confirmación; el acceso solicita identidad y contraseña. */ ?>
<?php if($registro):?><label class="visually-hidden" for="confirmacion_contrasena">Confirmar contraseña</label><input class="form-control" id="confirmacion_contrasena" type="password" name="confirmacion_contrasena" required minlength="8" maxlength="72" autocomplete="new-password" placeholder="Confirmar contraseña"><?php endif;?>
<button type="submit" class="btn btn-primary w-100 enviar-autenticacion"><?= $registro?'Crear cuenta':'Iniciar sesión' ?></button>
</form>
<p class="alternativa-autenticacion"><a href="<?= $registro?'/ingresar':'/registrarse' ?>"><?= $registro?'Iniciar sesión':'Crear cuenta' ?></a></p>
