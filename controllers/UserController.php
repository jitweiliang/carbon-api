<?php
    require "./utilities/Database.php";
    require "InterfController.php";

    class UserController implements InterfController {
        private $pdo;
        
        public function __construct(){
            $db = new Database();
            $this->pdo = $db->getPDOObject();
        }

        public function processRequest(string $verb, ?string $uri) :void {
            switch ($verb) {
                case "GET":
                    switch (true) {
                        case preg_match('/\/api\/users/', $uri):
                            $stmt = "SELECT * FROM carbon_users";
                            $sql = $this->pdo->prepare($stmt);
                            $sql->execute();

                            $data = $sql->fetchAll(PDO::FETCH_ASSOC);
                            echo json_encode($data);
                            break;
                        
                        case preg_match('/\/api\/users\/id\/[1-9]/', $uri):
                            $param = basename($uri);

                            $stmt = "SELECT * FROM carbon_users WHERE id = :id";
                            $sql = $this->pdo->prepare($stmt);
                            $sql->bindValue(":id", $param, PDO::PARAM_INT);
                            $sql->execute();

                            $data = $sql->fetch(PDO::FETCH_OBJ);
                            echo json_encode($data);
                            break;

                        default:
                            echo json_encode("Error in api request");
                            break;
                    }
                    break;

                // POST will have double functionality, to serve both as an email query, OR 
                case "POST":
                    switch(true) {

                        // If it comes with an 'email' suffix, then GET
                        case preg_match('/\/api\/users\/email/', $uri):
                            $model = (array) json_decode(file_get_contents("php://input"), true);

                            $stmt = "SELECT * FROM carbon_users WHERE user_email = :userEmail";
                            $sql = $this->pdo->prepare($stmt);
                            $sql->bindValue(":id", $model["userEmail"], PDO::PARAM_INT);
                            $sql->execute();

                            $data = $sql->fetch(PDO::FETCH_OBJ);
                            echo json_encode($data);
                            break;

                        // If not, then it's an UPSERT
                        default:
                            $model = (array) json_decode(file_get_contents("php://input"), true);

                            $stmt = "insert into carbon_users (user_name, user_email, photo_url, provider, token)
                                        values (:userName, :userEmail, :photoUrl, :provider, :token)";
        
                            $sql = $this->pdo->prepare($stmt);
                            $sql->bindValue(":userName", $model["userName"], PDO::PARAM_STR);
                            $sql->bindValue(":userEmail", $model["userEmail"], PDO::PARAM_STR);
                            $sql->bindValue(":photoUrl", $model["photoUrl"], PDO::PARAM_STR);
                            $sql->bindValue(":provider", $model["provider"], PDO::PARAM_STR);
                            $sql->bindValue(":token", $model["token"], PDO::PARAM_STR);
        
                            $sql->execute();
                            echo json_encode($sql->rowCount());
                        break;
                    }
                    

                // This is actually an UPSERT
                case "PUT": 
                    
                    $model = (array) json_decode(file_get_contents("php://input"), true);

                    $sql = $this->pdo->prepare("select * from carbon_users where user_email = :userEmail");
                    $sql->bindValue(":userEmail", $model["regEmail"], PDO::PARAM_STR);
                    $sql->execute();
                    $recordCount = $sql->rowCount();

                    if($recordCount == 0) {
                        // If the record count is 0, then no current user exists. INSERT
                        $stmt = "insert INTO carbon_users (user_name, user_email, user_country, photo_url, provider, token)
                                    VALUES (:userName, :userEmail, :userCountry, :photoUrl, :provider, :token)";

                        $sql = $this->pdo->prepare($stmt);
                        $sql->bindValue(":userName", $model["regName"], PDO::PARAM_STR);
                        $sql->bindValue(":userEmail", $model["regEmail"], PDO::PARAM_STR);
                        $sql->bindValue(":userCountry", $model["regCountry"] ?? null, PDO::PARAM_STR);
                        $sql->bindValue(":photoUrl", $model["photoUrl"] ?? null, PDO::PARAM_STR);
                        $sql->bindValue(":provider", $model["provider"] ?? null, PDO::PARAM_STR);
                        $sql->bindValue(":token", $model["token"] ?? null, PDO::PARAM_STR);

                        $sql->execute();

                        echo json_encode("New user successfully created!");
                    }
                    else {
                        // If not, then one or more existing matches have been found. UPDATE
                        $stmt = "update carbon_users SET
                                    user_name = :userName, user_email = :userEmail, user_country = :userCountry
                                    photo_url = :photoUrl, provider = :provider, token = :token
                                WHERE id = :id";
                        $sql = $this->pdo->prepare($stmt);
                        $sql->bindValue(":userName", $model["userName"], PDO::PARAM_STR);
                        $sql->bindValue(":userEmail", $model["userEmail"], PDO::PARAM_STR);
                        $sql->bindValue(":userCountry", $model["userEmail"], PDO::PARAM_STR);
                        $sql->bindValue(":photoUrl", $model["photoUrl"], PDO::PARAM_STR);
                        $sql->bindValue(":provider", $model["provider"], PDO::PARAM_STR);
                        $sql->bindValue(":token", $model["token"], PDO::PARAM_STR);

                        echo jsoN_encode("Existing user successfully updated!");
                        echo json_encode($sql->rowCount());
                        $sql->execute();
                    }
                    
                    break;

                case "DELETE":
                    $model = (array) json_decode(file_get_contents("php://input"), true);

                    $stmt = "DELETE FROM carbon_users WHERE id = :id";
                    $sql = $this->pdo->prepare($stmt);
                    $sql->bindValue(":id", $model["id"], PDO::PARAM_STR);

                    echo json_encode($sql->rowCount());
                    $sql->execute();

                    break;
            }
        }
    }