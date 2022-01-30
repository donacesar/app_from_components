<?php


namespace App\Controllers;
use App\QueryBuilder;
use Delight\Auth\Auth;
use League\Plates\Engine;
use \Tamtamchik\SimpleFlash\Flash;

class Registration
{
    private $templates;
    private $auth;
    private $flash;
    private $qb;

    public function __construct(Engine $templates, Auth $auth, Flash $flash, QueryBuilder $qb ) {
        $this->templates = $templates;
        $this->auth = $auth;
        $this->flash = $flash;
        $this->qb = $qb;
    }

    public function page_register() {
        echo $this->templates->render('register');
    }

    public function register() {
        try {
            $userId = $this->auth->register($_POST['email'], $_POST['password']);
            $this->flash->message("You have successfully registered. " . $userId);

            //Создание в остальных таблицах полей с начальными данными для пользователя с id:"$userId"
            $this->qb->insert('users4', ['id' => $userId, 'email' => $_POST['email']]);
            //$this->qb->insert(['id' => $userId], 'users_links');

            //Присвоение роли новому зарегистрированному пользователю с id: "$userId"
            //$this->auth->admin()->addRoleForUserById($userId, \Delight\Auth\Role::ADMIN);

            header('Location: /page_login');die();
        }
        catch (\Delight\Auth\InvalidEmailException $e) {
            $this->flash->message('Invalid email address', 'error');
        }
        catch (\Delight\Auth\InvalidPasswordException $e) {
            $this->flash->message('Invalid password', 'error');
        }
        catch (\Delight\Auth\UserAlreadyExistsException $e) {
            $this->flash->message('User already exist', 'error');
        }
        catch (\Delight\Auth\TooManyRequestsException $e) {
            $this->flash->message('Too many requests', 'error');
        }
        echo $this->templates->render('register');
    }

}