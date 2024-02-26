<?php

    require "./utilities/Database.php";
    require "InterfController.php";

    class BulletinController implements InterfController {
        private $pdo;

        public function __construct() {
            $db = new Database();
            $this->pdo = $db->getPDOObject();
        }

        public function processRequest(string $verb, $url) {
            switch ($verb) {
                case "GET":
                    $stmt = "";

                    break;

                case "POST":
                    $model = (array) json_decode(file_get_contents("php://input"), true);
                    $stmt = "INSERT INTO carbon_bulletin (user_id, message, img_url, post_date)
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