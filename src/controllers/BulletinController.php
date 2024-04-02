<?php
    require "./src/utilities/Database.php";
    require "./src/utilities/FirebaseSDK.php";
    
    class BulletinController
    {
        private $pdo;
        private $sdk;

        public function __construct() {
            // --- get a new PDO object for mysql connection
            $db = new Database();
            $this->pdo = $db->getPDOObject();
            
            // ---- get instance of firebase sdk
            $this->sdk = new FirebaseSDK();
        }

        public function processRequest(string $verb, ?string $uri): void 
        {
            switch ($verb) {
                // ============================ G E T ==============================
                case "GET":
                    switch(true) {
                        // -- get all latest bulletins (top 10)
                        case preg_match('/\/api\/bulletins\/latest$/', $uri):
                            $stmt = "select t1.id as id, t2.id as userId, t2.user_name as userName, t2.photo_url as photoUrl, 
                                        t1.title as title, t1.message as message, t1.post_date as postDate 
                                        from carbon_bulletins t1 
                                            left join carbon_users t2 on t1.user_id = t2.id order by t1.id desc limit 10";
                                            
                            $sql = $this->pdo->prepare($stmt);
                            $sql->execute();
                            
                            $data = $sql->fetchAll(PDO::FETCH_OBJ);
                            echo json_encode($data);    

                            break;
                        // --- if requests do not match any get api
                        default:
                            throw new Exception("Invalid get request !!!");
                            break;
                        }                        
                    break;
                // ============================ P O S T ============================
                case "POST":
                    $model = (array) json_decode(file_get_contents("php://input"), true);
                    
                    $stmt = "insert into carbon_bulletins (user_id, title, message)
                                        values (:userId, :title, :message)";

                    $sql = $this->pdo->prepare($stmt);
                    $sql->bindValue(":userId",  $model["userId"],  PDO::PARAM_STR);
                    $sql->bindValue(":title",   $model["title"],   PDO::PARAM_STR);
                    $sql->bindValue(":message", $model["message"], PDO::PARAM_STR);

                    $sql->execute();
                    // -- make sure row is successfully inserted
                    if($sql->rowCount() > 0) {
                        // -- push new updates (row) to firestore
                        $this->sdk->firestoreAdd('bulletins', $model["userId"]);

                        // -- send notifications to all users
                        // Select ALL tokens from users, then prep n execute
                        $stmt = 'select token from carbon_users where token is not null';
                        $sql = $this->pdo->prepare($stmt);
                        $sql->execute();

                        $data = $sql->fetchAll(PDO::FETCH_ASSOC);
                        foreach($data as $dataEle) {
                            $this->sdk->sendNotificationToOneDevice(
                                $dataEle["token"],
                                "HELP Carbon Emission Bulletins",
                                "A new bulletin message has been posted !!!"
                            );
                        };                    
                    }
                    break;
            }
        }
    }