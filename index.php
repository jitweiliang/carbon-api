<?php declare(strict_types=1);

    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    // ---       Exit early so the page isn't fully loaded for options requests       --- //
    // --- **** this is to skip the cors preflight check from client **** --- //
    if (strtolower($_SERVER['REQUEST_METHOD']) == 'options') {
        exit();
    }

    $requestVerb = isset($_SERVER["REQUEST_METHOD"]) ? $_SERVER["REQUEST_METHOD"] : null;   // - check http verb (GET/PUT/POST/DELETE)
    $requestURL = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : null;          // - request url

    $controller = null;

    if($requestVerb && $requestURL) {
        
        if(preg_match('/\/api\/users/', $requestURL)) {
            require "./controllers/UserController.php";
            $controller = new UserController();
        }
        else if(preg_match('/\/api\/bulletins/', $requestURL)) {
            // require "./controllers/BulletinController.php";
            // $controller = new BulletinController();
        }

        if($controller) {
            try {
                $controller->processRequest($requestVerb, $requestURL);
            }
            catch(PDOException $pdoex) {
                header('HTTP/1.1 500 Internal Server Error');
                die(json_encode(array('message' => $pdoex->getMessage())));
            } 
            catch(Exception $ex) {
                header('HTTP/1.1 500 Internal Server Error');
                die(json_encode(array('message' => $ex->getMessage())));
                // echo json_encode($ex);
            }    
        }
        else {
            echo "Invalid Api call; This is a test;";
        }
    }