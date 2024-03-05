<?php declare(strict_types=1);

    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    // ---       Exit early so the page isn"t fully loaded for options requests       --- //
    // --- **** this is to skip the cors preflight check from client **** --- //
    if (strtolower($_SERVER["REQUEST_METHOD"]) == "options") {
        exit();
    }

    $requestVerb = isset($_SERVER["REQUEST_METHOD"]) ? $_SERVER["REQUEST_METHOD"] : null;   // - check http verb (GET/PUT/POST/DELETE)
    $requestURL = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : null;          // - request url

    $controller = null;

    if($requestVerb && $requestURL) {
        try {
            if(preg_match("/\/api\/users/", $requestURL)) {
                require "./src/controllers/UserController.php";
                $controller = new UserController();
            }
            else if(preg_match("/\/api\/bulletins/", $requestURL)) {
                require "./src/controllers/BulletinController.php";
                $controller = new BulletinController();
            }
            else if(preg_match("/\/api\/events/", $requestURL)) {
                require "./src/controllers/EventController.php";
                $controller = new EventController();
            }   
            else if(preg_match("/\/api\/reminders/", $requestURL)) {
                require "./src/controllers/ReminderController.php";
                $controller = new ReminderController();
            }
            else if(preg_match("/\/api\/test/", $requestURL)) {
                require "./src/controllers/TestController.php";
                $controller = new TestController();
            }

            if ($controller) {
                $controller->processRequest($requestVerb, $requestURL);
            }
            else throw new Exception("no such controllers");
        }
        catch(PDOException $pdoex) {
            http_response_code(500);
            echo json_encode([`message` => `{$pdoex->getMessage()}`]);
            die(0);
        }
        catch(Exception $ex) {
            header("HTTP/1.1 500 Internal Server Error");
            // die(json_encode(array("message"=>"{$ex->getMessage()}")));
            die(json_encode(array("message" => "{$ex->getMessage()}")));
        }
        
    }