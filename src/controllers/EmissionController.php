<?php
    require "./src/utilities/Database.php";
    require "./src/utilities/FirebaseSDK.php";
    
    class EmissionController
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
                        // -- get single user by id
                        case preg_match('/\/api\/emissions\/id\/d{0,3}/', $uri):
                            // this is the last parameter in the url
                            $param = basename($uri);    

                            $stmt = "select t1.id as id, t1.user_id as userId, t2.user_name as userName,
                                        t1.submitted_date as submittedDate, t1.household as household, t1.transportation as transportation,
                                        t1.food as food, t1.total_emission as totalEmission 
                                        from carbon_emissions t1
                                            left join carbon_users t2 on t1.user_id = t2.id
                                        where t1.id = :id";

                            $sql = $this->pdo->prepare($stmt);
                            $sql->bindValue(":id", $param, PDO::PARAM_INT);
                            $sql->execute();
                            
                            $data = $sql->fetch(PDO::FETCH_ASSOC);
                            echo json_encode($data);    

                            break;
                        // --- get last emission submission by user
                        case preg_match('/\/api\/emissions\/history\/user\/d{0,3}/', $uri):
                                // this is the last parameter in the url
                                $param = basename($uri);    
    
                                // -- 1. get array of days
                                $stmt = "select submitted_date as submittedDate from carbon_emissions where user_id = :userId
                                            order by id desc limit 5";
                                $sql = $this->pdo->prepare($stmt);
                                $sql->bindValue(":userId", $param, PDO::PARAM_INT);
                                $sql->execute();
            
                                $data = $sql->fetchAll(PDO::FETCH_OBJ);
                                $daySummary = array();
                                if(sizeof($data) > 0) {
                                    foreach($data as $dat) {
                                        array_push($daySummary, $dat->submittedDate);
                                    }                                    
                                }
                                
                                // -- 2. get array of househould
                                $stmt = "select household from carbon_emissions where user_id = :userId 
                                            order by id desc limit 5";
    
                                $sql = $this->pdo->prepare($stmt);
                                $sql->bindValue(":userId", $param, PDO::PARAM_INT);
                                $sql->execute();
                                
                                $data = $sql->fetchAll(PDO::FETCH_OBJ);
                                $householdSummary = array();
                                if(sizeof($data) > 0) {
                                    foreach($data as $dat) {
                                        array_push($householdSummary, $dat->household);
                                    }                                    
                                }
                                // -- 2. get array of transportation
                                $stmt = "select transportation from carbon_emissions where user_id = :userId 
                                            order by id desc limit 5";
    
                                $sql = $this->pdo->prepare($stmt);
                                $sql->bindValue(":userId", $param, PDO::PARAM_INT);
                                $sql->execute();
                                
                                $data = $sql->fetchAll(PDO::FETCH_OBJ);
                                $transportationSummary = array();
                                if(sizeof($data) > 0) {
                                    foreach($data as $dat) {
                                        array_push($transportationSummary, $dat->transportation);
                                    }                                    
                                }
                                // -- 2. get array of food
                                $stmt = "select food from carbon_emissions where user_id = :userId 
                                            order by id desc limit 5";
    
                                $sql = $this->pdo->prepare($stmt);
                                $sql->bindValue(":userId", $param, PDO::PARAM_INT);
                                $sql->execute();
                                
                                $data = $sql->fetchAll(PDO::FETCH_OBJ);
                                $foodSummary = array();
                                if(sizeof($data) > 0) {
                                    foreach($data as $dat) {
                                        array_push($foodSummary, $dat->food);
                                    }                                    
                                }

                                $totalSummary = array(
                                    "date"          =>$daySummary,
                                    "household"     =>$householdSummary,
                                    "transportation"=>$transportationSummary,
                                    "food"          =>$foodSummary,
                                    "totalEmission" =>$householdSummary + $transportationSummary + $foodSummary
                                );
                                echo json_encode($totalSummary);
        
                                break;                    
                        // -- get average of emissions categories
                        case preg_match('/\/api\/emissions\/average\/user\/d{0,3}/', $uri):
                            $param = basename($uri);

                            $stmt = "select count(*) as emissionCount, sum(total_emission) as totalEmission, avg(total_emission) as avgEmission 
                                        from carbon_emissions where user_id = :userId";

                            $sql = $this->pdo->prepare($stmt);
                            $sql->bindValue(":userId", $param, PDO::PARAM_INT);

                            $sql->execute();
                            
                            $data = $sql->fetch(PDO::FETCH_ASSOC);

                            echo json_encode($data);

                            break;                            
                        // -- get average of emissions categories
                        case preg_match('/\/api\/emissions\/category\/average$/', $uri):
                            $stmt = "select avg(household) as household, avg(transportation) as transportation, avg(food) as food from carbon_emissions";

                            $sql = $this->pdo->prepare($stmt);
                            $sql->execute();
                            
                            $data = $sql->fetch(PDO::FETCH_ASSOC);

                            // php => array("household"=>101607.1429", "transportation":"3581.2857", "food":"895.3571")
                            // js  => { "household": "101607.1429", "transportation": "3581.2857", "food": "895.3571" }

                            $summary = array();
                            if(sizeof($data) > 0) {
                                array_push($summary, array("name"=>"household",      "y"=>(int)$data["household"]));
                                array_push($summary, array("name"=>"transportation", "y"=>(int)$data["transportation"]));
                                array_push($summary, array("name"=>"food",           "y"=>(int)$data["food"]));
                            }

                            // php => array(array("name"=>"household", "y"=>101607.1429), array("name"=>"transportation", "y"=>3581.2857), array("name"=>"food", "y"=>8953571);
                            // js  => [{"name": "household", "y": 101607.1429}, {"name": "transportation", "y": 3581.2857}, {"name": "food", "y": 895.3571}];

                            echo json_encode($summary);    

                            break;                            
                        // -- get emissions ranking / order by total_emissions
                        case preg_match('/\/api\/emissions\/ranking$/', $uri):
                            $stmt = "select t1.id as id, t2.id as userId, t2.user_name as userName, t2.user_email as email, t2.photo_url as photoUrl,
                                        t1.household as household, t1.transportation as transportation, t1.food as food, t1.total_emission as totalEmission, t1.submitted_date as submittedDate 
                                        from carbon_emissions t1
                                            left join carbon_users t2 on t1.user_id = t2.id
                                        order by total_emission limit 10";

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

                // ============================ P O S T ============================                
                case "POST":
                    // --- insert new emission calculation
                    $model = (array) json_decode(file_get_contents("php://input"), true);
                    
                    $stmt = "insert into carbon_emissions (user_id, household, transportation, food, total_emission)
                                        values (:userId, :household, :transportation, :food, :totalEmission)";

                    $sql = $this->pdo->prepare($stmt);
                    $sql->bindValue(":userId",         $model["userId"],         PDO::PARAM_INT);
                    $sql->bindValue(":household",      $model["household"],      PDO::PARAM_INT);
                    $sql->bindValue(":transportation", $model["transportation"], PDO::PARAM_INT);
                    $sql->bindValue(":food",           $model["food"],           PDO::PARAM_INT);
                    $sql->bindValue(":totalEmission",  $model["totalEmission"],  PDO::PARAM_INT);

                    $sql->execute();
                    // -- get newest id
                    $lastId = $this->pdo->lastInsertId();

                    // -- make sure row is successfully inserted
                    if($sql->rowCount() > 0) {
                        // -- push new updates (row) to firestore
                        $this->sdk->firestoreAdd('emissions', $model["userId"]);
                        // -- send notification to users
                        // Select ALL tokens from users, then prep n execute
                        $stmt = 'select token from carbon_users where token is not null';
                        $sql = $this->pdo->prepare($stmt);
                        $sql->execute();

                        $data = $sql->fetchAll(PDO::FETCH_ASSOC);
                        foreach($data as $dataEle) {
                            $this->sdk->sendNotificationToOneDevice(
                                $dataEle["token"],
                                "HELP Carbon Emission Achievement",
                                "Congratulations {$model["userName"]} for you latest achievement !!!"
                            );
                        };
                    }

                    // -- to return lastest insert id so the certificate can be displayed
                    echo json_encode(array("lastId" => $lastId));

                    break;
            }
        }
    }