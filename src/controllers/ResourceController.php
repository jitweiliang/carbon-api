<?php
    require "./src/utilities/Database.php";
    require "./src/utilities/FirebaseSDK.php";

    class ResourceController {
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
                case "GET":
                    switch (true) {
                        case preg_match('/\/api\/resources$/', $uri):
                            $stmt = "select id as rssId, rss_url as url, rss_title as title, rss_description as description, rss_type as type
                                        from carbon_rss";
                            $sql = $this->pdo->prepare($stmt);
                            $sql->execute();
                            
                            $data = $sql->fetchAll(PDO::FETCH_OBJ);
                            echo json_encode($data, JSON_UNESCAPED_SLASHES);

                            break;
                    }
                    break;

                case "POST":
                    switch (true) {
                        case preg_match('/\/api\/resources$/', $uri):

                            // --- get json data from request
                            $model = (array) json_decode(file_get_contents("php://input"), true);

                            // -- Prepare the Update Statement
                            $stmt = "update carbon_rss
                                        set rss_url=:url, rss_title=:title, rss_description=:description, rss_type=:type
                                    where id=:id";
                            $sql = $this->pdo->prepare($stmt);

                            // -- Bind all the values from the model to the statement, then execute it
                            $sql->bindValue(":url",           $model["url"],         PDO::PARAM_STR);
                            $sql->bindValue(":title",         $model["title"],       PDO::PARAM_STR);
                            $sql->bindValue(":description",   $model["description"], PDO::PARAM_STR);
                            $sql->bindValue(":type",          $model["type"],       PDO::PARAM_INT);
                            $sql->execute();
                                    
                            // -- Receive the return data 
                            $data = $sql->fetchAll(PDO::FETCH_OBJ);
                            echo json_encode($data);    

                            break;
                    }
                    break;
            }
        }
    }