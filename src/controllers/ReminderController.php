<?php
    require "./src/utilities/Database.php";
    require "./src/utilities/FirebaseSDK.php";


    class ReminderController
    {
        private $pdo;
        private $sdk;

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
                        case preg_match('/\/api\/reminders\/user\/d{0,3}/', $uri):
                            // this is the last parameter in the url
                            $param = basename($uri);

                            $stmt = "select t1.id as reminderId, t1.reminder_date as reminderDate, t1.remind_before as remindBefore,
                                        t1.event_id as eventId, t2.event_name as eventName, t2.event_desc as eventDesc, t2.start_date as eventStartDate, t2.end_date as eventEndDate 
                                        FROM carbon_reminders t1 
                                            left join carbon_events t2 on t1.event_id = t2.id where user_id = :userId 
                                            and date(reminder_date) >= NOW()";
                            $sql = $this->pdo->prepare($stmt);
                            $sql->bindValue(":userId", $param, PDO::PARAM_INT);
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

                            break;
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
                    switch(true) {
                        case preg_match('/\/api\/reminders\/event\/5m$/', $uri):
                            // https://stackoverflow.com/questions/41177335/php-get-current-time-in-specific-time-zone
                            // https://www.php.net/manual/en/timezones.asia.php
                            $currentDate = new DateTime(null, new DateTimeZone('Asia/Kuala_Lumpur'));
                            $currentDateStr = $currentDate->format('Y-m-d H:i:s');
                            // Select ALL tokens from users, then prep n execute
                            $stmt = "select t3.token as token, t2.event_name as eventName, t1.reminder_date
                                        from carbon_reminders t1
                                            left join carbon_events t2 ON t1.event_id = t2.id
                                            left join carbon_users t3 ON t1.user_id = t3.id
                                        where TIMESTAMPDIFF(MINUTE, reminder_date, '{$currentDateStr}') between 0 AND 5
                                        and remind_before = '5m'";
                            $sql = $this->pdo->prepare($stmt);
                            $sql->execute();

                            $data = $sql->fetchAll(PDO::FETCH_ASSOC);
                            foreach($data as $dataEle) {
                                $this->sdk->sendNotificationToOneDevice(
                                    $dataEle["token"],
                                    "HELP Carbon Events Reminder",
                                    "A reminder for you upcoming event {$dataEle['eventName']}"
                                );
                            };


                            break;
                        case preg_match('/\/api\/reminders$/', $uri):
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
                        case preg_match('/\/api\/reminders\/events\/alert$/', $uri):
                            $stmt = "select t3.token AS userToken, t2.event_name AS eventName, t2.start_date eventStartDate
                                        from carbon_reminders t1
                                            left join carbon_events t2 ON t1.event_id = t2.id
                                            left join carbon_users t3 ON t1.user_id = t3.id
                                        where t1.reminder_date <= NOW() and t2.start_date >= NOW()";
                            
                            $sql = $this->pdo->prepare($stmt);
                            $sql->execute();
                                
                            // -- 1. using array of objects
                            $data = $sql->fetchAll(PDO::FETCH_OBJ);

                            $notificationsArray = array();
                            foreach($data as $dat) {
                                $notify = new stdClass();
                                $notify->token = $dat->userToken;
                                $notify->eventName = $dat->eventName;
                                $notify->body = "you have a reminder {$dat->eventStartDate}";
                                array_push($notificationsArray, $notify);
                            }
                            // -- 2. using array of associative arrays
                            // $data = $sql->fetchAll(PDO::FETCH_ASSOC);
                            // $notificationsArray = array();
                            // foreach($data as $dat) {
                            //     array_push($notificationsArray, 
                            //         array("token"=>$dat["userToken"], "event"=>$dat["eventName"], "startDate"=>$dat["eventStartDate"]));
                            // }

                            $this->sdk->sendNotifications($notificationsArray);

                            break;
                        case preg_match('/\/api\/reminders\/activity\/alerts$/', $uri):
                            $stmt = "select t3.token AS userToken, t2.event_name AS eventName, t2.start_date eventStartDate
                            from carbon_reminders t1
                                left join carbon_events t2 ON t1.event_id = t2.id
                                left join carbon_users t3 ON t1.user_id = t3.id
                            where t1.reminder_date >= NOW()";
                
                            $sql = $this->pdo->prepare($stmt);
                            $sql->execute();
                                
                            // -- 1. using array of objects
                            $data = $sql->fetchAll(PDO::FETCH_OBJ);
                            $notificationsArray = array();
                            foreach($data as $dat) {
                                $notify = new stdClass();
                                $notify->token = $dat->userToken;
                                $notify->title = $dat->eventName;
                                $notify->body = $dat->eventStartDate;
                                array_push($notificationsArray, $notify);
                            }
                            // -- 2. using array of associative arrays
                            // $data = $sql->fetchAll(PDO::FETCH_ASSOC);
                            // $notificationsArray = array();
                            // foreach($data as $dat) {
                            //     array_push($notificationsArray, 
                            //         array("token"=>$dat["userToken"], "event"=>$dat["eventName"], "startDate"=>$dat["eventStartDate"]));
                            // }

                            $this->sdk->sendNotifications($notificationsArray);

                            break;
                    }

                    break;
            }            
        }
    }   