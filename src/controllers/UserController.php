<?php
    require "./src/utilities/Database.php";
    require "./src/utilities/FirebaseSDK.php";

    require "IController.php";

    class UserController implements IController
    {
        private $pdo;
        private $sdk;

        public function __construct()
        {
            // --- get a new PDO object for mysql connection
            $db = new Database();
            $this->pdo = $db->getPDOObject();

            $this->sdk = new FirebaseSDK();
        }


        public function processRequest(string $verb, ?string $uri): void
        {
            switch ($verb) {
                // ============================ G E T ==============================
                case "GET":
                    switch(true) {
                        // -- get all users
                        case preg_match('/\/api\/users$/', $uri):
                            $stmt = "select id as id, user_name as userName, user_email as userEmail, photo_url as photoUrl, about_me as aboutMe 
                                        from carbon_users";
                            $sql = $this->pdo->prepare($stmt);
                            $sql->execute();
                            
                            $data = $sql->fetchAll(PDO::FETCH_OBJ);
                            echo json_encode($data);    

                            break;
                        // -- get single user by id
                        case preg_match('/\/api\/users\/id\/[1-9]/', $uri):
                            // this is the last parameter in the url
                            $param = basename($uri);    

                            $stmt = "select id as id, user_name as userName, user_email as userEmail, user_address1 as userAddress1, user_address2 as userAddress2,
                                        user_contactno as userContactNo, photo_url as photoUrl, about_me as aboutMe 
                                        from carbon_users WHERE id = :id";
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
                        case preg_match('/\/api\/users$/', $uri):
                            // --- get json data from request
                            $model = (array) json_decode(file_get_contents("php://input"), true);

                            $stmt = "update carbon_users 
                                            set user_name=:userName, user_address1=:userAddress1, user_address2=:userAddress2, user_contactNo=:userContactNo, about_me=:aboutMe  
                                            where id=:id";
                            $sql = $this->pdo->prepare($stmt);
                            
                            $sql->bindValue(":userName",      $model["userName"],      PDO::PARAM_STR);
                            $sql->bindValue(":userAddress1",  $model["userAddress1"],  PDO::PARAM_STR);
                            $sql->bindValue(":userAddress2",  $model["userAddress2"],  PDO::PARAM_STR);
                            $sql->bindValue(":userContactNo", $model["userContactNo"], PDO::PARAM_STR);
                            $sql->bindValue(":aboutMe",       $model["aboutMe"],       PDO::PARAM_STR);
                            $sql->bindValue(":id",            $model["id"],            PDO::PARAM_INT);                    

                            $sql->execute();                   
                            echo json_encode($sql->rowCount());

                            break;
                    }

                    break;
                // ============================ P O S T ============================
                case "POST":
                    switch(true) {
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

                                // ---
                                if($imageFileName) {
                                    // https://storage.googleapis.com/<<bucket name>>/5813testload.png
                                    $photoUrl = "https://storage.googleapis.com/carbon-project-9a417.appspot.com/{$imageFileName}";
                                    $stmt = "update carbon_users set photo_url = :photoUrl where user_email = :userEmail";
                
                                    $sql = $this->pdo->prepare($stmt);
                                    $sql->bindValue(":photoUrl",  $photoUrl, PDO::PARAM_STR);
                                    $sql->bindValue(":userEmail", $userEmail, PDO::PARAM_STR);
                                    $sql->execute();
                                }

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
                                $sql->bindValue(":userName",  $model["userName"],  PDO::PARAM_STR);
                                $sql->bindValue(":userEmail", $model["userEmail"], PDO::PARAM_STR);
                                $sql->bindValue(":provider",  $model["provider"],  PDO::PARAM_STR);
                                $sql->bindValue(":token",     $model["token"],     PDO::PARAM_STR);
                
                                $sql->execute();
                                echo json_encode($sql->rowCount());
                            }
                            // --- if user exists, then will update user data in table
                            else if($recordCount == 1) {
                                $stmt = "update carbon_users set token = :token WHERE user_email = :userEmail";
                            
                                $sql = $this->pdo->prepare($stmt);
                                $sql->bindValue(":token",     $model["token"],     PDO::PARAM_STR);
                                $sql->bindValue(":userEmail", $model["userEmail"], PDO::PARAM_STR);
                                
                                $sql->execute();
                                echo json_encode($sql->rowCount());
                            }
                            // --- if more than one users with the same email
                            else {
                                throw new Exception("Duplicte Emails detected !!!");
                            }

                            break;
                    }
                    break;
                // ========================== E R R O R  ===========================
                default:
                    throw new Exception("Invalid User Controller request");
            }            
        }
    }
