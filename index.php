<?php

include('basic_auth.php');

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    try {
        $stmt = $db->prepare("SELECT id, name, email, year, gender, limbs, biography FROM users");
        $stmt->execute();
        $values = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        print('Error : ' . $e->getMessage());
        exit();
    }
    $messages = array();

    $errors = array();
    $errors['name'] = !empty($_COOKIE['name_error']);
    $errors['email'] = !empty($_COOKIE['email_error']);
    $errors['year'] = !empty($_COOKIE['year_error']);
    $errors['gender'] = !empty($_COOKIE['gender_error']);
    $errors['limbs'] = !empty($_COOKIE['limbs_error']);
    $errors['abilities'] = !empty($_COOKIE['abilities_error']);
    $errors['bio'] = !empty($_COOKIE['bio_error']);
  
    if ($errors['name']) {
        setcookie('name_error', '', 100000);
        $messages[] = '<div class="error">Заполните name.</div>';
    }
    if ($errors['email']) {
      setcookie('email_error', '', 100000);
      $messages[] = '<div class="error">Заполните email.</div>';
    }
    if ($errors['year']) {
      setcookie('year_error', '', 100000);
      $messages[] = '<div class="error">Заполните year.</div>';
    }
    if ($errors['gender']) {
      setcookie('gender_error', '', 100000);
      $messages[] = '<div class="error">Заполните gender.</div>';
    }
    if ($errors['limbs']) {
      setcookie('limbs_error', '', 100000);
      $messages[] = '<div class="error">Заполните limbs.</div>';
    }
    if ($errors['abilities']) {
      setcookie('abilities_error', '', 100000);
      $messages[] = '<div class="error">Заполните abilities.</div>';
    }  
    if ($errors['bio']) {
      setcookie('bio_error', '', 100000);
      $messages[] = '<div class="error">Заполните bio.</div>';
    }
    $_SESSION['token'] = bin2hex(random_bytes(32));
    $_SESSION['login'] = $validUser;
    include('dbshow.php');
    exit();
} else {
  if (!empty($_POST['token']) && hash_equals($_POST['token'], $_SESSION['token'])) {
    foreach ($_POST as $key => $value) {
        if (preg_match('/^clear(\d+)$/', $key, $matches)) {
            $p_id = $matches[1];
            setcookie('clear', $p_id, time() + 24 * 60 * 60);
            $stmt = $db->prepare("DELETE FROM user_ab WHERE user_id = ?");
            $stmt->execute([$p_id]);
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$p_id]);
            $stmt = $db->prepare("DELETE FROM logpas WHERE user_id = ?");
            $stmt->execute([$p_id]);
        }
        if (preg_match('/^save(\d+)$/', $key, $matches)) {
            $p_id = $matches[1];
            $dates = array();
            $dates['name'] = $_POST['name' . $p_id];
            $dates['email'] = $_POST['email' . $p_id];
            $dates['year'] = $_POST['year' . $p_id];
            $dates['gender'] = $_POST['gender' . $p_id];
            $dates['limbs'] = $_POST['limbs' . $p_id];
            $abilities = $_POST['abilities' . $p_id];
            $dates['bio'] = $_POST['bio' . $p_id];
        
            $name = $dates['name'];
            $email = $dates['email'];
            $year = $dates['year'];
            $gender = $dates['gender'];
            $limbs = $dates['limbs'];
            $bio = $dates['bio'];
        
            $errors = FALSE;
            if (empty($name)) {
              $errors = TRUE;
              setcookie('name_error', '1', time() + 24 * 60 * 60);
              setcookie('name_value', $name, time() + 30 * 24 * 60 * 60);
            }
            if (empty($email) || !preg_match('/^((([0-9A-Za-z]{1}[-0-9A-z\.]{1,}[0-9A-Za-z]{1})|([0-9А-Яа-я]{1}[-0-9А-я\.]{1,}[0-9А-Яа-я]{1}))@([-A-Za-z]{1,}\.){1,2}[-A-Za-z]{2,})$/u', $email)) {
              $errors = TRUE;
              setcookie('email_value', $email, time() + 30 * 24 * 60 * 60);
              setcookie('email_error', '1', time() + 24 * 60 * 60);
            }
            if (empty($year) || !is_numeric($year) || (int)$year <= 1922 || (int)$year >= 2022) {
              $errors = TRUE;
              setcookie('year_error', '1', time() + 24 * 60 * 60);
              setcookie('year_value', $year, time() + 30 * 24 * 60 * 60);
            }
            if ($gender !== 'm' && $gender !== 'w'){
              $errors = TRUE;
              setcookie('gender_error', '1', time() + 24 * 60 * 60);
            }
            if ($limbs !== '2' && $limbs !== '3' && $limbs !== '4') {  
              $errors = TRUE;
              setcookie('limbs_error', '1', time() + 24 * 60 * 60);
            }
            if (empty($abilities) || !is_array($abilities)) {
              $errors = TRUE;
              setcookie('abilities_error', '1', time() + 24 * 60 * 60);
            }
            if (empty($bio) || strlen($bio) > 128) {
              $errors = TRUE;
              setcookie('bio_error', '1', time() + 24 * 60 * 60);
              setcookie('bio_value', $bio, time() + 30 * 24 * 60 * 60);
            }
          
            if ($errors) {
              header('Location: index.php');
              exit();
            }
            else {
              setcookie('name_error', '', 100000);
              setcookie('email_error', '', 100000);
              setcookie('year_error', '', 100000);
              setcookie('gender_error', '', 100000);
              setcookie('limbs_error', '', 100000);
              setcookie('bio_error', '', 100000);
            }

            $stmt = $db->prepare("SELECT name, email, year, gender, limbs, biography FROM users WHERE id = ?");
            $stmt->execute([$p_id]);
            $old_dates = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $db->prepare("SELECT ability_id FROM user_ab WHERE user_id = ?");
            $stmt->execute([$p_id]);
            $old_abilities = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if (array_diff($dates, $old_dates[0])) {
              $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, year = ?, gender = ?, limbs = ?, biography = ?
                WHERE id = ?");
              $stmt->execute([$name, $email, $year, $gender, $limbs, $bio, $p_id]);
            }
            if (array_diff($abilities, $old_abilities) || count($abilities) != count($old_abilities)) {
                $stmt = $db->prepare("DELETE FROM user_ab WHERE user_id = ?");
                $stmt->execute([$p_id]);
                $stmt = $db->prepare("INSERT INTO user_ab (user_id, ability_id) VALUES (?, ?)");
                foreach ($abilities as $sup_id) {
                    $stmt->execute([$p_id, $sup_id]);
                }
            }
        }
    }
    header('Location: index.php');
  } else {
    die('Ошибка CSRF: недопустимый токен');
  }
}