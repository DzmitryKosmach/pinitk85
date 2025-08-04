<?php
if(!defined('ANTIBOT')) die('access denied');
$form_user = isset($_GET['form_user']) ? htmlspecialchars(trim(strip_tags($_GET['form_user'])), ENT_QUOTES | ENT_HTML5, 'UTF-8') : ''; // email
$form_pass = isset($_GET['form_pass']) ? htmlspecialchars(trim(strip_tags($_GET['form_pass'])), ENT_QUOTES | ENT_HTML5, 'UTF-8') : ''; // pass
?>
<!doctype html>
<html lang="<?php echo abTranslate('en'); ?>">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo abTranslate('AntiBot Admin Panel'); ?></title>
<link rel="stylesheet" href="<?php echo $ab_config['webdir']; ?>static/bootstrap4.min.css">
<meta name="theme-color" content="#563d7c">
    <style>
html,
body {
  height: 100%;
}

body {
  display: -ms-flexbox;
  display: flex;
  -ms-flex-align: center;
  align-items: center;
  padding-top: 40px;
  padding-bottom: 40px;
  background-color: #f5f5f5;
}

.form-signin {
  width: 100%;
  max-width: 330px;
  padding: 15px;
  margin: auto;
}

.form-signin .form-control {
  position: relative;
  box-sizing: border-box;
  height: auto;
  padding: 10px;
  font-size: 16px;
  margin-bottom: 10px;
}
.form-signin .form-control:focus {
  z-index: 2;
}

    </style>


  </head>
  <body class="text-center">
    
<form class="form-signin" action="" method="post">
<span class="text-danger"><?php echo $error_msg; ?></span>
  <h1 class="h3 mb-3 font-weight-normal"><?php echo abTranslate('AntiBot Admin Panel'); ?></h1>
  <label for="inputEmail" class="sr-only"><?php echo abTranslate('Email (Login)'); ?></label>
  <input value="<?php echo $form_user; ?>" name="auth_user" type="email" id="inputEmail" class="form-control" placeholder="<?php echo abTranslate('Email (Login)'); ?>" required autofocus>
  <label for="inputPassword" class="sr-only"><?php echo abTranslate('Password'); ?></label>
  <input value="<?php echo $form_pass; ?>" name="auth_pass" type="password" id="inputPassword" class="form-control" placeholder="<?php echo abTranslate('Password'); ?>" required>
  <?php if ($ab_config['secondpass'] != '') { ?>
  <label for="secondpass" class="sr-only"><?php echo abTranslate('Second Password'); ?></label>
  <input name="auth_second_pass" type="text" autocomplete="off" id="secondpass" class="form-control" placeholder="<?php echo abTranslate('Second Password'); ?>">
  <?php } ?>
  <button class="btn btn-lg btn-primary btn-block" type="submit" name="auth_post"><?php echo abTranslate('Log in'); ?></button>
</form>


    
  </body>
</html>
