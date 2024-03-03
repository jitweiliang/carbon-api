<?php
    require "./utilities/Database.php";

    class CourseController
    {
        private $pdo;

        public function __construct()
        {
            $db = new Database();
            $this->pdo = $db->getPDOObject();
        }



        // ----- private methods
        public function processRequest(string $verb, ?array $params): void
        {
            switch ($verb) {
                // ============================ G E T ==============================
                // api/course/id/## or api/course
                case "GET":
                    $method = $params[2] ?? null;

                    if($method) {
                        if($method == "id") {
                            $key = $params[3];

                            $stmt = "SELECT * FROM courses WHERE id = :id";
                    
                            $sql = $this->pdo->prepare($stmt);
                            $sql->bindValue(":id", $key, PDO::PARAM_INT);
                            $sql->execute();
                            
                            $data = $sql->fetch(PDO::FETCH_ASSOC);
                            echo json_encode($data);    
                        }
                    }
                    else {
                        $stmt = "SELECT * FROM courses";
                        
                        $sql = $this->pdo->prepare($stmt);
                        $sql->execute();

                        $data = $sql->fetchAll(PDO::FETCH_ASSOC);
                        echo json_encode($data);
                    }

                    break;                   
                // ============================= P U T =============================
                // api/course
                case "PUT":
                    $model = (array) json_decode(file_get_contents("php://input"), true);                   

                    $stmt = "UPDATE courses set course_name = :name, code = :code, status = :status WHERE id = :id";
                    
                    $sql = $this->pdo->prepare($stmt);
                    $sql->bindValue(":name", $model["courseName"], PDO::PARAM_STR);
                    $sql->bindValue(":code", $model["code"], PDO::PARAM_STR);
                    $sql->bindValue(":status", $model["status"], PDO::PARAM_INT);
                    $sql->bindValue(":id", $model["id"], PDO::PARAM_INT);
                    
                    $sql->execute();                   
                    echo json_encode($sql->rowCount());

                    break;
                // =========================== D E L E T E ==========================
                // api/course/id/##
                case "DELETE":
                    $method = $params[2] ?? null;

                    if($method) {
                        if($method == "id") {
                            $key = $params[3];

                            $stmt = "delete FROM courses WHERE id = :key";
                    
                            $sql = $this->pdo->prepare($stmt);
                            $sql->bindValue(":key", $key, PDO::PARAM_INT);

                            $sql->execute();                            
                            echo json_encode($sql->rowCount());
                        }
                    }

                    break;
                // ============================ P O S T ============================
                // api/course
                case "POST":
                    $model = (array) json_decode(file_get_contents("php://input"), true);                   
                
                    $stmt = "insert into courses (code, course_name, status) values (:code, :courseName, :status)";

                    $sql = $this->pdo->prepare($stmt);
                    $sql->bindValue(":code", $model["code"], PDO::PARAM_STR);
                    $sql->bindValue(":courseName", $model["courseName"], PDO::PARAM_STR);
                    $sql->bindValue(":status", $model["status"], PDO::PARAM_INT);
                                    
                    $sql->execute();
                    echo json_encode($sql->rowCount());

                    break;
                // ========================== E R R O R  ===========================
                default:
                    http_response_code(405);
                    header("Allow: GET, PATCH, DELETE");
                    break;
                }
        }
    }