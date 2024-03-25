<?php

    require "./src/utilities/Database.php";
    require "./src/utilities/FirebaseSDK.php";
    
    require "IController.php";

    class EmissionController implements IController 
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
                        case preg_match('/\/api\/emissions\/ranking$/', $uri):
                            $stmt = "select t1.id as id, t2.id as userId, t2.user_name as userName, t2.user_email as email, t2.photo_url as photoUrl,
                                        t1.household as household, t1.transportation as transportation, t1.food as food, t1.total_emission as totalEmission, t1.submitted_date as submittedDate 
                                        from carbon_emissions t1
                                            left join carbon_users t2 on t1.user_id = t2.id
                                        order by total_emission limit 12";

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
                    
                    $stmt = "insert into carbon_emissions (user_id, household, transportation, food, total_emission)
                                        values (:userId, :household, :transportation, :food, :totalEmission)";

                    $sql = $this->pdo->prepare($stmt);
                    $sql->bindValue(":userId",         $model["userId"],         PDO::PARAM_INT);
                    $sql->bindValue(":household",      $model["household"],      PDO::PARAM_INT);
                    $sql->bindValue(":transportation", $model["transportation"], PDO::PARAM_INT);
                    $sql->bindValue(":food",           $model["food"],           PDO::PARAM_INT);
                    $sql->bindValue(":totalEmission",  $model["totalEmission"],  PDO::PARAM_INT);

                    $sql->execute();                   
                    $lastId = $this->pdo->lastInsertId();

                    if($sql->rowCount() > 0) {
                        $this->sdk->firestoreAdd('emissions', $model["userId"]);
                    }

                    echo json_encode(array("lastId" => $lastId));

                    break;
            }
        }
    }