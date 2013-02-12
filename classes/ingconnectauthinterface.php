<?php

/**
 * Interface for authentication handlers
 */
interface INGConnectAuthInterface
{
    /**
     * This method is used to process the first part of authentication workflow, before redirect
     *
     * @return array Array with status and redirect URI
     */
    public function getRedirectUri();

    /**
     * This method is used to process the second part of authentication workflow, after redirect
     *
     * @return array Array with status and user details
     */
    public function processAuth();
}
