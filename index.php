<?php declare(strict_types=1);

    // https://developer.okta.com/blog/2019/03/08/simple-rest-api-php
    // https://lornajane.net/posts/2012/building-a-restful-php-server-routing-the-request
    // https://www.techiediaries.com/php-rest-api/

    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    // ---          Exit early so the page isn"t fully loaded for options requests         --- //
    // --- ****     this is to skip the cors preflight check from client ****              --- //
    // https://stackoverflow.com/questions/8719276/cross-origin-request-headerscors-with-php-headers
    // https://www.wpeform.io/blog/handle-cors-preflight-php-wordpress/
    if (strtolower($_SERVER["REQUEST_METHOD"]) == "options") {
        exit();
    }

    // https://larachamp.com/building-a-rest-api-with-php/
    $requestVerb = $_SERVER["REQUEST_METHOD"];   // - check http verb (GET/PUT/POST/DELETE)
    $requestURL  = $_SERVER["REQUEST_URI"];        // - request url



    // -- process request and pass to controllers
    $controller = null;
    if(isset($requestVerb) && isset($requestURL)) {
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
            else if(preg_match("/\/api\/emissions/", $requestURL)) {
                require "./src/controllers/EmissionController.php";
                $controller = new EmissionController();
            }
            else if(preg_match("/\/api\/test/", $requestURL)) {
                require "./src/controllers/TestController.php";
                $controller = new TestController();
            }
            else if(preg_match("/\/api\/resources/", $requestURL)) {
                require "./src/controllers/ResourceController.php";
                $controller = new ResourceController();
            }
           

            // --- process controller requests
            if (isset($controller)) {
                $controller->processRequest($requestVerb, $requestURL);
            }
            else throw new Exception("no such controllers");
        }
        catch(PDOException $pdoex) {
            http_response_code(500);
            header("HTTP/1.1 500 Internal Server Error");
            echo json_encode([`message` => `{$pdoex->getMessage()}`]);
            die(0);
        }
        catch(Exception $ex) {
            header("HTTP/1.1 500 Internal Server Error");
            // die(json_encode(array("message"=>"{$ex->getMessage()}")));
            die(json_encode(array("message" => "{$ex->getMessage()}")));    // ["message"=>]
        }        
    }
    