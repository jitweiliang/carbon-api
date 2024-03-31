<?php
    require "./src/utilities/Database.php";
    require "./src/utilities/FirebaseSDK.php";

    class UserController
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
                        // -- get single user by id
                        case preg_match('/\/api\/users\/id\/d{0,3}/', $uri):
                            // this is the last parameter in the url
                            $param = basename($uri);    

                            $stmt = "select id as userId, user_name as userName, user_email as userEmail, user_address1 as userAddress1, user_address2 as userAddress2,
                                        user_contactno as userContactNo, photo_url as photoUrl, about_me as aboutMe 
                                        from carbon_users WHERE id = :id";
                            $sql = $this->pdo->prepare($stmt);
                            $sql->bindValue(":id", $param, PDO::PARAM_INT);
                            $sql->execute();
                            
                            $data = $sql->fetch(PDO::FETCH_ASSOC);
                            echo json_encode($data, JSON_UNESCAPED_SLASHES);

                            break;
                        // -- get users summary
                        case preg_match('/\/api\/users\/summary$/', $uri):

                            // 1 -- get total users
                            $stmt = "select count(*) as totalUsers from carbon_users";
                            $sql = $this->pdo->prepare($stmt);
                            $sql->execute();                           
                            $data1 = $sql->fetch(PDO::FETCH_OBJ);       // $data1->totalUsers == 4

                            // 2 -- get total active users current month
                            $stmt = "select count(distinct(user_id)) as activeUsers 
                                        from carbon_emissions where month(submitted_date) = month(now()) and year(submitted_date) = year(now())";
                            $sql = $this->pdo->prepare($stmt);
                            $sql->execute();                           
                            $data2 = $sql->fetch(PDO::FETCH_OBJ);       // $data2->activeUses == 3

                            echo json_encode(
                                array("totalUsers"=>$data1->totalUsers, "activeUsers"=>$data2->activeUsers));

                            break;
                        // -- get users summary
                        case preg_match('/\/api\/users\/activities\/id\/d{0,3}/', $uri):
                            $param = basename($uri);

                            // 1 -- get total users
                            $stmt = "select count(*) as emissionsCount 
                                        from carbon_emissions where user_id = :userId";
                            $sql = $this->pdo->prepare($stmt);
                            $sql->bindValue(":userId", $param, PDO::PARAM_INT);

                            $sql->execute();                           
                            $data1 = $sql->fetch(PDO::FETCH_OBJ);       // $data1->emissionCount == 4

                            // 2 -- get total active users current month
                            $stmt = "select count(*) as bulletinsCount 
                                        from carbon_bulletins where user_id = :userId";
                            $sql = $this->pdo->prepare($stmt);
                            $sql->bindValue(":userId", $param, PDO::PARAM_INT);

                            $sql->execute();                           
                            $data2 = $sql->fetch(PDO::FETCH_OBJ);       // $data2->activeUses == 3

                            echo json_encode(
                                array("emissionsCnt"=>$data1->emissionsCount, "bulletinsCnt"=>$data2->bulletinsCount));

                            break;                            
                        // --- if requests do not match any api
                        default:
                            throw new Exception("Invalid get request !!!");
                    }

                    break;
                // ============================= P U T =============================
                case "PUT":
                    switch(true) {
                        case preg_match('/\/api\/users$/', $uri):
                            // --- get json data from request
                            $model = (array) json_decode(file_get_contents("php://input"), true);

                            $stmt = "update carbon_users 
                                            set user_name=:userName, user_address1=:userAddress1, user_address2=:userAddress2, user_contactNo=:userContactNo, about_me=:aboutMe  
                                            where id=:userId";
                            $sql = $this->pdo->prepare($stmt);
                            
                            $sql->bindValue(":userName",      $model["userName"],      PDO::PARAM_STR);
                            $sql->bindValue(":userAddress1",  $model["userAddress1"],  PDO::PARAM_STR);
                            $sql->bindValue(":userAddress2",  $model["userAddress2"],  PDO::PARAM_STR);
                            $sql->bindValue(":userContactNo", $model["userContactNo"], PDO::PARAM_STR);
                            $sql->bindValue(":aboutMe",       $model["aboutMe"],       PDO::PARAM_STR);
                            $sql->bindValue(":userId",        $model["userId"],            PDO::PARAM_INT);                    

                            $sql->execute();                   
                            echo json_encode($sql->rowCount());

                            break;
                    }

                    break;
                // ============================ P O S T ============================
                case "POST":
                    switch(true) {
                        // -- get single user by id
                        case preg_match('/\/api\/users\/id\/d{0,3}/', $uri):
                            // this is the last parameter in the url
                            $param = basename($uri);    
                        
                            $stmt = "SELECT * FROM carbon_users WHERE id = :id";
                            $sql = $this->pdo->prepare($stmt);
                            $sql->bindValue(":id", $param, PDO::PARAM_INT);
                            $sql->execute();
                                                    
                            $data = $sql->fetch(PDO::FETCH_ASSOC);
                            echo json_encode($data);    
                        
                            break;
                        // -- get single user by email
                        case preg_match('/\/api\/users\/email$/', $uri):
                            // --- get json data from request
                            $model = (array) json_decode(file_get_contents("php://input"), true);

                            $stmt = "select id as id, user_name as userName, user_email as userEmail, user_address1 as userAddress1, user_address2 as userAddress2,
                                            user_contactno as userContactNo, photo_url as photoUrl, about_me as aboutMe 
                                            from carbon_users where user_email = :userEmail";
                            
                            $sql = $this->pdo->prepare($stmt);
                            $sql->bindValue(":userEmail", $model["userEmail"], PDO::PARAM_STR);
                            $sql->execute();
                                                 
                            $data = $sql->fetch(PDO::FETCH_ASSOC);
                            echo json_encode($data);    
                        
                            break; 
                        // -- upload images
                        case preg_match('/\/api\/users\/img$/', $uri):
                            $imageFileName = $_FILES['imgfile']['name'];
                            $imageTmpName = $_FILES['imgfile']['tmp_name'];

                            $userEmail = $_POST["userEmail"];
   
                            $imageFileName = $this->sdk->storageStoreImage($imageTmpName, $imageFileName);

                            if($imageFileName) {
                                // https://storage.googleapis.com/<<bucket name>>/5813testload.png
                                $photoUrl = "https://storage.googleapis.com/carbon-project-9a417.appspot.com/{$imageFileName}";
                                $stmt = "update carbon_users set photo_url = :photoUrl where user_email = :userEmail";
                
                                $sql = $this->pdo->prepare($stmt);
                                $sql->bindValue(":photoUrl",  $photoUrl, PDO::PARAM_STR);
                                $sql->bindValue(":userEmail", $userEmail, PDO::PARAM_STR);
                                $sql->execute();
                           }

                            echo json_encode(array("photoUrl"=> $photoUrl));

                            break;                            
                        // -- upsert user profile
                        case preg_match('/\/api\/users$/', $uri):
                            // --- get json data from request
                            $model = (array) json_decode(file_get_contents("php://input"), true);
                            
                            // --- check if email already exists in the users table
                            $sql = $this->pdo->prepare("select id from carbon_users where user_email = :userEmail");
                            $sql->bindValue(":userEmail", $model["userEmail"], PDO::PARAM_STR);                    
                            $sql->execute();                   
                            $recordCount = $sql->rowCount();

                            // --- if user does not exists, then will do an insert into table
                            if($recordCount == 0) {
                                $stmt = "insert into carbon_users (user_name, user_email, provider, token) 
                                                    values (:userName, :userEmail, :provider, :token)";

                                $sql = $this->pdo->prepare($stmt);
                                $sql->bindValue(":userName",  $model["userName"] ?? "",  PDO::PARAM_STR);
                                $sql->bindValue(":userEmail", $model["userEmail"], PDO::PARAM_STR);
                                $sql->bindValue(":provider",  $model["provider"],  PDO::PARAM_STR);
                                $sql->bindValue(":token",     $model["token"],     PDO::PARAM_STR);
                
                                $sql->execute();
                            }
                            // --- if user exists, then will update user data in table
                            else if($recordCount == 1) {
                                $stmt = "update carbon_users set token = :token WHERE user_email = :userEmail";
                            
                                $sql = $this->pdo->prepare($stmt);
                                $sql->bindValue(":token",     $model["token"],     PDO::PARAM_STR);
                                $sql->bindValue(":userEmail", $model["userEmail"], PDO::PARAM_STR);
                                
                                $sql->execute();
                            }
                            // --- if more than one users with the same email
                            else {
                                throw new Exception("Duplicte Emails detected !!!");
                            }

                            // --- now will return the user details if all upserts are successful
                            $stmt = "select id as userId, user_name as userName, user_email as userEmail, user_address1 as userAddress1, user_address2 as userAddress2, 
                                        photo_url as photoUrl, about_me as aboutMe from carbon_users where user_email = :userEmail";
                            $sql = $this->pdo->prepare($stmt);
                            $sql->bindValue(":userEmail", $model["userEmail"], PDO::PARAM_STR);

                            $sql->execute();                           
                            $user = $sql->fetch(PDO::FETCH_OBJ);

                            echo json_encode($user, JSON_UNESCAPED_SLASHES );
                            break;
                    }

                    break;
            }            
        }
    }
