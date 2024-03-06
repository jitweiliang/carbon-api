<?php
    require "IController.php";
    require "./src/utilities/FirebaseSDK.php";

    class TestController implements IController {
        private $sdk;

        public function __construct() {
            $this->sdk = new FirebaseSDK();
        }

        public function processRequest(string $verb, ?string $uri): void {
            switch ($verb) {
                case "GET":
                    switch(true) {
                        case preg_match('/\/api\/test\/test$/', $uri):
                            echo json_encode('test okok');
                            break;

                        case preg_match('/\/api\/test\/firestore$/', $uri):
                            $data = $this->sdk->firestoreGet("bulletins");
                            echo json_encode($data);

                            break;

                        case preg_match('/\/api\/test\/storage\/[^\/]+/', $uri):
                    }
                    break;

                case "POST":
                    switch(true) {
                        case preg_match('/\/api\/test\/firestore$/', $uri):
                            $model = (array) json_decode(file_get_contents("php://input"), true);

                            $this->sdk->firestoreAdd("bulletins", $model["postedBy"]);
                            break;

                        case preg_match('/\/api\/test\/images$/', $uri):
                            $data = $this->sdk->firestoreGet("bulletins");
                            echo json_encode($data);

                            break;

                        case preg_match('/\/api\/test\/messaging\/token\/[^\/]+/', $uri):

                            break;
                    }
                    break;

                default:
                    http_response_code(405);
                    header("Allow: GET, PATCH, DELETE");
                    break;
            }

        }
    }