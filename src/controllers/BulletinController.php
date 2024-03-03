<?php

    require "./utilities/Database.php";
    require "InterfController.php";

    class BulletinController implements IController {
        private $pdo;

        public function __construct() {
            $db = new Database();
            $this->pdo = $db->getPDOObject();
        }

        public function processRequest(string $verb, $uri) {
            switch ($verb) {
                case "GET":
                    switch(true) {
                        // -- get all users
                        case preg_match('/\/api\/bulletins$/', $uri):
                            $stmt = "select * FROM carbon_bulletins";
                            $sql = $this->pdo->prepare($stmt);
                            $sql->execute();
                            
                            $data = $sql->fetchAll(PDO::FETCH_OBJ);
                            echo json_encode($data);    

                            break;
                        // -- get single user by id
                        case preg_match('/\/api\/bulletins\/id\/[1-9]/', $uri):
                            // this is the last parameter in the url
                            $param = basename($uri);    

                            $stmt = "SELECT * FROM carbon_bulletinss WHERE id = :id";
                            $sql = $this->pdo->prepare($stmt);
                            $sql->bindValue(":id", $param, PDO::PARAM_INT);
                            $sql->execute();
                            
                            $data = $sql->fetch(PDO::FETCH_ASSOC);
                            echo json_encode($data);    

                            break;
                        // --- if requests do not match any api
                        default:
                            throw new Exception("Invalid get request !!!");
                        }

                case "POST":
                    $model = (array) json_decode(file_get_contents("php://input"), true);
                    $stmt = "INSERT INTO carbon_bulletins (user_id, message, img_url, post_date)
                                VALUES (:userId, :message, :imgUrl, :postDate)";

                    $sql = $this->pdo->prepare($stmt);
                    $sql->bindValue(":userId", $model["userId"], PDO::PARAM_STR);
                    $sql->bindValue(":message", $model["message"], PDO::PARAM_STR);
                    $sql->bindValue(":imgUrl", $model["imgUrl"], PDO::PARAM_STR);
                    $sql->bindValue(":postDate", $model["postDate"], PDO::PARAM_STR);

                    $sql->execute();

                    break;

                case "PUT":
                    $model = (array) json_decode(file_get_contents("php://input"), true);
                    $stmt = "UPDATE carbon_bulletin SET user_id = :userId, message = :message, img_url = :imgUrl, post_date = :postDate";

                    $sql = $this->pdo->prepare($stmt);
                    $sql->bindValue(":userId", $model["userId"], PDO::PARAM_STR);
                    $sql->bindValue(":message", $model["message"], PDO::PARAM_STR);
                    $sql->bindValue(":imgUrl", $model["imgUrl"], PDO::PARAM_STR);
                    $sql->bindValue(":postDate", $model["postDate"], PDO::PARAM_STR);

                    $sql->execute();

                    break;

                case "DELETE":
                    $model = (array) json_decode(file_get_contents("php://input"), true);
                    $stmt = "DELETE FROM carbon_bulletin where id = :id";

                    $sql = $this->pdo->prepare($stmt);
                    $sql->bindValue(":id", $model["id"], PDO::PARAM_STR);

                    $sql->execute();

                    break;

            }
        }
    }