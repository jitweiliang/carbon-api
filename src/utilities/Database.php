<?php 
    // https://www.php.net/manual/en/pdo.connections.php

    class Database {
        // // // This is the CLOUD one
        // private $host     = '35.186.148.24';
        // private $db_name  = 'carbon-db';
        // private $username = 'root';
        // private $password = 'Aeiou321';

        // private $conn;
        // public function getPDOObject(): PDO {
        //     $this->conn = null;
        //     try { 
        //         $this->conn = new PDO('mysql:host=' . $this->host . ';dbname=' . $this->db_name, $this->username, $this->password);
        //         $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        //     } 
        //     catch(PDOException $e) {
        //    throw new Exception($e->getMessage());
        //     }
        
        //     return $this->conn;
        // }



        // --- 2. ==================== cloud sql via app engine ==================== --- //
        private $conn;
        private $host       = '/cloudsql/carbon-project-9a417:asia-southeast1:carbon-db';
        private $username   = "root";
        private $password   = "Aeiou321";
        private $dbname     = "carbon-db";
        
        public function getPDOObject(): PDO {
            $this->conn = null;
            try { 
                // 'mysql:dbname=carbon-db;unix_socket=/cloudsql/carbon-project-9a417:asia-southeast1:carbon-db'
                
                $connString = "mysql:dbname={$this->dbname};unix_socket={$this->host}";
                $this->conn = new PDO($connString, $this->username, $this->password);
            } 
            catch(PDOException $e) {
                throw new Exception($e->getMessage());
            }

            return $this->conn;
        }
  }
