<?php

/**
 * The Website Model
 *
 * @author Faizan Ayubi
 */
class Website extends Shared\Model {

    /**
     * @column
     * @readwrite
     * @type integer
     * @index
     */
    protected $_user_id;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 255
     * 
     * @validate required, alpha, min(3), max(32)
     * @label title
     */
    protected $_title;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 255
     * @index
     * 
     * @validate required
     * @label It is the domain only (like playmusic.net)
     */
    protected $_url;

    /**
     * @column
     * @readwrite
     * @type boolean
     * 
     * @label 
     */
    protected $_ping_live = false;


    /**
     * @column
     * @readwrite
     * @type boolean
     * 
     * @label time interval in minutes
     */
    protected $_ping_interval = NULL;

    /**
     * @column
     * @readwrite
     * @type boolean
     */
    protected $_owner = false;

}
