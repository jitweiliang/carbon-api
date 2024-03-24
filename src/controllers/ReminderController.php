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
                        case preg_match('/\/api\/reminders\/user\/d{0,3}/', $uri):
                            $stmt = "select t1.id as reminderId, t1.reminder_date as reminderDate, t1.event_id as eventId, 
                                        t2.event_name as eventName, t2.event_desc as eventDesc, t2.start_date as eventStartDate, t2.end_date as eventEndDate 
                                        FROM carbon_reminders t1 
                                            left join carbon_events t2 on t1.event_id = t2.id where user_id = :userId and date(reminder_date) >= NOW()";
                            $sql = $this->pdo->prepare($stmt);
                            $sql->execute();
                                
                            $data = $sql->fetchAll(PDO::FETCH_OBJ);
                            echo json_encode($data);    
    
                            break;
                        default:
                            throw new Exception("Invalid get request !!!");
                            break;
                    }

                    break;
                // ============================ G E T ==============================
                case "PUT":
                        switch(true) {
                            case preg_match('/\/api\/reminders\/before$/', $uri):
                                $model = (array) json_decode(file_get_contents("php://input"), true);
                    
                                $stmt = "update carbon_reminders set remind_before = :remindBefore, reminder_date = :reminderDate where id = :reminderId"; 
                                
                                $sql = $this->pdo->prepare($stmt);
                                $sql->bindValue(":remindBefore", $model["remindBefore"], PDO::PARAM_STR);
                                $sql->bindValue(":reminderDate", $model["reminderDate"], PDO::PARAM_STR);
                                $sql->bindValue(":reminderId",   $model["reminderId"],   PDO::PARAM_INT);
                                    
                                $sql->execute();
                                echo json_encode($sql->rowCount());
                            }
                    
                        break;
                // ======================== D E L E T E  ===========================
                case "DELETE":
                    switch(true) {
                        case preg_match('/\/api\/reminders\/\d{0,3}/', $uri):
                            // this is the last parameter in the url
                            $param = basename($uri);

                            $stmt = "delete from carbon_reminders where id = :id";
                            $sql = $this->pdo->prepare($stmt);
                            $sql->bindValue(":id", $param, PDO::PARAM_INT);

                            $sql->execute();                   
                            echo json_encode($sql->rowCount());
                            break;
                    }

                    break;
                // ============================ P O S T ============================
                case "POST":
                    // --- get json data from request
                    $model = (array) json_decode(file_get_contents("php://input"), true);
                    
                    $stmt = "insert into carbon_reminders (user_id, event_id, remind_before, reminder_date) 
                                values (:userId, :eventId, :remindBefore, :reminderDate)";
                    
                    $sql = $this->pdo->prepare($stmt);
                    $sql->bindValue(":userId",       $model["userId"],       PDO::PARAM_INT);
                    $sql->bindValue(":eventId",      $model["evtId"],        PDO::PARAM_INT);
                    $sql->bindValue(":remindBefore", $model["remindBefore"], PDO::PARAM_STR);
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