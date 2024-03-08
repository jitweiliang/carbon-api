<?php
    require "./src/utilities/Database.php";
    require "IController.php";

    class UserController implements IController
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
                        case preg_match('/\/api\/users$/', $uri):
                            $stmt = "select * FROM carbon_users";
                            $sql = $this->pdo->prepare($stmt);
                            $sql->execute();
                            
                            $data = $sql->fetchAll(PDO::FETCH_OBJ);
                            echo json_encode($data);    

                            break;
                        // -- get single user by id
                        case preg_match('/\/api\/users\/id\/[1-9]/', $uri):
                            // this is the last parameter in the url
                            $param = basename($uri);    

                            $stmt = "SELECT * FROM carbon_users WHERE id = :id";
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

                    $stmt = "update carbon_users 
                                set user_name = :userName, photo_url = :photoUrl where id=:id";
                    $sql = $this->pdo->prepare($stmt);
                    
                    $sql->bindValue(":userName", $model["userName"], PDO::PARAM_STR);
                    $sql->bindValue(":photoUrl", $model["photoUrl"], PDO::PARAM_STR);
                    $sql->bindValue(":id",       $model["id"],       PDO::PARAM_INT);                    

                    $sql->execute();                   
                    echo json_encode($sql->rowCount());

                    break;
                // ============================ P O S T ============================
                case "POST":
                    switch(true) {
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

                        case preg_match('/\/api\/users\/img$/', $uri):
                            $imageFileName = $_FILES['imgfile']['name'];
                            $userId = $_POST["userid"];

                            $fileNameParts = explode('.', $imageFileName);
                            $extension = end($fileNameParts);
                            $imageName = "profile_{$userId}.{$extension}";

                            $imageTmpName = $_FILES['imgfile']['tmp_name'];
                            $fileStream = fopen($imageTmpName, 'r+');

                            $this->sdk->storageStoreImage($imageName, $fileStream);

                            break;
                    }
                    break;
                // ========================== E R R O R  ===========================
                default:
                    throw new Exception("Invalid User Controller request");
            }            
        }
    }   