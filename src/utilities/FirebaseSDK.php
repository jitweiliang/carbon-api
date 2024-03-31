<?php
    require './vendor/autoload.php';        // this is mandatory as per firebase admin sdk requirement

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
    use Kreait\Firebase\Messaging\Notification;
    use Kreait\Firebase\Messaging\CloudMessage;

    class FirebaseSDK {
        private $factory;


        // Factory is a FUNCTIONALITY that CREATES AN OBJECT that then CREATES A CONNECTION to Firebase USING our Firebase.json credentials
        // Factory also comes with A BUNCH OF METHODS which we will use later in several of the functions here
        public function __construct() {
            // the json file (a key file) is generated from the firebase account to access firebase admin processes
            $this->factory = (new Factory) -> withServiceAccount(('./carbon-project-9a417-firebase.json'));
        }


        // ======== Firestore
        public function firestoreAdd($collName, $postedBy) {
            // create an instance of firestore
            $fireStore = $this->factory->createFirestore();

            $fireDatabase = $fireStore->database();
            // must specify the name of the colection to be used
            $fireCollection = $fireDatabase->collection("{$collName}");
            
            // -- just insert a new row with the current datetime (serverTimestamp)
            $docRef = $fireCollection->add(
                ['postedBy' => $postedBy, 'postedDate'=>FieldValue::serverTimestamp()]  // this will take the current time
            );
        }
        // ======== CloudStorage
        public function storageStoreImage($imgTmpName, $imgFileName) {
            // create an instance of cloud storage
            $fireStorage = $this->factory->createStorage();
            $fireBucket = $fireStorage->getBucket();

            
            // -- generate a random filename for image to be uploaded to gcloud
            $fileNameParts = explode('.', $imgFileName);
            $extension = end($fileNameParts);
            $prefix = substr(microtime(), -5, 5); // unique number generator

            $newImageFileName = "profile_{$prefix}.{$extension}";
    
            // --- $imgTmpName is a random generated name for temporary files uploaded to php
            $stream = fopen($imgTmpName, 'r+');
            $result = $fireBucket->upload($stream, ['name' => $newImageFileName]);

            return $newImageFileName;
        }
        // ========== Push Notification
        public function sendNotificationToOneDevice($token, $title, $body) {
            // $noficationsArray = [{token=>token, title=>title, body=>body}]
            try {
                $fireMessaging = $this->factory->createMessaging();

                $message = CloudMessage::withTarget('token', $token)
                            ->withNotification(Notification::create($title, $body))
                            ->withData(['key' => 'value']);
                $fireMessaging->send($message);
    
                return 1;    
            }
            catch(Exception $ex) {
                return 0;
            }
        } 
    }