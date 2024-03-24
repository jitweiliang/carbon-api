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
                        // -- get all latest bulletins (top 10)
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
            }
        }
    }