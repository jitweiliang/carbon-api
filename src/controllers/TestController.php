<?php
    require "IController.php";
    require "./src/utilities/FirebaseSDK.php";

    class TestController implements IController {
        private $sdk;

        public function __construct()
        {
            $this->sdk = new FirebaseSDK();
        }

        public function processRequest(string $verb, ?string $uri): void
        {
            switch ($verb) {
                // ============================ G E T ==============================
                case "GET":
                    switch(true) {
                        // --- ==================== f i r e s t o r e ===================== --- //
                        case preg_match('/\/api\/test\/test$/', $uri):
                            echo json_encode('test okok');    

                            break;
                        case preg_match('/\/api\/test\/firestore$/', $uri):
                            $data = $this->sdk->firestoreGet();
                            echo json_encode($data);    

                            break;
                        // --- ================= c l o u d   s t o r a g e ================ --- //
                        case preg_match('/\/api\/test\/storage$/', $uri):
                            $data = $this->sdk->storageGet();
                            echo json_encode($data);    

                            break;
                        case preg_match('/\/api\/test\/image$/', $uri):
                            $data = $this->sdk->storageImage('rllogo.png');

                            header('Content-Type: image/jpeg');
                            echo $data;    
    
                            break;
                        // --- ================ f c m   m e s s a g i n g ================= --- //
                        case preg_match('/\/api\/test\/schedule$/', $uri):
                            $data = $this->sdk->messagingGet();
                            echo json_encode($data); 
    
                            break;
                        case preg_match('/\/api\/test\/messaging$/', $uri):
                            $data = $this->sdk->messagingGet();
                            echo json_encode($data);
    
                            break;
                        default:
                            throw new Exception("Invalid !!!");
                            break;
                    }
                    break;
                // ============================= P U T =============================
                // case "PUT":
                //     $model = (array) json_decode(file_get_contents("php://input"), true);
                //     $stmt = "update carbon_bulletins set user_id = :userId, message = :message, img_url = :imgUrl WHERE id = :id";
                //     $sql = $this->pdo->prepare($stmt);
                //     $sql->bindValue(":userId",      $model["userEmail"], PDO::PARAM_STR);
                //     $sql->bindValue(":message",     $model["photoUrl"],  PDO::PARAM_STR);
                //     $sql->bindValue(":imgUrl",      $model["accType"],   PDO::PARAM_STR);
                //     $sql->bindValue(":id",          $model["id"],        PDO::PARAM_INT);                    
                //     $sql->execute();                   
                //     echo json_encode($sql->rowCount());
                //     break;
                // =========================== D E L E T E ==========================
                // case "DELETE":
                //     $method = $params[2] ?? null;
                //     if($method) {
                //         if($method == "id") {
                //             $key = $params[3];
                //             $stmt = "delete FROM courses WHERE id = :key";                    
                //             $sql = $this->pdo->prepare($stmt);
                //             $sql->bindValue(":key", $key, PDO::PARAM_INT);
                //             $sql->execute();                            
                //             echo json_encode($sql->rowCount());
                //         }
                //     }
                //     break;
                // ============================ P O S T ============================
                // -- api/bulletin
                case "POST":
                    case preg_match('/\/api\/test\/images$/', $uri):
                        $imageFileName = $_FILES['imgfile']['name'];
                        $imageFileType = $_FILES['imgfile']['type'];
                        $imageFileSize = $_FILES['imgfile']['size'];
                        //$imageFileContents = file_get_contents($imageFile);

                        $imageTmpName = $_FILES['imgfile']['tmp_name'];
                        $this->sdk->storageAdd($imageTmpName);
                    break;
        // ========================== E R R O R  ===========================
                default:
                    http_response_code(405);
                    header("Allow: GET, PATCH, DELETE");
                    break;
                }
        }
    }