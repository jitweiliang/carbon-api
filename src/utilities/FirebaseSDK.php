<?php

    require './vendor/autoload.php';
    
    // --- 
    use Kreait\Firebase\Factory;    
    use Kreait\Firebase\ServiceAccount;

    // -- cloud storage api
    use Google\Cloud\Storage\StorageClient;
    use Kreait\Firebase\Contract\Storage;

    // -- firestore api
    use Google\Cloud\Firestore\FieldValue;
    use Kreait\Firebase\Contract\Firestore;
    use Google\Cloud\Firestore\FirestoreClient;

    // -- messaging api
    use Kreait\Firebase\Contract\Messaging;
    use Kreait\Firebase\Messaging\Notification;
    use Kreait\Firebase\Messaging\CloudMessage;


    class FirebaseSDK {
        private $factory;

        public function __construct()
        {
            // --- create instance of firebase admin sdk with json credentials
            $this->factory = (new Factory)
                            ->withServiceAccount('./carbon-project-9a417-firebase.json');
        }


        // --- ====================== f i r e s t o r e ======================== //
        // --- this is for updating the bulletins collection in order to notify all listeners --- //
        public function firestoreGet($collName) {
            $firestore = $this->factory->createFirestore();

            $database = $firestore->database();
            $collection = $database->collection("{$collName}");
            $documents = $collection->documents();

            $bulletinArray = [];
            foreach($documents as $doc) {
                array_push($bulletinArray, 
                    ['postedBy' => $doc->data()["postedBy"], 'postedDate' => $doc->data()['postedDate']]);
            }

            return $bulletinArray;
        }
        public function firestoreAdd($collName, $postedBy) {
            $firestore = $this->factory->createFirestore();
            
            $database = $firestore->database();
            $collection = $database->collection("${collName}");

            $docRef = $collection->add(
                ['postedBy' => $postedBy, 'postedDate' => FieldValue::serverTimestamp()]);
        }



        // --- ==================== c l o u d    s t o r a g e ================== --- //
        public function storageStoreImage($fileName) {
            $storage = $this->factory->createStorage();
            $storageClient = $storage->getStorageClient();
            $bucket        = $storage->getBucket();

            $blob = $bucket->object($fileName);
            $object = $blob->downloadToFile('D:/testertest.docx');
        }
        public function storageGetImage($fileName) {
            $storage = $this->factory->createStorage();
            $storageClient = $storage->getStorageClient();
            $bucket        = $storage->getBucket();

            $blob = $bucket->object($fileName);
            $imageData = $blob->downloadAsString();

            return $imageData;
        }
        public function storageAdd($tmpFileName) {
            $storage = $this->factory->createStorage();
            $storageClient = $storage->getStorageClient();
            $bucket        = $storage->getBucket();

            // // --- option to generate random file name
            // $stream = fopen($tmpFileName, 'r+');
            // $prefix = substr(microtime(), -4, 4); // unique number generator
            // $name = $prefix . str_replace(' ', '_', 'testload.png'); // remove space

            // ----
            $name = $tmpFileName;
            
            $bucket->upload($stream, ['name' => $name ]);
        }




        // --- ============== p u s h   n o t i f i c a t i o n s ============== --- //
        public function messagingPost($deviceToken) {
            $messaging = $this->factory->createMessaging();
            $message = CloudMessage::withTarget('token', $deviceToken)
                        ->withNotification(Notification::create('Title', 'Body'))
                        ->withData(['key' => 'value']);
            $messaging->send($message);

            return true;
        } 
    }

    