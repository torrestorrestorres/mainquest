<?php
/** @var string[] $errors */
if (!$errors) {
    return;
}
?>
<div class="flash flash--error" role="alert">
<?php foreach ($errors as $error): ?>
    <p><?= e($error) ?></p>
<?php endforeach; ?>
</div>
