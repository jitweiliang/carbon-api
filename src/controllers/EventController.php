<?php
    require "./src/utilities/Database.php";
    require "IController.php";

    class EventController implements IController
    {
        private $pdo;

        public function __construct()
        {
            // --- get a new PDO object for mysql connection
            $db = new Database();
            $this->pdo = $db->getPDOObject();
        }


        public function processRequest(string $verb, ?string $uri): void
        {
            switch ($verb) {
                // ============================ G E T ==============================
                case "GET":
                    switch(true) {
                        // -- get all users
                        case preg_match('/\/api\/events$/', $uri):
                            $stmt = "select * FROM carbon_events";
                            $sql = $this->pdo->prepare($stmt);
                            $sql->execute();
                            
                            $data = $sql->fetchAll(PDO::FETCH_OBJ);
                            echo json_encode($data);    

                            break;
                        // -- get single user by id
                        case preg_match('/\/api\/events\/id\/[1-9]/', $uri):
                            // this is the last parameter in the url
                            $param = basename($uri);    

                            $stmt = "SELECT * FROM carbon_events WHERE id = :id";
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

                    break;
                // ============================= P U T =============================
                case "PUT":
                    // --- get json data from request
                    $model = (array) json_decode(file_get_contents("php://input"), true);

                    $stmt = "update carbon_events 
                                set event_name = :eventName, event_desc = :eventDesc, start_date = startDate, end_date = endDate where id=:id";
                    $sql = $this->pdo->prepare($stmt);
                    
                    $sql->bindValue(":eventName", $model["eventName"], PDO::PARAM_STR);
                    $sql->bindValue(":eventDesc", $model["eventDesc"], PDO::PARAM_STR);
                    $sql->bindValue(":startDate", $model["startDate"], PDO::PARAM_STR);
                    $sql->bindValue(":endDate", $model["endDate"], PDO::PARAM_STR);
                    $sql->bindValue(":id",       $model["id"],       PDO::PARAM_INT);                    

                    $sql->execute();                   
                    echo json_encode($sql->rowCount());

                    break;
                // ============================ P O S T ============================
                case "POST":
                    // --- get json data from request
                    $model = (array) json_decode(file_get_contents("php://input"), true);
                    
                    // --- check if email already exists in the users table
                    $sql = $this->pdo->prepare("select id from carbon_events where user_email = :userEmail");
                    $sql->bindValue(":userEmail", $model["userEmail"], PDO::PARAM_STR);                    
                    $sql->execute();                   
                    $recordCount = $sql->rowCount();

                        $stmt = "update carbon_users set event_name = :eventName, event_desc = :eventDesc, start_date = :startDate, end_date = :endDate WHERE id = :id";
                    
                        $sql = $this->pdo->prepare($stmt);
                        $sql->bindValue(":eventName", $model["eventName"], PDO::PARAM_STR);
                        $sql->bindValue(":eventDesc", $model["eventDesc"], PDO::PARAM_STR);
                        $sql->bindValue(":startDate", $model["startDate"], PDO::PARAM_STR);
                        $sql->bindValue(":endDate", $model["endDate"], PDO::PARAM_STR);
                        $sql->bindValue(":id", $model["id"], PDO::PARAM_STR);
                        
                        $sql->execute();
                        echo json_encode($sql->rowCount());     

                    break;
                // ========================== E R R O R  ===========================
                default:
                    throw new Exception("Invalid User Controller request");
            }            
        }
    }   