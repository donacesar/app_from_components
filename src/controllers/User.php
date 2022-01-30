<?php
namespace App\Controllers;

use App\QueryBuilder;
use Delight\Auth\Auth;
use Exception;
use League\Plates\Engine;
use Tamtamchik\SimpleFlash\Flash;

class User
{
    private $templates;
    private $auth;
    private $qb;
    private $flash;

    public function __construct(QueryBuilder $qb, Engine $templates, Auth $auth, Flash $flash)
    {
        $this->qb = $qb;
        $this->templates = $templates;
        $this->auth = $auth;
        $this->flash = $flash;
    }

    public function index() {

      if(!$this->auth->isLoggedIn()) {
          header('Location: /page_login');
          exit;
      }

        $users = $this->qb->getAll('users4');

        echo $this->templates->render('users', ['auth' => $this->auth, 'users' => $users]);
    }
    public function page_login() {
        echo $this->templates->render('login');
    }
    public function login() {
        try {
            $this->auth->login($_POST['email'], $_POST['password']);

            header('Location: /');die();
        }
        catch (\Delight\Auth\InvalidEmailException $e) {
            $this->flash->message('Wrong email address', 'error');
        }
        catch (\Delight\Auth\InvalidPasswordException $e) {
            $this->flash->message('Wrong password', 'error');
        }
        catch (\Delight\Auth\EmailNotVerifiedException $e) {
            $this->flash->message('Email not verified', 'error');
        }
        catch (\Delight\Auth\TooManyRequestsException $e) {
            $this->flash->message('Too many requests', 'error');
        }
        echo $this->templates->render('login');
    }
    public function logout() {
        $this->auth->logOut();
        header('Location: /page_login');die();
    }
    public function page_create() {
        if(!$this->auth->isLoggedIn()) {
            header('Location: /page_login');
            exit;
        }
        echo $this->templates->render('create', ['auth' => $this->auth]);
    }
    public function create() {
        if(!$this->auth->isLoggedIn()) {
            header('Location: /page_login');
            exit;
        }
        try {
            $userId = $this->auth->register($_POST['email'], $_POST['password']);
            $this->flash->message("New user successfully created. " . $userId);

            //Сохраняем полученную картинку и создаем уникальное имя
            $filename = $_FILES['avatar']['name'];
            $extension = pathinfo($filename)['extension'];
            $filename = uniqid() . "." . $extension;
            $avatar = 'img/demo/avatars/' . $filename;
            move_uploaded_file($_FILES['avatar']['tmp_name'], $avatar);

            //Запись в таблицу с информацией пользователей, используя полученный $userId
            $this->qb->insert('users4', [
                'id' => $userId,
                'email' => $_POST['email'],
                'name' => $_POST['name'],
                'workplace' => $_POST['workplace'],
                'phone' => $_POST['phone'],
                'address' => $_POST['address'],
                'status' => $_POST['status'],
                'avatar' => $avatar,
                'vk' => $_POST['vk'],
                'telegram' => $_POST['telegram'],
                'instagram' => $_POST['instagram']
                 ]);


            header('Location: /');die();
        }
        catch (\Delight\Auth\InvalidEmailException $e) {
            $this->flash->message('Invalid email address');
        }
        catch (\Delight\Auth\InvalidPasswordException $e) {
            $this->flash->message('Invalid password');
        }
        catch (\Delight\Auth\UserAlreadyExistsException $e) {
        }
        catch (\Delight\Auth\TooManyRequestsException $e) {
            $this->flash->message('Too many requests');
        }
        echo $this->templates->render('create', ['auth' => $this->auth]);
    }
    public function page_edit($vars) {
        if(!$this->auth->isLoggedIn()) {
            header('Location: /page_login');
            exit;
        }
        $id = $vars['id'];
        if (!($this->auth->hasRole(\Delight\Auth\Role::ADMIN) or $id == $this->auth->getUserId())) {
            $this->flash->message('You can edit your profile only', 'error');
            header('Location: /');
        }
            $user = $this->qb->getOne('users4', $id);
        echo $this->templates->render('edit', ['auth' => $this->auth, 'user' => $user]);
    }
    public function edit(){
        if(!$this->auth->isLoggedIn()) {
            header('Location: /page_login');
            exit;
        }
        $this->qb->update('users4', $_POST['id'], $_POST);
        $this->flash->message('Пользователь успешно обновлен', 'success');
        header('Location: /user/' . $_POST['id']);die;
    }
    public function page_user($vars) {
        if(!$this->auth->isLoggedIn()) {
            header('Location: /page_login');
            exit;
        }
        $id = $vars['id'];
        if (!($this->auth->hasRole(\Delight\Auth\Role::ADMIN) or $id == $this->auth->getUserId())) {
            $this->flash->message('You can see your profile only', 'error');
            header('Location: /');
        }
        $user = $this->qb->getOne('users4', $id);
        echo $this->templates->render('user', ['auth' => $this->auth, 'user' => $user]);

    }
    public function page_security($vars) {
        if(!$this->auth->isLoggedIn()) {
            header('Location: /page_login');
            exit;
        }
        $id = $vars['id'];
        if (!($this->auth->hasRole(\Delight\Auth\Role::ADMIN) or $id == $this->auth->getUserId())) {
            $this->flash->message('You can edit your profile only', 'error');
            header('Location: /');
        }
        $user = $this->qb->getOne('users', $id);
        echo $this->templates->render('security', ['auth' => $this->auth, 'user' => $user]);

    }
    public function security() {
        $id = $_POST['id'];
        if(!$this->auth->isLoggedIn()) {
            header('Location: /page_login');
            exit;
        }
        if (!($this->auth->hasRole(\Delight\Auth\Role::ADMIN) or $id == $this->auth->getUserId())) {
            $this->flash->message('You can change your profile only', 'error');
            header('Location: /');
        }
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $user = $this->qb->getOne('users', $id);
        if ($user['email'] !== $_POST['email']) {
           if($this->qb->checkEmailExist('users', $_POST['email'])) {
               $this->flash->message('This email is already used', 'error');
               echo $this->templates->render('security', ['auth' => $this->auth, 'user' => $user]);
               exit;
           }
        }
        $this->qb->update('users', $id, [
            'email' => $_POST['email'],
            'id' => $id,
            'password' => $password
        ]);
        $this->flash->message('Credentials successfully updated.', 'success');
        header('Location: /user/' . $id);
    }
    public function page_status($vars) {
        if(!$this->auth->isLoggedIn()) {
            header('Location: /page_login');
            exit;
        }
        $id = $vars['id'];
        if (!($this->auth->hasRole(\Delight\Auth\Role::ADMIN) or $id == $this->auth->getUserId())) {
            $this->flash->message('You can edit your profile only', 'error');
            header('Location: /');
        }

        $user = $this->qb->getOne('users4', $id);
        echo $this->templates->render('status', ['auth' => $this->auth, 'user' => $user]);

    }
    public function status(){
        if(!$this->auth->isLoggedIn()) {
            header('Location: /page_login');
            exit;
        }
        $id = $_POST['id'];
        if (!($this->auth->hasRole(\Delight\Auth\Role::ADMIN) or $id == $this->auth->getUserId())) {
            $this->flash->message('You can edit your profile only', 'error');
            header('Location: /');
        }
        $this->qb->update('users4', $id, ['status' => $_POST['status']]);
        $this->flash->message('Status updated', 'success');
        header('Location: /user/' . $id);
    }
    public function page_media($vars) {
        if(!$this->auth->isLoggedIn()) {
            header('Location: /page_login');
            exit;
        }
        $id = $vars['id'];
        if (!($this->auth->hasRole(\Delight\Auth\Role::ADMIN) or $id == $this->auth->getUserId())) {
            $this->flash->message('You can edit your profile only', 'error');
            header('Location: /');
        }
        $user = $this->qb->getOne('users4', $id);
        echo $this->templates->render('media', ['auth' => $this->auth, 'user' => $user]);
    }
    public function media_handler(){
        if(!$this->auth->isLoggedIn()) {
            header('Location: /page_login');
            exit;
        }
        $id = $_POST['id'];
        if (!($this->auth->hasRole(\Delight\Auth\Role::ADMIN) or $id == $this->auth->getUserId())) {
            $this->flash->message('You can edit your profile only', 'error');
            header('Location: /');
        }
        $user = $this->qb->getOne('users4', $id);
        if ($user['avatar'] !== '') {
            unlink($user['avatar']);
        }

        //Сохраняем полученную картинку и создаем уникальное имя
        $filename = $_FILES['avatar']['name'];
        $extension = pathinfo($filename)['extension'];
        $filename = uniqid() . "." . $extension;
        $avatar = 'img/demo/avatars/' . $filename;
        move_uploaded_file($_FILES['avatar']['tmp_name'], $avatar);

        //Перезаписываем в базу
        $this->qb->update('users4', $id, ['avatar' => $avatar]);
        $this->flash->message('Avatar updated', 'success');
        header('Location: /user/' . $id);
    }
    public function delete($vars) {
        if(!$this->auth->isLoggedIn()) {
            header('Location: /page_login');
            exit;
        }
        $id = $vars['id'];
        if (!($this->auth->hasRole(\Delight\Auth\Role::ADMIN) or $id == $this->auth->getUserId())) {
            $this->flash->message('You can delete your profile only', 'error');
            header('Location: /');
        }
        $this->qb->delete('users4', $id);
        $this->qb->delete('users', $id);
        $currentId = $this->auth->getUserId();
        if($currentId == $id) {
            $this->logout();
        }
    }


}