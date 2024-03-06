<?php

    require './vendor/autoload.php';

    use Kreait\Firebase\Factory;
    use Kreait\Firebase\ServiceAccount;

    // -- Firestore API
    use Google\Cloud\Firestore\FieldValue;
    use Google\Cloud\Firestore\FirestoreClient;
    use Kreait\Firebase\Contract\Firestore;

    // -- Messaging API
    use Kreait\Firebase\Contract\Messaging;
    use Kreait\Firebase\Messagin\Notification;
    use Kreait\Firebase\Messagin\CloudMessage;

    class FirebaseSDK {
        private $factory;


        // Factory is a FUNCTIONALITY that CREATES AN OBJECT that then CREATES A CONNECTION to Firebase USING our Firebase.json credentials
        // Factory also comes with A BUNCH OF METHODS which we will use later in several of the functions here
        public function __construct() {
            $this->factory = (new Factory) -> withServiceAccount(('./carbon-project-9a417-firebase.json'));
        }

        // ======== FIRESTORE
        public function firestoreGet($collName) {
            $firestore = $this->factory->createFirestore();

            $database = $firestore->database();
            $collection = $database->collection("{$collName}");
            $documents = $collection->documents();

            $bulletinArray = [];
            foreach($documents as $doc) {
                $postedByData = $doc->data()["postedBy"];
                $postedDateData = $doc->data()["postedDate"];

                array_push($bulletinArray,
                ['postedBy' => $doc->data()["postedBy"], 'postedDate' => $doc->data()['postedDate']]
                );
            }

            return $bulletinArray;
        }
        public function firestoreAdd($collName, $postedBy) {
            $firestore = $this->factory->createFirestore();

            $database = $firestore->database();
            $collection = $database->collection("{$collName}");
            
            $docRef = $collection->add(
                ['postedBy' => $postedBy, 'postedDate'=>FieldValue::serverTimestamp()]
            );
        }

        // ======== CloudStorage
    }