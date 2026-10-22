<?php /** Formulario compartido: datos escapados, nombres propios en español y CSRF en todo POST. */ $registro=$modo==='registrarse'; ?>
<span class="sobretitulo">BIENVENIDO A TU ESPACIO</span>
<h2><?= $registro?'Crea tu espacio':'Qué bueno verte de nuevo' ?></h2>
<p class="texto-secundario mb-4"><?= $registro?'Comienza a organizar tus archivos en un solo lugar.':'Inicia sesión para acceder a tus archivos.' ?></p>
<?php if(isset($error)):?><div class="alert alert-danger" role="alert"><?= escapar($error) ?></div><?php endif;?>
<form action="<?= $registro?'/registrarse':'/ingresar' ?>" method="post" class="formulario-autenticacion">
<?= campoCsrf() ?>
<?php if($registro):?>
<label for="nombre_usuario">Nombre de usuario</label><input class="form-control" id="nombre_usuario" name="nombre_usuario" required pattern="[a-zA-Z0-9_]{3,40}" minlength="3" maxlength="40" autocomplete="username" value="<?= escapar($_POST['nombre_usuario']??'') ?>"><small class="ayuda-campo">Entre 3 y 40 letras, números o guiones bajos.</small>
<label for="correo">Correo electrónico</label><input class="form-control" id="correo" type="email" name="correo" required maxlength="254" autocomplete="email" value="<?= escapar($_POST['correo']??'') ?>">
<?php else:?>
<label for="acceso">Usuario o correo electrónico</label><input class="form-control" id="acceso" name="acceso" required maxlength="254" autocomplete="username" placeholder="Tu usuario o correo" value="<?= escapar($_POST['acceso']??'') ?>">
<?php endif;?>
<label for="contrasena">Contraseña</label><div class="campo-contrasena"><input class="form-control" id="contrasena" type="password" name="contrasena" required <?= $registro?'minlength="8" maxlength="72"':'' ?> autocomplete="<?= $registro?'new-password':'current-password' ?>" placeholder="<?= $registro?'Al menos 8 caracteres':'Tu contraseña' ?>"><button type="button" class="alternar-contrasena" aria-label="Mostrar contraseña" aria-controls="contrasena">Ver</button></div>
<?php if($registro):?><label for="confirmacion_contrasena">Confirmar contraseña</label><input class="form-control" id="confirmacion_contrasena" type="password" name="confirmacion_contrasena" required minlength="8" maxlength="72" autocomplete="new-password"><?php endif;?>
<button type="submit" class="btn btn-primary w-100 enviar-autenticacion"><?= $registro?'Crear cuenta':'Iniciar sesión' ?><svg class="icono-interfaz" aria-hidden="true"><use href="/recursos/iconos/interfaz.svg#flecha"></use></svg></button>
</form>
<p class="alternativa-autenticacion"><?= $registro?'¿Ya tienes una cuenta?':'¿Aún no tienes una cuenta?' ?><a href="<?= $registro?'/ingresar':'/registrarse' ?>"><?= $registro?'Inicia sesión':'Crea una cuenta' ?></a></p>
<div class="seguridad-autenticacion"><span class="indicador-seguridad"></span>Tus archivos solo están disponibles para ti.</div>
