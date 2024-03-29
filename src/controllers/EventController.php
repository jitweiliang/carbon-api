<?php
    require "./src/utilities/Database.php";
    require "./src/utilities/FirebaseSDK.php";

    class EventController
    {
        private $pdo;

        public function __construct()
        {
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
                        // -- get all reminders by user id
                        case preg_match('/\/api\/events\/latest\/user\/d{0,3}/', $uri):
                            // this is the last parameter in the url
                            $param = basename($uri);    

                            $stmt = "select t1.id AS evtId, t2.id AS reminderId, t1.event_name AS eventName, t1.event_desc AS eventDesc, t1.start_date AS startDate, t1.end_Date AS endDate, 
                                        t2.remind_before as remindBefore, t2.reminder_date AS reminderDate 
                                        from carbon_events t1
                                            left join carbon_reminders t2 ON t1.id = t2.event_id AND t2.user_id = :userId
                                            where start_date >= NOW();";
                            $sql = $this->pdo->prepare($stmt);
                            $sql->bindValue(":userId", $param, PDO::PARAM_INT);
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
            }            
        }
    }   