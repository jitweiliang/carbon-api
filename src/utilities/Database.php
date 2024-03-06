<?php 
    class Database {
        private $host     = '35.186.148.24';        // ! //     103.3.173.137
        private $db_name  = 'carbon-db';         // ! //     testdb
        private $username = 'root';             // ! //     looksee
        private $password = 'Aeiou321';         // ! //     return2626!

        private $conn;
        public function getPDOObject(): PDO {
            $this->conn = null;

            try { 
                $this->conn = new PDO('mysql:host=' . $this->host . ';dbname=' . $this->db_name, $this->username, $this->password);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } 
            catch(PDOException $e) {
                echo 'Connection Error: ' . $e->getMessage();
            }

            return $this->conn;
        }


        // --- 3. ==================== cloud sql via app engine ==================== --- //
        // private $host       = '/cloudsql/rliang-carbon-project:asia-southeast1:carbon-db';
        // private $username   = "root";
        // private $password   = "mycarbondb777!";
        // private $dbname    = "carbon-db";
        // //private $socketpath = '/cloudsql/rliang-carbon-project:asia-southeast1:carbon-db';
        
        // public function getPDOObject(): PDO {
        //     $this->conn = null;

        //     try { 
        //         $connString = 'mysql:dbname={$this->dbname};unix_socket={$this->host}';
        //         $this->conn = new PDO('mysql:dbname=carbon-db;unix_socket=/cloudsql/rliang-carbon-project:asia-southeast1:carbon-db', 
        //                         $this->username, $this->password);
        //     } 
        //     catch(PDOException $e) {
        //         echo 'Connection Error: ' . $e->getMessage();
        //     }

        //     return $this->conn;
        // }
  }
