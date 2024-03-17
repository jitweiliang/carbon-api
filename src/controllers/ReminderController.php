<?php
    require "./src/utilities/Database.php";
    require "IController.php";

    class ReminderController implements IController
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
                        // -- get all reminders
                        case preg_match('/\/api\/reminders$/', $uri):
                            $stmt = "select * FROM carbon_reminders";
                            $sql = $this->pdo->prepare($stmt);
                            $sql->execute();
                            
                            $data = $sql->fetchAll(PDO::FETCH_OBJ);
                            echo json_encode($data);    

                            break;
                        case preg_match('/\/api\/reminders/user\/[1,9]', $uri):
                                $stmt = "select * FROM carbon_reminders where user_id = :userId";
                                $sql = $this->pdo->prepare($stmt);
                                $sql->execute();
                                
                                $data = $sql->fetchAll(PDO::FETCH_OBJ);
                                echo json_encode($data);    
    
                                break;
                            // -- get single reminder by id
                        case preg_match('/\/api\/reminders\/id\/[1-9]/', $uri):
                            // this is the last parameter in the url
                            $param = basename($uri);    

                            $stmt = "SELECT * FROM carbon_reminders WHERE id = :id";
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
                    switch(true) {
                        case preg_match('/\/api\/reminders/toggle$/'):
                            $model = (array) json_decode(file_get_contents("php://input"), true);
                            // { eventId: eventId, userId: userId, remindeBefore: remindBefore } //

                            $stmt = "insert into carbon_reminders (user_id, event_id, remind_before, reminder_date) 
                                                (:userId, :eventId, :remindBefore, :reminderDate";
                            $sql = $this->pdo->prepare($stmt);
                    
                            $sql->bindValue(":eventId",      $model["eventId"],         PDO::PARAM_INT);
                            $sql->bindValue(":userId",       $model["userId"],          PDO::PARAM_INT);
                            $sql->bindValue(":remindBefore", $model["reminderBefore"],  PDO::PARAM_STR);
                            $sql->bindValue(":reminderDate", $model["reminderDate"],    PDO::PARAM_STR);

                            $sql->execute();                   
                            echo json_encode($sql->rowCount());
    
                            break;
                        case preg_match('/\/api\/reminders\/change$/'):
                            $model = (array) json_decode(file_get_contents("php://input"), true);
                            // { eventId: eventId, remindeBefore: remindBefore } //
                            // remindbefore = 5m/1d/1w //

                            $stmt = "update carbon_reminders set remind_before = :remindBefore where event_id = :eventId";
                            $sql = $this->pdo->prepare($stmt);
                    
                            $sql->bindValue(":eventId",      $model["eventId"],      PDO::PARAM_INT);
                            $sql->bindValue(":remindBefore", $model["remindBefore"], PDO::PARAM_STR);
                            
                            $sql->execute();                   
                            echo json_encode($sql->rowCount());   

                            break;
                    }



                    $stmt = "update carbon_reminders 
                                set event_id = :eventId, user_id = :userId, alert_date = :alertDate, status = :status where id=:id";
                    $sql = $this->pdo->prepare($stmt);
                    
                    $sql->bindValue(":eventId",     $model["eventId"],   PDO::PARAM_STR);
                    $sql->bindValue(":userId",      $model["userId"],    PDO::PARAM_STR);
                    $sql->bindValue(":alertDate",   $model["alertDate"], PDO::PARAM_STR);
                    $sql->bindValue(":status",      $model["status"],    PDO::PARAM_STR);
                    $sql->bindValue(":id",          $model["id"],        PDO::PARAM_INT);                    

                    $sql->execute();                   
                    echo json_encode($sql->rowCount());

                    break;
                // ============================ P O S T ============================
                case "POST":
                    // --- get json data from request
                    $model = (array) json_decode(file_get_contents("php://input"), true);
                    
                    $stmt = "insert into carbon_reminders (user_id, event_id, reminder_date) values (:userId, :eventId, :reminderDate)";
                    
                    $sql = $this->pdo->prepare($stmt);
                    $sql->bindValue(":userId",       $model["userId"],       PDO::PARAM_INT);
                    $sql->bindValue(":eventId",      $model["eventId"],      PDO::PARAM_INT);
                    $sql->bindValue(":reminderDate", $model["reminderDate"], PDO::PARAM_STR);
                        
                    $sql->execute();
                    echo json_encode($sql->rowCount());

                    break;
                // ========================== E R R O R  ===========================
                default:
                    throw new Exception("Invalid User Controller request");
            }            
        }
    }   