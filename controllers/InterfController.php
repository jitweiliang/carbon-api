<?php

    interface InterfController {
        public function processRequest(string $verb, ?string $url): void;
    }
    