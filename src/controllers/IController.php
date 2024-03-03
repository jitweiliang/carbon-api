<?php

    interface IController {
        public function processRequest(string $verb, ?string $url): void;
    }
    