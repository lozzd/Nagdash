<?php

interface iNagiosConnection
{
    /**
     * get the current state of the nagios instance
     *
     * Returns an array of the form
     *  ["errors" => true/false, "details" => "message"]
     */
    public function getState();

    /**
     * acknowledge a problem
     *
     * Parameter
     *  $details - array with problem meta data like
     *             [
     *              "host" => $host,
     *              "service" => $service,
     *              "comment" => $comment,
     *              "author" => $author,
     *              "duration" => $duration
     *              ]
     *
     * Returns an array of the form
     *  ["errors" => true/false, "details" => "message"]
     */
    public function acknowledge($details);

    /**
     * enable notifications for a host/service
     *
     * Parameter
     *  $target - host or service to enable notifications for
     *
     * Returns an array of the form
     *  ["errors" => true/false, "details" => "message"]
     */
    public function enableNotifications($target);

    /**
     * disable notifications for a host/service
     *
     * Parameter
     *  $target - host or service to disable notifications for
     *
     * Returns an array of the form
     *  ["errors" => true/false, "details" => "message"]
     */
    public function disableNotifications($target);

    /**
     * set downtime for a host or service
     *
     * Parameter:
     *  $target - host or service to set downtime fork
     *
     * Returns an array of the form
     *  ["errors" => true/false, "details" => "message"]
     */
    public function setDowntime($target);
}

