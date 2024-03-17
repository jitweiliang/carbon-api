<?php

    require "./src/utilities/Database.php";
    require "./src/utilities/FirebaseSDK.php";
    require "IController.php";

    class BulletinController implements IController 
    {
        private $pdo;
        private $sdk;

        public function __construct() {
            $db = new Database();
            $this->pdo = $db->getPDOObject();

            $this->sdk = new FirebaseSDK();
        }

        public function processRequest(string $verb, ?string $uri): void 
        {
            switch ($verb) {
                case "GET":
                    switch(true) {
                        // -- get all users
                        case preg_match('/\/api\/bulletins\/latest$/', $uri):
                            $stmt = "select t1.id as id, t2.id as userId, t2.user_name as userName, 
                                        t1.title as title, t1.message as message, t1.post_date as postDate 
                                        from carbon_bulletins t1 
                                            left join carbon_users t2 on t1.user_id = t2.id order by t1.id desc limit 10";
                                            
                            $sql = $this->pdo->prepare($stmt);
                            $sql->execute();
                            
                            $data = $sql->fetchAll(PDO::FETCH_OBJ);
                            echo json_encode($data);    

                            break;
                        // -- get single user by id
                        case preg_match('/\/api\/bulletins\/id\/[1-9]/', $uri):
                            // this is the last parameter in the url
                            $param = basename($uri);    

                            $stmt = "SELECT * FROM carbon_bulletins WHERE id = :id";
                            $sql = $this->pdo->prepare($stmt);
                            $sql->bindValue(":id", $param, PDO::PARAM_INT);
                            $sql->execute();
                            
                            $data = $sql->fetch(PDO::FETCH_ASSOC);
                            echo json_encode($data);    

                            break;
                        // --- if requests do not match any api
                        default:
                            throw new Exception("Invalid get request !!!");
                            break;
                        }
                        
                    break;
                case "POST":
                    $model = (array) json_decode(file_get_contents("php://input"), true);
                    
                    $stmt = "insert into carbon_bulletins (user_id, title, message, img_url)
                                        values (:userId, :title, :message, :imgUrl)";

                    $sql = $this->pdo->prepare($stmt);
                    $sql->bindValue(":userId",  $model["userId"], PDO::PARAM_STR);
                    $sql->bindValue(":title",   $model["title"], PDO::PARAM_STR);
                    $sql->bindValue(":message", $model["message"], PDO::PARAM_STR);
                    $sql->bindValue(":imgUrl",  $model["imgUrl"], PDO::PARAM_STR);

                    $sql->execute();
                    if($sql->rowCount() > 0) {
                        $this->sdk->firestoreAdd('bulletins', $model["userName"]);
                    }

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