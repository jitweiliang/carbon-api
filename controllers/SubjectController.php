<?php
    require '../utilities/Database.php';

    Class subjectController {
        private $pdo;

        // Function Constructor
        public function __construct() {
            $db = new Database();
            $this->pdo = $db->getPDOObject();
        }

        // Private Methods
        public function processRequests(string $verb, ?array $params) {
            switch ($verb) {
                case 'GET':
                    $method = $params[2] ?? null;

                    if ($method) {
                        if ($method == 'id') {

                        }
                    }
                    else {
                        $stmt = "SELECT * FROM COURSES";

                        $sql = $this->pdo->prepare($stmt);
                        $sql->execute();
                        
                        $data = $sql->fetchAll(PDO::FETCH_ASSOC);
                    }
                    break;
            }
        }
    }