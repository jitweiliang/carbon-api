<?php

    require './vendor/autoload.php';

    use Kreait\Firebase\Factory;
    use Kreait\Firebase\ServiceAccount;

    // -- Firestore API
    use Google\Cloud\Firestore\FieldValue;
    use Google\Cloud\Firestore\FirestoreClient;
    use Kreait\Firebase\Contract\Firestore;

    // -- Cloud Storage API
    use Google\Cloud\Storage\StorageClient;
    use Kreait\Firebase\Contract\Storage;

    // -- Messaging API
    use Kreait\Firebase\Contract\Messaging;
    use Kreait\Firebase\Messagin\Notification;
    use Kreait\Firebase\Messagin\CloudMessage;

    class FirebaseSDK {
        private $factory;


        // Factory is a FUNCTIONALITY that CREATES AN OBJECT that then CREATES A CONNECTION to Firebase USING our Firebase.json credentials
        // Factory also comes with A BUNCH OF METHODS which we will use later in several of the functions here
        public function __construct() {
            // the json file (a key file) is generated from the firebase account to access firebase admin processes
            $this->factory = (new Factory) -> withServiceAccount(('./carbon-project-9a417-firebase.json'));
        }

        // ======== FIRESTORE
        // public function firestoreGet($collName) {
        //     $firestore = $this->factory->createFirestore();

        //     $database = $firestore->database();
        //     $collection = $database->collection("{$collName}");
        //     $documents = $collection->documents();

        //     $bulletinArray = [];
        //     foreach($documents as $doc) {
        //         $postedByData = $doc->data()["postedBy"];
        //         $postedDateData = $doc->data()["postedDate"];

        //         array_push($bulletinArray,
        //         ['postedBy' => $doc->data()["postedBy"], 'postedDate' => $doc->data()['postedDate']]
        //         );
        //     }

        //     return $bulletinArray;
        // }
        public function firestoreAdd($collName, $postedBy) {
            // create an instance of firestore
            $firestore = $this->factory->createFirestore();

            $database = $firestore->database();
            // must specify the name of the colection to be used
            $collection = $database->collection("{$collName}");
            
            // -- just insert a new row with the current datetime (serverTimestamp)
            $docRef = $collection->add(
                ['postedBy' => $postedBy, 'postedDate'=>FieldValue::serverTimestamp()]
            );
        }

        // ======== CloudStorage
        public function storageStoreImage($imgTmpName, $imgFileName) {
            // create an instance of cloud storage
            $storage = $this->factory->createStorage();
            $bucket = $storage->getBucket();

            
            // -- generate a random filename for image to be uploaded to gcloud
            $fileNameParts = explode('.', $imgFileName);
            $extension = end($fileNameParts);
            $prefix = substr(microtime(), -5, 5); // unique number generator

            $newImageFileName = "profile_{$prefix}.{$extension}";
    
            // --- $imgTmpName is a random generated name for temporary files uploaded to php
            $stream = fopen($imgTmpName, 'r+');
            $result = $bucket->upload($stream, ['name' => $newImageFileName]);

            return $newImageFileName;
        }
    }