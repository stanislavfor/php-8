<?php

namespace Geekbrains\Application1\Application;

use Geekbrains\Application1\Domain\Controllers\AbstractController;
use Geekbrains\Application1\Infrastructure\Config;
use Geekbrains\Application1\Infrastructure\Storage;
use Geekbrains\Application1\Application\Auth;

class Logger
{
    private string $logDir;
    private bool $isEnabled;

    public function __construct(string $logDir, bool $isEnabled)
    {
        $this->logDir = $logDir;
        $this->isEnabled = $isEnabled;

        if ($this->isEnabled && !is_dir($this->logDir)) {
            mkdir($this->logDir, 0777, true);
        }
    }

    public function log(string $level, string $message): void {
        if (!$this->isEnabled) {
            return;
        }

        $date = date('Y-m-d H:i:s');
        $filename = $this->logDir . DIRECTORY_SEPARATOR . date('Y-m-d') . '.log';
        $logMessage = "[$date] [$level] $message" . PHP_EOL;
        file_put_contents($filename, $logMessage, FILE_APPEND);
    }
}

class Application {

    private const APP_NAMESPACE = 'Geekbrains\Application1\Domain\Controllers\\';
    private string $controllerName;
    private string $methodName;
    public static Config $config;
    public static Storage $storage;
    public static Auth $auth;
    private Logger $logger;

    public function __construct(){
        Application::$config = new Config();
        Application::$storage = new Storage();
        Application::$auth = new Auth();

        $logDir = __DIR__ . '/../../../log';
        $logEnabled = Application::$config->get('logging.enabled', false);
        $this->logger = new Logger($logDir, $logEnabled);

    }

    // public function run() : string {
    //     session_start();
    //     $routeArray = explode('/', $_SERVER['REQUEST_URI']);

    //     if(isset($routeArray[1]) && $routeArray[1] != '') {
    //         $controllerName = $routeArray[1];
    //     }
    //     else{
    //         $controllerName = "page";
    //     }

    //     $this->controllerName = Application::APP_NAMESPACE . ucfirst($controllerName) . "Controller";

    //     if(class_exists($this->controllerName)){
    //         // пытаемся вызвать метод
    //         if(isset($routeArray[2]) && $routeArray[2] != '') {
    //             $methodName = $routeArray[2];
    //         }
    //         else {
    //             $methodName = "index";
    //         }

    //         $this->methodName = "action" . ucfirst($methodName);

    //         if(method_exists($this->controllerName, $this->methodName)){
    //             $controllerInstance = new $this->controllerName();

    //             if($controllerInstance instanceof AbstractController){
    //                 if($this->checkAccessToMethod($controllerInstance, $this->methodName)){
    //                     return call_user_func_array(
    //                         [$controllerInstance, $this->methodName],
    //                         []
    //                     );
    //                 }
    //                 else{
    //                     return "Нет доступа к методу";
    //                 }
    //             }
    //             else{
    //                 return call_user_func_array(
    //                     [$controllerInstance, $this->methodName],
    //                     []
    //                 );
    //             }
    //         }
    //         else {
    //             return "Метод не существует";
    //         }
    //     }
    //     else{
    //         return "Класс $this->controllerName не существует";
    //     }
    // }

    public function run(): string {
        try {
            session_start();
            $routeArray = explode('/', $_SERVER['REQUEST_URI']);
            $controllerName = isset($routeArray[1]) && $routeArray[1] !== '' ? $routeArray[1] : "page";
            $this->controllerName = Application::APP_NAMESPACE . ucfirst($controllerName) . "Controller";

            if (!class_exists($this->controllerName)) {
                throw new Exception("Класс {$this->controllerName} не существует");
            }

            $methodName = isset($routeArray[2]) && $routeArray[2] !== '' ? $routeArray[2] : "index";
            $this->methodName = "action" . ucfirst($methodName);

            if (!method_exists($this->controllerName, $this->methodName)) {
                throw new Exception("Метод {$this->methodName} не существует");
            }

            $controllerInstance = new $this->controllerName();

            if ($controllerInstance instanceof AbstractController) {
                if (!$this->checkAccessToMethod($controllerInstance, $this->methodName)) {
                    throw new Exception("Нет доступа к методу");
                }
            }

            return call_user_func_array([$controllerInstance, $this->methodName], []);
        } catch (Exception $e) {
            $this->logger->log('ERROR', $e->getMessage());
            return $e->getMessage();
        }
    }


    private function checkAccessToMethod(AbstractController $controllerInstance, string $methodName): bool {
        $userRoles = $controllerInstance->getUserRoles();
        $rules = $controllerInstance->getActionsPermissions($methodName);
        
        $isAllowed = false;
        if(!empty($rules)){
            foreach($rules as $rolePermission){
                if(in_array($rolePermission, $userRoles)){
                    $isAllowed = true;
                    break;
                }
            }
        }

        return $isAllowed;
    }
}