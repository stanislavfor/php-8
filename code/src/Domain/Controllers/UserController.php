<?php

namespace Geekbrains\Application1\Domain\Controllers;
namespace Geekbrains\Application1\Domain\Models;

use Geekbrains\Application1\Application\Application;
use Geekbrains\Application1\Application\Render;
use Geekbrains\Application1\Application\Auth;
use Geekbrains\Application1\Domain\Models\User;


class UserController extends AbstractController {
    protected array $actionsPermissions = [
        'actionHash' => ['admin', 'some'],
        'actionSave' => ['admin']
    ];

    public function actionIndex(): string {
        $users = User::getAllUsersFromStorage();        
        $render = new Render();

        if(!$users){
            return $render->renderPage(
                'user-empty.tpl', 
                [
                    'title' => 'Список пользователей в хранилище',
                    'message' => "Список пуст или не найден"
                ]);
        }
        else{
            return $render->renderPage(
                'user-index.tpl', 
                [
                    'title' => 'Список пользователей в хранилище',
                    'users' => $users
                ]);
        }
    }

    public function actionSave(): string {
        if(User::validateRequestData()) {
            $user = new User();
            $user->setParamsFromRequestData();
            $user->saveToStorage();
            $render = new Render();

            return $render->renderPage(
                'user-created.tpl', 
                [
                    'title' => 'Пользователь создан',
                    'message' => "Создан пользователь " . $user->getUserName() . " " . $user->getUserLastName()
                ]);
        }
        else {
            throw new \Exception("Переданные данные некорректны");
        }
    }

    public function actionEdit(): string {
        $render = new Render();
        
        return $render->renderPageWithForm(
                'user-form.tpl', 
                [
                    'title' => 'Форма создания пользователя'
                ]);
    }

    public function actionAuth(): string {
        $render = new Render();
        
        return $render->renderPageWithForm(
                'user-auth.tpl', 
                [
                    'title' => 'Форма логина'
                ]);
    }

    public function actionHash(): string {
        return Auth::getPasswordHash($_GET['pass_string']);
    }

    public function actionLogin(): string {
        $result = false;
        if (isset($_POST['login']) && isset($_POST['password'])) {
            $result = Application::$auth->proceedAuth($_POST['login'], $_POST['password']);

            if (isset($_POST['remember_me']) && $_POST['remember_me'] === 'on') {
                $token = bin2hex(random_bytes(32));
                setcookie('auth_token', $token, time() + (86400 * 30), "/");
                User::storeAuthToken($_POST['login'], $token);
            }
        }

        if (!$result) {
            $render = new Render();
            return $render->renderPageWithForm(
                'user-auth.tpl',
                [
                    'title' => 'Форма логина',
                    'auth-success' => false,
                    'auth-error' => 'Неверные логин или пароль'
                ]
            );
        } else {
            header('Location: /');
            return "";
        }
    }

    public function actionLogout(): void {
        if (isset($_COOKIE['auth_token'])) {
            User::deactivateAuthToken($_COOKIE['auth_token']);
            setcookie('auth_token', '', time() - 3600, "/");
        }
        session_destroy();
        header('Location: /');
    }

}



class User {
    private string $username;
    private string $lastname;
    private static array $storage = [];
    private static array $authTokens = [];

    public static function validateRequestData(): bool {
        $dataToValidate = $_POST;
        $htmlTagPattern = "/<[^>]*>/";

        foreach ($dataToValidate as $key => $value) {
            if (is_string($value) && preg_match($htmlTagPattern, $value)) {
                return false;
            }
        }

        return true;
    }

    public function setParamsFromRequestData(): void {
        $this->username = $_POST['username'] ?? '';
        $this->lastname = $_POST['lastname'] ?? '';
    }

    public function saveToStorage(): void {
        self::$storage[] = [
            'username' => $this->username,
            'lastname' => $this->lastname
        ];
    }

    public static function getAllUsersFromStorage(): array {
        return self::$storage;
    }

    public static function storeAuthToken(string $username, string $token): void {
        self::$authTokens[$username] = $token;
    }

    public static function deactivateAuthToken(string $token): void {
        $user = array_search($token, self::$authTokens, true);
        if ($user !== false) {
            unset(self::$authTokens[$user]);
        }
    }

    public function getUserName(): string {
        return $this->username;
    }

    public function getUserLastName(): string {
        return $this->lastname;
    }
}
